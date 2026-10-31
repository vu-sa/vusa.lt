<?php

namespace App\Console\Commands;

use App\Actions\ActivityRequests\ResolveActivityRequestEligibility;
use App\Actions\ActivityRequests\ResolveActivityRequestRecipients;
use App\Actions\ActivityRequests\SendInstitutionActivityRequests;
use App\Actions\ResolveTaskAssignees;
use App\Enums\InstitutionActivityStatus;
use App\Listeners\QueueNotificationForDigest;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\Meeting;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Task;
use App\Models\User;
use App\Services\AcademicCalendarService;
use App\States\ReservationResource\Lent;
use App\States\ReservationResource\Reserved;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use App\Tasks\Handlers\AgendaCreationTaskHandler;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use App\Tasks\Handlers\PickupTaskHandler;
use App\Tasks\Handlers\ReturnTaskHandler;
use App\ValueObjects\InstitutionActivityStatusData;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Create the automatic tasks the current state calls for, without duplicating open ones.
 *
 * `institution` runs daily: besides creating periodicity gap tasks it completes, cancels or
 * reassigns the open ones and sends "Ar vyko posėdis?" to overdue institutions, so it does send
 * emails. `reservation` and `meeting` are backfills (pickup/return and agenda tasks).
 */
#[Description('Create missing automatic tasks; for institutions also reconcile open ones and ask overdue institutions about their activity')]
#[Signature('tasks:repopulate
                            {model? : Model type to repopulate (institution, reservation, meeting)}
                            {--force : Skip the confirmation asked when no model type is given}
                            {--dry-run : Show what would be done without changing anything or sending emails}
                            {--include-past : Include past meetings (useful for historical data entry)}')]
class RepopulateTasks extends Command
{
    /** @var list<array{task: Task, institution: Institution, recipients: Collection<int, User>}> */
    private array $pendingAsks = [];

    /** @var array{completed: int, cancelled: int, reassigned: int, overdue: int, questions: int|null} */
    private array $outcomes = ['completed' => 0, 'cancelled' => 0, 'reassigned' => 0, 'overdue' => 0, 'questions' => null];

    /**
     * Model alias mapping to handler methods.
     */
    protected array $modelHandlers = [
        'institution' => 'repopulateInstitutionTasks',
        'reservation' => 'repopulateReservationTasks',
        'meeting' => 'repopulateMeetingTasks',
    ];

    public function __construct(
        protected PeriodicityGapTaskHandler $periodicityGapHandler,
        protected PickupTaskHandler $pickupHandler,
        protected ReturnTaskHandler $returnHandler,
        protected AgendaCreationTaskHandler $agendaCreationHandler,
        protected AgendaCompletionTaskHandler $agendaCompletionHandler,
        protected AcademicCalendarService $academicCalendar,
        protected ResolveActivityRequestEligibility $activityEligibility,
        protected ResolveActivityRequestRecipients $activityRecipients,
        protected SendInstitutionActivityRequests $activityRequests,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $model = $this->argument('model');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN - nothing will be changed or sent');
        }

        if ($model && ! isset($this->modelHandlers[$model])) {
            $this->error("Unknown model type: {$model}");
            $this->line('Available types: '.implode(', ', array_keys($this->modelHandlers)));

            return self::FAILURE;
        }

        if (! $model && ! $this->option('force') && ! $this->confirm('Repopulate tasks for all model types?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($model ? [$model] : array_keys($this->modelHandlers) as $modelType) {
            $this->info("Processing {$modelType} tasks...");

            // A backfill should not fill digests with old news. The institution run is the daily one,
            // and its questions must reach people who take their email as a digest.
            QueueNotificationForDigest::$skipDigest = $modelType !== 'institution';

            try {
                $result = $this->{$this->modelHandlers[$modelType]}($dryRun);
            } finally {
                QueueNotificationForDigest::$skipDigest = false;
            }

            $totalCreated += $result['created'];
            $totalSkipped += $result['skipped'];

            $this->line("  Created: {$result['created']}, Skipped: {$result['skipped']}");
        }

        $this->newLine();
        $this->info($dryRun ? 'Summary (dry run):' : 'Summary:');
        $this->table(['Metric', 'Count'], [
            ['Tasks created', $totalCreated],
            ['Tasks already open', $totalSkipped],
            ['Open tasks completed', $this->outcomes['completed']],
            ['Open tasks cancelled', $this->outcomes['cancelled']],
            ['Open tasks reassigned', $this->outcomes['reassigned']],
            ['Overdue institutions', $this->outcomes['overdue']],
            // Most overdue institutions were asked within the month or have nobody to ask.
            ['Questions sent', $this->outcomes['questions'] ?? '–'],
        ]);

        return self::SUCCESS;
    }

    /**
     * Create periodicity gap tasks for institutions approaching their threshold, reconcile the open
     * ones, and ask "Ar vyko posėdis?" once an institution turns overdue.
     *
     * @return array{created: int, skipped: int}
     */
    protected function repopulateInstitutionTasks(bool $dryRun): array
    {
        $created = 0;
        $skipped = 0;
        $institutions = Institution::query()
            ->where('is_active', true)
            ->with(['types', 'meetings:id,start_time', 'checkIns'])
            ->get();

        $candidates = $this->activityRecipients->forInstitutions($institutions->pluck('id'));
        $bar = $this->output->createProgressBar($institutions->count());
        foreach ($bar->iterate($institutions) as $institution) {
            $roles = $candidates[$institution->id];
            $tenureStart = collect($roles['secretaries'] ?: $roles['representatives'])->min('held_since');
            $activityStatus = $this->activityEligibility->status($institution, $tenureStart);
            $existingTask = $this->periodicityGapHandler->findExistingTask($institution);

            if ($existingTask) {
                $skipped++;
                $this->reconcilePeriodicityTask($existingTask, $institution, $activityStatus, $dryRun);

                continue;
            }

            $created += (int) $this->createPeriodicityTask($institution, $activityStatus, $dryRun);
        }
        $this->newLine();

        $this->cancelTasksOfInactiveInstitutions($institutions->modelKeys(), $dryRun);

        $this->outcomes['overdue'] += count($this->pendingAsks);
        if (! $dryRun) {
            $before = InstitutionActivityRequest::query()->count();
            $this->activityRequests->forTasks($this->pendingAsks);
            $this->outcomes['questions'] = InstitutionActivityRequest::query()->count() - $before;
        }
        $this->pendingAsks = [];

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** A task for an institution approaching or past its threshold; overdue ones are also asked. */
    private function createPeriodicityTask(Institution $institution, InstitutionActivityStatusData $activityStatus, bool $dryRun): bool
    {
        $isOverdue = $activityStatus->status === InstitutionActivityStatus::Overdue;
        if (! $isOverdue && $activityStatus->status !== InstitutionActivityStatus::Approaching) {
            return false;
        }

        // Administrators for the current term, else today's representatives.
        $representatives = ResolveTaskAssignees::forInstitution($institution);
        if ($representatives->isEmpty()) {
            return false;
        }

        $this->detail($dryRun, ($dryRun ? 'Would create: ' : 'Created: ').$institution->name.($isOverdue ? ' (and ask)' : ''));
        if ($dryRun) {
            return true;
        }

        $daysUntilThreshold = $isOverdue ? -1 : $activityStatus->periodicityDays - ($activityStatus->effectiveDaysSinceActivity ?? $activityStatus->periodicityDays);

        // Due a week after the institution turns overdue, so the weekly overdue reminder follows the
        // question instead of arriving with it. Vacation days are skipped.
        $dueDate = Carbon::instance(
            $this->academicCalendar->addEffectiveDays(Carbon::today(), max(0, $daysUntilThreshold + 1) + 7)
        );

        $task = $this->periodicityGapHandler->findOrCreate(
            institution: $institution,
            users: $representatives,
            dueDate: $dueDate,
            activityStatus: $activityStatus,
        );

        if ($isOverdue) {
            $this->askAboutActivity($task, $institution, $dryRun);
        }

        return true;
    }

    /**
     * Collected and sent together after the loop, so each assignee gets one email for the whole run.
     * Idempotent per task and recipient, so the daily run asks each assignee once.
     */
    private function askAboutActivity(Task $task, Institution $institution, bool $dryRun): void
    {
        if ($dryRun) {
            $this->detail($dryRun, "Overdue, would ask unless already asked: {$institution->name}");
            $this->outcomes['overdue']++;

            return;
        }

        $this->pendingAsks[] = ['task' => $task, 'institution' => $institution, 'recipients' => $task->notifiableUsers()];
    }

    /**
     * An open task keeps the people from the day it was made. Once its gap is closed by a meeting or a
     * report it is completed; once only a new term made it moot (nothing was reported, but the new
     * people have not been in office long enough to owe anything) it is cancelled. While it stands,
     * it follows the current people, so its question never goes to last term's.
     */
    private function reconcilePeriodicityTask(Task $task, Institution $institution, InstitutionActivityStatusData $status, bool $dryRun): void
    {
        if (! $status->status->requiresAction()) {
            $resolvedByActivity = ! $this->activityEligibility->status($institution)->status->requiresAction();
            $outcome = $resolvedByActivity ? 'completed' : 'cancelled';
            $this->outcomes[$outcome]++;
            $this->detail($dryRun, ($dryRun ? 'Would mark ' : 'Marked ')."{$outcome}: {$institution->name}");

            if (! $dryRun) {
                $resolvedByActivity
                    ? $this->periodicityGapHandler->completeForInstitution($institution)
                    : $this->periodicityGapHandler->cancelTask($task);
            }

            return;
        }

        $current = ResolveTaskAssignees::forInstitution($institution)->pluck('id');
        if ($current->isNotEmpty() && $current->sort()->values()->all() !== $task->users->pluck('id')->sort()->values()->all()) {
            $this->outcomes['reassigned']++;
            $this->detail($dryRun, ($dryRun ? 'Would reassign: ' : 'Reassigned: ').$institution->name);

            if (! $dryRun) {
                $task->users()->sync($current);
                $task->load('users');
            }
        }

        if ($status->status === InstitutionActivityStatus::Overdue) {
            $this->askAboutActivity($task, $institution, $dryRun);
        }
    }

    /**
     * A deactivated institution owes no more reports. Deleted ones take their tasks with them (HasTasks).
     *
     * @param  list<string>  $activeInstitutionIds
     */
    private function cancelTasksOfInactiveInstitutions(array $activeInstitutionIds, bool $dryRun): void
    {
        $tasks = Task::query()
            ->where('taskable_type', MorphMap::alias(Institution::class))
            ->where('action_type', ActionType::PeriodicityGap)
            ->whereNull('completed_at')
            ->whereNotIn('taskable_id', $activeInstitutionIds)
            ->get();

        foreach ($tasks as $task) {
            $this->outcomes['cancelled']++;
            $this->detail($dryRun, ($dryRun ? 'Would mark' : 'Marked')." cancelled (institution inactive): {$task->name}");

            if (! $dryRun) {
                $this->periodicityGapHandler->cancelTask($task);
            }
        }
    }

    /** Per-item lines are for -v; a dry run always shows them, since they are its whole point. */
    private function detail(bool $dryRun, string $line): void
    {
        $this->line('    '.$line, verbosity: $dryRun ? null : 'v');
    }

    /**
     * Repopulate pickup and return tasks for active reservations.
     *
     * @return array{created: int, skipped: int}
     */
    protected function repopulateReservationTasks(bool $dryRun): array
    {
        $created = 0;
        $skipped = 0;

        // Get ReservationResource pivots in Reserved or Lent state grouped by reservation
        $reservationResources = ReservationResource::query()
            ->with(['reservation.users', 'resource'])
            ->whereIn('state', [Reserved::$name, Lent::$name])
            ->get()
            ->groupBy('reservation_id');

        foreach ($reservationResources as $resources) {
            $reservation = $resources->first()?->reservation;

            if (! $reservation) {
                continue;
            }

            $reservedResources = $resources->filter(
                fn ($r) => $r->state === Reserved::$name || $r->state instanceof Reserved
            );

            $lentResources = $resources->filter(
                fn ($r) => $r->state === Lent::$name || $r->state instanceof Lent
            );

            // Handle pickup tasks for Reserved resources
            if ($reservedResources->isNotEmpty()) {
                $result = $this->repopulatePickupTask($reservation, $reservedResources, $dryRun);
                $created += $result['created'];
                $skipped += $result['skipped'];
            }

            // Handle return tasks for Lent resources
            if ($lentResources->isNotEmpty()) {
                $result = $this->repopulateReturnTask($reservation, $lentResources, $dryRun);
                $created += $result['created'];
                $skipped += $result['skipped'];
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * @return array{created: int, skipped: int}
     */
    protected function repopulatePickupTask(Reservation $reservation, $resources, bool $dryRun): array
    {
        // findOrCreate is already idempotent - it returns existing task
        $existingTask = $this->pickupHandler->findExistingTask($reservation);

        if ($existingTask) {
            return ['created' => 0, 'skipped' => 1];
        }

        if ($dryRun) {
            $this->line("    → Would create pickup task for reservation #{$reservation->id}");

            return ['created' => 1, 'skipped' => 0];
        }

        // Get earliest start time from resources
        $dueDate = $resources->min('start_time');

        $this->pickupHandler->findOrCreate(
            name: __('Atsiimti rezervacijos išteklius'),
            model: $reservation,
            users: $reservation->users,
            dueDate: $dueDate?->toDateString(),
        );

        // Update total items to match actual count
        $task = $this->pickupHandler->findExistingTask($reservation);
        if ($task) {
            $metadata = $task->metadata ?? ['items_total' => 0, 'items_completed' => 0];
            $metadata['items_total'] = $resources->count();
            $task->metadata = $metadata;
            $task->save();
        }

        $this->line("    ✓ Created pickup task for reservation #{$reservation->id}");

        return ['created' => 1, 'skipped' => 0];
    }

    /**
     * @return array{created: int, skipped: int}
     */
    protected function repopulateReturnTask(Reservation $reservation, $resources, bool $dryRun): array
    {
        $existingTask = $this->returnHandler->findExistingTask($reservation);

        if ($existingTask) {
            return ['created' => 0, 'skipped' => 1];
        }

        if ($dryRun) {
            $this->line("    → Would create return task for reservation #{$reservation->id}");

            return ['created' => 1, 'skipped' => 0];
        }

        // Get latest end time from resources
        $dueDate = $resources->max('end_time');

        $this->returnHandler->findOrCreate(
            name: __('Grąžinti rezervacijos išteklius'),
            model: $reservation,
            users: $reservation->users,
            dueDate: $dueDate?->toDateString(),
        );

        // Update total items to match actual count
        $task = $this->returnHandler->findExistingTask($reservation);
        if ($task) {
            $metadata = $task->metadata ?? ['items_total' => 0, 'items_completed' => 0];
            $metadata['items_total'] = $resources->count();
            $task->metadata = $metadata;
            $task->save();
        }

        $this->line("    ✓ Created return task for reservation #{$reservation->id}");

        return ['created' => 1, 'skipped' => 0];
    }

    /**
     * Repopulate agenda tasks for meetings.
     *
     * By default only processes future meetings. Use --include-past to process
     * historical meetings as well. Tasks are assigned to representatives who
     * were active at the meeting date.
     *
     * @return array{created: int, skipped: int}
     */
    protected function repopulateMeetingTasks(bool $dryRun): array
    {
        $created = 0;
        $skipped = 0;
        $includePast = $this->option('include-past');

        // Build query - include past meetings if flag is set
        $query = Meeting::query()
            ->with(['institutions.tenant', 'agendaItems']);

        if (! $includePast) {
            $query->where('start_time', '>=', now());
        }

        $meetings = $query->get();

        if ($includePast) {
            $this->info("    Processing {$meetings->count()} meetings (including historical)");
        }

        foreach ($meetings as $meeting) {
            // Administrators nominated for the term the meeting fell in, else the
            // representatives who were active at its start_time.
            $representatives = ResolveTaskAssignees::forMeeting($meeting);

            if ($representatives->isEmpty()) {
                continue;
            }

            // Handle meetings without agenda items - need creation task
            if ($meeting->agendaItems->isEmpty()) {
                $existingTask = $this->agendaCreationHandler->findExistingTask($meeting);

                if ($existingTask) {
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line("    → Would create agenda creation task for meeting #{$meeting->id}");
                    $created++;

                    continue;
                }

                $this->agendaCreationHandler->findOrCreate(
                    name: __('Sukurti posėdžio darbotvarkės klausimus'),
                    meeting: $meeting,
                    users: $representatives,
                    dueDate: $meeting->start_time->addDays(3)->toDateString(),
                );

                $this->line("    ✓ Created agenda creation task for meeting #{$meeting->id}");
                $created++;
            } else {
                // Has agenda items - check if completion task needed
                $existingTask = $this->agendaCompletionHandler->findExistingTask($meeting);

                if ($existingTask) {
                    // Resync so tasks written under older completion rules stop showing a stale percentage.
                    if (! $dryRun) {
                        $this->agendaCompletionHandler->updateProgressForMeeting($meeting);
                    }

                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line("    → Would create agenda completion task for meeting #{$meeting->id}");
                    $created++;

                    continue;
                }

                $this->agendaCompletionHandler->findOrCreate(
                    name: __('Užpildyti darbotvarkės klausimų informaciją'),
                    meeting: $meeting,
                    users: $representatives,
                    dueDate: $meeting->start_time->toDateString(),
                );

                $this->line("    ✓ Created agenda completion task for meeting #{$meeting->id}");
                $created++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
