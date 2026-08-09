<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyStatus;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexSurveyRequest;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\SyncSurveyQuestionsRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Jobs\PublishSurveyToLimeSurveyJob;
use App\Jobs\SyncSurveyStatsJob;
use App\Models\Survey;
use App\Models\SurveyQuestionTemplate;
use App\Services\ApprovalService;
use App\Services\LimeSurveyClient;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

class SurveyController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(
        public Authorizer $authorizer,
        private TanstackTableService $tableService,
        private ApprovalService $approvalService,
    ) {}

    public function index(IndexSurveyRequest $request): Response
    {
        $this->handleAuthorization('viewAny', Survey::class);

        $query = Survey::query()->with('tenant');

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            ['name'],
            ['applySortBeforePagination' => true],
        );

        $deletedCount = $this->getTrashedCount($query);

        $surveys = $query->paginate($request->input('per_page', 15))->withQueryString();

        return $this->inertiaResponse('Admin/Surveys/IndexSurvey', [
            'surveys' => [
                'data' => $surveys->getCollection()->map(fn (Survey $survey): array => [
                    ...$survey->toFullArray(),
                    'tenant' => $survey->tenant?->shortname,
                    'question_count' => $survey->questions()->count(),
                ]),
                'meta' => [
                    'total' => $surveys->total(),
                    'per_page' => $surveys->perPage(),
                    'current_page' => $surveys->currentPage(),
                    'last_page' => $surveys->lastPage(),
                    'from' => $surveys->firstItem(),
                    'to' => $surveys->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
            'showDeleted' => $request->boolean('showDeleted', false),
            'deletedCount' => $deletedCount,
            'statusOptions' => SurveyStatus::options(),
        ]);
    }

    public function create(): Response
    {
        $this->handleAuthorization('create', Survey::class);

        return $this->inertiaResponse('Admin/Surveys/CreateSurvey', [
            'assignableTenants' => GetTenantsForUpserts::execute('surveys.create.padalinys', $this->authorizer),
        ]);
    }

    public function store(StoreSurveyRequest $request): RedirectResponse
    {
        $survey = new Survey($request->validated());
        $survey->status = SurveyStatus::Draft;
        $survey->save();

        return $this->redirectWithSuccess('surveys.show', __('surveys.flash.created'), $survey->id);
    }

    public function show(Survey $survey, LimeSurveyClient $client): Response
    {
        $this->handleAuthorization('view', $survey);

        $survey->load(['tenant', 'questions', 'approvals.user']);

        return $this->inertiaResponse('Admin/Surveys/ShowSurvey', [
            'survey' => [
                ...$survey->toFullArray(),
                'tenant' => $survey->tenant?->toArray(),
                'questions' => $survey->questions->map->toFullArray(),
                'is_editable' => $survey->isEditable(),
                'is_published' => $survey->isPublished(),
                'approvals' => $survey->approvals->map(fn ($approval): array => [
                    ...$approval->toArray(),
                    'user' => $approval->user?->only(['id', 'name', 'profile_photo_path']),
                ]),
                'can_approve' => auth()->user() !== null && $survey->canBeApprovedBy(auth()->user()),
            ],
            'questionTypes' => SurveyQuestionType::options(),
            'questionTemplates' => SurveyQuestionTemplate::query()
                ->availableTo($survey->tenant_id)
                ->orderBy('order')
                ->get()
                ->map->toFullArray(),
            'limeSurveyConfigured' => $client->isConfigured(),
        ]);
    }

    public function edit(Survey $survey): Response
    {
        $this->handleAuthorization('update', $survey);

        return $this->inertiaResponse('Admin/Surveys/EditSurvey', [
            'survey' => $survey->toFullArray(),
            'assignableTenants' => GetTenantsForUpserts::execute('surveys.update.padalinys', $this->authorizer),
        ]);
    }

    public function update(UpdateSurveyRequest $request, Survey $survey): RedirectResponse
    {
        $survey->update($request->validated());

        return $this->redirectWithSuccess('surveys.show', __('surveys.flash.updated'), $survey->id);
    }

    public function destroy(Survey $survey): RedirectResponse
    {
        $this->handleAuthorization('delete', $survey);

        $survey->delete();

        return $this->redirectToIndexWithSuccess('surveys', __('surveys.flash.deleted'));
    }

    /**
     * Replace the survey's question list.
     *
     * The whole list arrives at once, so this deletes and rewrites rather than diffing —
     * simpler, and safe because a published survey can never reach here (the policy stops
     * it) and therefore no LimeSurvey state depends on the old rows.
     */
    public function syncQuestions(SyncSurveyQuestionsRequest $request, Survey $survey): RedirectResponse
    {
        DB::transaction(function () use ($request, $survey): void {
            $survey->questions()->delete();

            foreach ($request->validated('questions') as $order => $question) {
                $type = SurveyQuestionType::from($question['type']);

                $survey->questions()->create([
                    'survey_question_template_id' => $question['survey_question_template_id'] ?? null,
                    'group_name' => $question['group_name'],
                    'title' => $question['title'],
                    'type' => $type,
                    'question' => $question['question'],
                    'help' => $question['help'] ?? null,
                    // Drop options that the type cannot use, so the .lss never carries
                    // answer rows for a free-text question.
                    'options' => $type->hasOptions() ? ($question['options'] ?? []) : null,
                    'is_required' => $question['is_required'] ?? false,
                    'order' => $order,
                ]);
            }
        });

        return $this->redirectBackWithSuccess(__('surveys.flash.questions_saved'));
    }

    /**
     * Submit the survey for approval.
     */
    public function requestApproval(Survey $survey): RedirectResponse
    {
        $this->handleAuthorization('requestApproval', $survey);

        if ($survey->questions()->count() === 0) {
            return $this->redirectBackWithError(__('surveys.flash.no_questions'));
        }

        if ($survey->getApprovalFlow() === null) {
            return $this->redirectBackWithError(__('surveys.flash.no_flow'));
        }

        $survey->forceFill(['status' => SurveyStatus::PendingApproval])->save();

        $this->approvalService->requestApproval($survey);

        return $this->redirectBackWithSuccess(__('surveys.flash.approval_requested'));
    }

    /**
     * Retry a failed publish, or refresh the statistics of a live survey.
     */
    public function resync(Survey $survey): RedirectResponse
    {
        $this->handleAuthorization('resync', $survey);

        if ($survey->isPublished()) {
            SyncSurveyStatsJob::dispatch($survey);

            return $this->redirectBackWithInfo(__('surveys.flash.stats_queued'));
        }

        if ($survey->status !== SurveyStatus::Approved) {
            return $this->redirectBackWithError(__('surveys.flash.not_approved'));
        }

        PublishSurveyToLimeSurveyJob::dispatch($survey);

        return $this->redirectBackWithInfo(__('surveys.flash.publish_queued'));
    }
}
