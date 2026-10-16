<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Actions\ImportMailingLists;
use App\Actions\SyncMailingList;
use App\Enums\MailingListMode;
use App\Enums\MailingListSourceType;
use App\Enums\MailingListSyncStatus;
use App\Http\Controllers\AdminController;
use App\Http\Requests\MailingLists\ShowMailingListRequest;
use App\Http\Requests\MailingLists\StoreMailingListRequest;
use App\Http\Requests\MailingLists\SyncMailingListRequest;
use App\Http\Requests\MailingLists\UpdateMailingListModeRequest;
use App\Http\Requests\MailingLists\UpdateMailingListRequest;
use App\Jobs\SyncManagedMailingLists;
use App\Listeners\QueueMailingListSync;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\MailingList;
use App\Models\MailingListMessage;
use App\Models\MailingListSource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Mailman\MailmanClient;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MailingListController extends AdminController
{
    /** Defaults for a list created here, matching the existing konferencijos. */
    private const array NEW_LIST_CONFIG = [
        'archive_policy' => 'private',
        'dmarc_mitigate_action' => 'munge_from',
        'default_member_action' => 'defer',
        'default_nonmember_action' => 'hold',
        'send_welcome_message' => false,
        'advertised' => true,
    ];

    public function __construct(
        public Authorizer $authorizer,
        private readonly MailmanClient $mailman,
    ) {}

    /**
     * Lists number in the dozens, so the whole set is sent and filtered in the browser.
     */
    public function index(Request $request): Response
    {
        $this->handleAuthorization('viewAny', MailingList::class);

        return $this->renderIndex($request, openCreate: false);
    }

    /** The + Sukurti entry point: the collection with its create sheet open. */
    public function create(Request $request): Response
    {
        $this->handleAuthorization('create', MailingList::class);

        return $this->renderIndex($request, openCreate: true);
    }

    private function renderIndex(Request $request, bool $openCreate): Response
    {

        $user = $request->user();
        $scope = $this->authorizer->scope($user, 'mailingLists.read.padalinys');

        $lists = MailingList::query()
            ->when(! $scope->isAllScope, fn ($query) => $query->whereIn('tenant_id', $scope->tenants->pluck('id')))
            ->with('tenant:id,shortname')
            ->withCount('sources')
            ->orderBy('address')
            ->get()
            ->map(fn (MailingList $list): array => [
                ...$this->summary($list),
                'sources_count' => $list->sources_count,
            ]);

        return $this->inertiaResponse('Admin/MailingLists/IndexMailingList', [
            'mailingLists' => $lists,
            'canCreate' => $user->can('create', MailingList::class),
            'canImport' => $this->authorizer->scope($user, 'mailingLists.create.padalinys')->isAllScope,
            'mailmanConfigured' => $this->mailman->isConfigured(),
            'tenants' => GetTenantsForUpserts::execute('mailingLists.create.padalinys', $this->authorizer),
            'domain' => $this->mailman->domain(),
            'openCreate' => $openCreate,
        ]);
    }

    public function store(StoreMailingListRequest $request): RedirectResponse
    {
        if (! $this->mailman->isConfigured()) {
            return back()->with('error', __('mailing_lists.messages.not_configured'));
        }

        $address = $request->address();
        try {
            $mailingList = Cache::lock('mailing-list:create:'.$address, 90)->block(2, function () use ($request, $address): MailingList {
                $listId = $this->mailman->createList($address, [
                    ...self::NEW_LIST_CONFIG,
                    'display_name' => $request->validated('display_name'),
                    'description' => (string) $request->validated('description'),
                ]);

                try {
                    return MailingList::create([
                        ...$request->safe()->only(['display_name', 'description', 'tenant_id']),
                        'address' => $address,
                        'mailman_list_id' => $listId,
                        'mode' => MailingListMode::Managed,
                        'archive_policy' => self::NEW_LIST_CONFIG['archive_policy'],
                    ]);
                } catch (Throwable $exception) {
                    try {
                        $this->mailman->deleteList($listId);
                    } catch (Throwable $cleanupException) {
                        report($cleanupException);
                    }
                    throw $exception;
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', __('mailing_lists.messages.create_failed'));
        }

        return redirect()->route('mailingLists.show', $mailingList)->with('success', $this->entityMessage('created', 'mailingList'));
    }

    public function show(ShowMailingListRequest $request, MailingList $mailingList): Response
    {
        $this->handleAuthorization('viewArchive', $mailingList);

        $user = $request->user();
        $canView = $user->can('view', $mailingList);
        $canUpdate = $user->can('update', $mailingList);
        $mailingList->load($canView ? ['tenant:id,shortname', 'sources', 'exclusions'] : ['tenant:id,shortname']);

        return $this->inertiaResponse('Admin/MailingLists/ShowMailingList', [
            'mailingList' => [
                ...$this->summary($mailingList),
                'last_sync_error' => $mailingList->last_sync_status === MailingListSyncStatus::Failed ? __('mailing_lists.messages.sync_failed') : null,
                'archive_fetched_at' => $mailingList->archive_fetched_at?->toIso8601String(),
                'archive_complete' => $this->isArchiveComplete($mailingList),
                'archive_imported' => $mailingList->messages()->count(),
                'archive_total' => $mailingList->archive_backfill_total,
            ],
            'sources' => $canView ? $this->presentSources($mailingList->sources) : [],
            'exclusions' => $canView ? $mailingList->exclusions->map->only(['id', 'email', 'note'])->values() : [],
            'roster' => Inertia::defer(fn (): Collection => $canView
                ? $mailingList->members()->get()->map->only(['email', 'display_name', 'reasons'])->values()
                : collect(), 'roster'),
            'diff' => Inertia::defer(fn (): ?array => $canUpdate ? $this->diffFor($mailingList, $user) : null, 'roster'),
            'messages' => Inertia::scroll(fn () => $this->messagesFor($mailingList, $request->validated('messages_page', 1)))->defer('archive'),
            'sourceOptions' => Inertia::optional(fn (): ?array => $canUpdate ? $this->sourceOptions($mailingList) : null),
            'mailmanConfigured' => $this->mailman->isConfigured(),
            'tenants' => GetTenantsForUpserts::execute('mailingLists.update.padalinys', $this->authorizer),
            'can' => [
                'view' => $canView,
                'update' => $canUpdate,
            ],
        ]);
    }

    public function update(UpdateMailingListRequest $request, MailingList $mailingList): RedirectResponse
    {
        try {
            $mailingList->withSyncLock(function () use ($request, $mailingList): void {
                if ($this->mailman->isConfigured()) {
                    $this->mailman->updateConfig($mailingList->mailmanListId(), [
                        'display_name' => $request->validated('display_name'),
                        'description' => (string) $request->validated('description'),
                    ]);
                }

                $mailingList->update($request->safe()->only(['display_name', 'description', 'tenant_id']));
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', __('mailing_lists.messages.update_failed'));
        }

        QueueMailingListSync::queueSync();

        return back()->with('success', $this->entityMessage('updated', 'mailingList'));
    }

    public function updateMode(UpdateMailingListModeRequest $request, MailingList $mailingList, SyncMailingList $sync): RedirectResponse
    {
        $mode = MailingListMode::from($request->validated('mode'));

        if ($mode === MailingListMode::Managed) {
            return $this->runSync($mailingList, $sync, $request->validated('preview_token'), $request->user(), activate: true);
        }

        try {
            $mailingList->withSyncLock(fn () => $mailingList->update([
                'mode' => $mode, 'last_sync_status' => null, 'last_sync_error' => null,
            ]));
        } catch (LockTimeoutException) {
            return back()->with('error', __('mailing_lists.messages.update_failed'));
        }

        QueueMailingListSync::queueSync();

        return back()->with('success', __('mailing_lists.messages.unmanaged'));
    }

    public function sync(SyncMailingListRequest $request, MailingList $mailingList, SyncMailingList $sync): RedirectResponse
    {
        return $this->runSync($mailingList, $sync, $request->validated('preview_token'), $request->user());
    }

    public function import(Request $request, ImportMailingLists $import): RedirectResponse
    {
        $this->handleAuthorization('create', MailingList::class);
        abort_unless($this->authorizer->scope($request->user(), 'mailingLists.create.padalinys')->isAllScope, 403);

        if (! $this->mailman->isConfigured()) {
            return back()->with('error', __('mailing_lists.messages.not_configured'));
        }

        try {
            $result = $import->execute();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', __('mailing_lists.messages.import_failed'));
        }

        return back()->with('success', __('mailing_lists.messages.imported', $result));
    }

    private function runSync(MailingList $mailingList, SyncMailingList $sync, ?string $previewToken, ?User $user, bool $activate = false): RedirectResponse
    {
        if (! $this->mailman->isConfigured()) {
            return back()->with('error', __('mailing_lists.messages.not_configured'));
        }

        try {
            $diff = $sync->verify($mailingList, $previewToken, $user, $activate);
        } catch (ValidationException $exception) {
            return back()->with('error', __('mailing_lists.messages.stale_preview'));
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', __('mailing_lists.messages.sync_failed'));
        }

        if ($diff->needsReview() && $previewToken === null) {
            return back()->with('error', __('mailing_lists.messages.needs_review'));
        }

        SyncManagedMailingLists::dispatch($mailingList->id, $previewToken, $user?->id, $activate)->afterCommit();

        return back()->with('success', __('mailing_lists.messages.sync_queued'));
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(MailingList $list): array
    {
        return [
            'id' => $list->id,
            'address' => $list->address,
            'display_name' => $list->display_name,
            'description' => $list->description,
            'mode' => $list->mode->value,
            'tenant' => $list->tenant->only(['id', 'shortname']),
            'archive_policy' => $list->archive_policy,
            'member_count' => $list->member_count,
            'posts_last_year' => $this->isArchiveComplete($list) ? $list->posts_last_year : null,
            'last_post_at' => $list->last_post_at?->toIso8601String(),
            'last_synced_at' => $list->last_synced_at?->toIso8601String(),
            'last_sync_status' => $list->last_sync_status?->value,
        ];
    }

    private function isArchiveComplete(MailingList $list): bool
    {
        return $list->archive_backfilled_at !== null
            || ($list->archive_fetched_at !== null && $list->archive_head_hash === null);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function diffFor(MailingList $mailingList, User $user): ?array
    {
        if (! $this->mailman->isConfigured()) {
            return null;
        }

        try {
            return $mailingList->withSyncLock(function () use ($mailingList, $user): array {
                $mailingList->refresh();
                $sync = app(SyncMailingList::class);
                $diff = $sync->preview($mailingList);

                return [...$diff->toArray(), 'preview_token' => $sync->reviewToken($mailingList, $diff, $user)];
            });
        } catch (Throwable) {
            return ['error' => __('mailing_lists.messages.preview_failed')];
        }
    }

    private function messagesFor(MailingList $mailingList, int $page): LengthAwarePaginator
    {
        return $mailingList->messages()->paginate(50, ['*'], 'messages_page', $page)->through(fn (MailingListMessage $message): array => [
            'id' => $message->id,
            'subject' => $message->subject,
            'sender_name' => $message->sender_name,
            'sender_email' => $message->sender_email,
            'sent_at' => $message->sent_at?->toIso8601String(),
            'body_text' => $message->body_text,
            'attachments' => $message->attachments ?? [],
            'url' => $message->url,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, MailingListSource>  $sources
     * @return list<array<string, mixed>>
     */
    private function presentSources(\Illuminate\Database\Eloquent\Collection $sources): array
    {
        $ids = fn (MailingListSourceType $type) => $sources->where('type', $type)->pluck('source_id')->filter()->all();
        $filterIds = fn (string $key) => $sources->flatMap(fn (MailingListSource $source) => (array) $source->filter($key, []))->unique()->all();

        $duties = Duty::query()->with('institution:id,name')->whereIn('id', $ids(MailingListSourceType::Duty))->get()->keyBy('id');
        $institutions = Institution::query()->whereIn('id', $ids(MailingListSourceType::Institution))->get(['id', 'name'])->keyBy('id');
        $lists = MailingList::query()->whereIn('id', $ids(MailingListSourceType::MailingList))->get(['id', 'address'])->keyBy('id');
        $institutionTypes = InstitutionType::query()->whereIn('id', [...$ids(MailingListSourceType::InstitutionType), ...$filterIds('exclude_institution_type_ids')])->get(['id', 'title'])->keyBy('id');
        $dutyTypes = DutyType::query()->whereIn('id', [...$ids(MailingListSourceType::DutyType), ...$filterIds('duty_type_ids')])->get(['id', 'title'])->keyBy('id');
        $tenants = Tenant::query()->whereIn('id', $filterIds('tenant_ids'))->pluck('shortname', 'id');

        return $sources->map(function (MailingListSource $source) use ($duties, $institutions, $lists, $institutionTypes, $dutyTypes, $tenants): array {
            $label = match ($source->type) {
                MailingListSourceType::Duty => ($duty = $duties->get($source->source_id))
                    ? trim(($duty->institution?->name ? $duty->institution->name.' · ' : '').$duty->name)
                    : null,
                MailingListSourceType::Institution => $institutions->get($source->source_id)?->name,
                MailingListSourceType::InstitutionType => $institutionTypes->get((int) $source->source_id)?->title,
                MailingListSourceType::DutyType => $dutyTypes->get((int) $source->source_id)?->title,
                MailingListSourceType::MailingList => $lists->get($source->source_id)?->address,
                MailingListSourceType::Email => $source->display_name ? "{$source->display_name} <{$source->email}>" : $source->email,
            };

            return [
                'id' => $source->id,
                'type' => $source->type->value,
                'label' => $label ?? __('mailing_lists.sources.missing'),
                'address_mode' => $source->type->usesAddressMode() ? $source->address_mode->value : null,
                'tenant_scope' => $source->filter('tenant_scope', 'all'),
                'tenants' => collect((array) $source->filter('tenant_ids', []))->map(fn ($id) => $tenants->get($id))->filter()->values(),
                'include_subtypes' => (bool) $source->filter('include_subtypes', true),
                'excluded_types' => collect((array) $source->filter('exclude_institution_type_ids', []))->map(fn ($id) => $institutionTypes->get((int) $id)?->title)->filter()->values(),
                'duty_types' => collect((array) $source->filter('duty_type_ids', []))->map(fn ($id) => $dutyTypes->get((int) $id)?->title)->filter()->values(),
                'note' => $source->note,
                'expires_on' => $source->expires_on?->toDateString(),
                'is_expired' => $source->isExpired(),
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceOptions(MailingList $mailingList): array
    {
        $scope = $this->authorizer->scope(request()->user(), 'mailingLists.update.padalinys');
        $tenantIds = $scope->tenantIds()->all();

        return [
            'globalScope' => $scope->isAllScope,
            'tenants' => Tenant::query()->whereIn('id', $tenantIds)->orderBy('shortname')->get(['id', 'shortname', 'type'])
                ->map(fn (Tenant $tenant) => ['id' => $tenant->id, 'shortname' => $tenant->shortname, 'type' => $tenant->type?->value]),
            'institutionTypes' => InstitutionType::query()->orderBy('id')->get(['id', 'title', 'parent_id'])
                ->map(fn (InstitutionType $type) => ['id' => $type->id, 'title' => $type->title, 'parent_id' => $type->parent_id]),
            'dutyTypes' => DutyType::query()->orderBy('id')->get(['id', 'title', 'parent_id'])
                ->map(fn (DutyType $type) => ['id' => $type->id, 'title' => $type->title, 'parent_id' => $type->parent_id]),
            'institutions' => Institution::query()->whereIn('tenant_id', $tenantIds)->with('tenant:id,shortname')->where('is_active', true)->orderBy('tenant_id')->get(['id', 'name', 'tenant_id'])
                ->map(fn (Institution $institution) => ['id' => $institution->id, 'name' => trim(($institution->tenant?->shortname ? $institution->tenant->shortname.' · ' : '').$institution->name)]),
            'mailingLists' => MailingList::query()->whereIn('tenant_id', $tenantIds)->whereKeyNot($mailingList->id)->orderBy('address')->get(['id', 'address'])
                ->map->only(['id', 'address']),
        ];
    }
}
