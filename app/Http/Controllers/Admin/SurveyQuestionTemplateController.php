<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Enums\SurveyQuestionType;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexSurveyQuestionTemplateRequest;
use App\Http\Requests\StoreSurveyQuestionTemplateRequest;
use App\Http\Requests\UpdateSurveyQuestionTemplateRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\SurveyQuestionTemplate;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * CRUD for the reusable survey question bank.
 */
class SurveyQuestionTemplateController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(
        public Authorizer $authorizer,
        private TanstackTableService $tableService,
    ) {}

    public function index(IndexSurveyQuestionTemplateRequest $request): Response
    {
        $this->handleAuthorization('viewAny', SurveyQuestionTemplate::class);

        $query = SurveyQuestionTemplate::query()->with('tenant');

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            ['title'],
            ['applySortBeforePagination' => true],
        );

        $templates = $query->paginate($request->input('per_page', 20))->withQueryString();

        return $this->inertiaResponse('Admin/Surveys/IndexSurveyQuestionTemplate', [
            'templates' => [
                'data' => $templates->getCollection()->map(fn (SurveyQuestionTemplate $template): array => [
                    ...$template->toFullArray(),
                    'tenant' => $template->tenant?->shortname,
                ]),
                'meta' => [
                    'total' => $templates->total(),
                    'per_page' => $templates->perPage(),
                    'current_page' => $templates->currentPage(),
                    'last_page' => $templates->lastPage(),
                    'from' => $templates->firstItem(),
                    'to' => $templates->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
            'questionTypes' => SurveyQuestionType::options(),
        ]);
    }

    public function create(): Response
    {
        $this->handleAuthorization('create', SurveyQuestionTemplate::class);

        return $this->inertiaResponse('Admin/Surveys/CreateSurveyQuestionTemplate', [
            'assignableTenants' => GetTenantsForUpserts::execute('surveys.create.padalinys', $this->authorizer),
            'questionTypes' => SurveyQuestionType::options(),
        ]);
    }

    public function store(StoreSurveyQuestionTemplateRequest $request): RedirectResponse
    {
        SurveyQuestionTemplate::create($request->validated());

        return $this->redirectToIndexWithSuccess('surveyQuestionTemplates', __('surveys.flash.template_created'));
    }

    public function edit(SurveyQuestionTemplate $surveyQuestionTemplate): Response
    {
        $this->handleAuthorization('update', $surveyQuestionTemplate);

        return $this->inertiaResponse('Admin/Surveys/EditSurveyQuestionTemplate', [
            'template' => $surveyQuestionTemplate->toFullArray(),
            'assignableTenants' => GetTenantsForUpserts::execute('surveys.update.padalinys', $this->authorizer),
            'questionTypes' => SurveyQuestionType::options(),
        ]);
    }

    public function update(UpdateSurveyQuestionTemplateRequest $request, SurveyQuestionTemplate $surveyQuestionTemplate): RedirectResponse
    {
        $surveyQuestionTemplate->update($request->validated());

        return $this->redirectToIndexWithSuccess('surveyQuestionTemplates', __('surveys.flash.template_updated'));
    }

    public function destroy(SurveyQuestionTemplate $surveyQuestionTemplate): RedirectResponse
    {
        $this->handleAuthorization('delete', $surveyQuestionTemplate);

        $surveyQuestionTemplate->delete();

        return $this->redirectToIndexWithSuccess('surveyQuestionTemplates', __('surveys.flash.template_deleted'));
    }
}
