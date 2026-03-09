<?php

namespace App\Console\Commands;

use App\Actions\GetInstitutionRepresentatives;
use App\Listeners\QueueNotificationForDigest;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\States\ReservationResource\Lent;
use App\States\ReservationResource\Reserved;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use App\Tasks\Handlers\AgendaCreationTaskHandler;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use App\Tasks\Handlers\PickupTaskHandler;
use App\Tasks\Handlers\ReturnTaskHandler;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Repopulate autotasks for specified model type or all model types.
 *
 * This command is idempotent - it will skip tasks that already exist
 * and only create missing tasks based on current system state.
 *
 * Friendly aliases for models:
 * - institution: PeriodicityGap tasks
 * - reservation: Pickup and Return tasks
 * - meeting: AgendaCreation and AgendaCompletion tasks
 */
class RepopulateTasks extends Command
{
    protected $signature = 'tasks:repopulate
                            {model? : Model type to repopulate (institution, reservation, meeting)}
                            {--force : Force repopulation without confirmation (required for production)}
                            {--dry-run : Show what would be done without creating tasks}
                            {--include-past : Include past meetings (useful for historical data entry)}';

    protected $description = 'Repopulate autotasks for a model type or all types. Idempotent - skips existing tasks. Use --include-past for historical meetings.';

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
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $model = $this->argument('model');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('DRY RUN - No tasks will be created');
        }

        // Validate model argument if provided
        if ($model && ! isset($this->modelHandlers[$model])) {
            $this->error("Unknown model type: {$model}");
            $this->line('Available types: '.implode(', ', array_keys($this->modelHandlers)));

            return self::FAILURE;
        }

        // If no model specified, ask for confirmation (unless --force)
        if (! $model) {
            if (! $force && ! $this->confirm('Repopulate tasks for all model types?')) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $totalCreated = 0;
        $totalSkipped = 0;

        // Skip digest queuing during bulk repopulation
        // Notifications will still be sent but won't be batched into digest emails
        QueueNotificationForDigest::$skipDigest = true;

        try {
            // Process specified model or all models
            $modelsToProcess = $model ? [$model] : array_keys($this->modelHandlers);

            foreach ($modelsToProcess as $modelType) {
                $this->info("Processing {$modelType} tasks...");

                $method = $this->modelHandlers[$modelType];
                $result = $this->{$method}($dryRun);

                $totalCreated += $result['created'];
                $totalSkipped += $result['skipped'];

                $this->line("  Created: {$result['created']}, Skipped: {$result['skipped']}");
            }
        } finally {
            // Always reset the flag
            QueueNotificationForDigest::$skipDigest = false;
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Tasks created', $totalCreated],
                ['Tasks skipped (existing)', $totalSkipped],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Repopulate periodicity gap tasks for institutions.
     *
     * @return array{created: int, skipped: int}
     */
    protected function repopulateInstitutionTasks(bool $dryRun): array
    {
        $created = 0;
        $skipped = 0;
        $warningDays = 7;

        $institutions = Institution::query()
            ->where('is_active', true)
            ->with(['types', 'meetings' => function ($query) {
                $query->where('start_time', '>=', now()->subYear())
                    ->orWhere('start_time', '>=', now())
                    ->orderBy('start_time', 'desc');
            }])
            ->get();

        foreach ($institutions as $institution) {
            $periodicityDays = $institution->meeting_periodicity_days;

            // Skip if no periodicity is set
            if (! $periodicityDays) {
                continue;
            }

            // Check if task already exists
            if ($this->periodicityGapHandler->hasExistingTask($institution)) {
                $skipped++;

                continue;
            }

            // Calculate days since last meeting
            $lastMeetingDate = $this->getLastMeetingDate($institution);
            $daysSinceLastMeeting = $lastMeetingDate
                ? (int) $lastMeetingDate->diffInDays(Carbon::today())
                : null;

            // Check if there's a future meeting scheduled
            $hasFutureMeeting = $institution->meetings
                ->filter(fn ($meeting) => $meeting->start_time->isFuture())
                ->isNotEmpty();

            if ($hasFutureMeeting) {
                continue;
            }

            // Check if we're approaching the threshold
            $daysUntilThreshold = $periodicityDays - ($daysSinceLastMeeting ?? $periodicityDays);

            if ($daysUntilThreshold > $warningDays) {
                continue;
            }

            // Get current representatives
            $representatives = GetInstitutionRepresentatives::execute($institution);

            if ($representatives->isEmpty()) {
                continue;
            }

            // Calculate due date - at least 7 days from now to give time to act
            $minDueDays = 7;
            $dueDate = Carbon::today()->addDays(max($minDueDays, $daysUntilThreshold));

            if ($dryRun) {
                $this->line("    → Would create: {$institution->name}");
                $created++;

                continue;
            }

            $this->periodicityGapHandler->findOrCreate(
                institution: $institution,
                users: $representatives,
                dueDate: $dueDate,
            );

            $this->line("    ✓ Created: {$institution->name}");
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
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

        foreach ($reservationResources as $reservationId => $resources) {
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
            // getRepresentativesActiveAt() uses the meeting's start_time
            // to find users who were representatives at that date
            $representatives = $meeting->getRepresentativesActiveAt();

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

    /**
     * Get the date of the last meeting for an institution.
     */
    protected function getLastMeetingDate(Institution $institution): ?Carbon
    {
        $lastMeeting = $institution->meetings
            ->filter(fn ($meeting) => $meeting->start_time->isPast())
            ->sortByDesc('start_time')
            ->first();

        return $lastMeeting?->start_time;
    }
}
