<?php

use App\Enums\SurveyQuestionType;
use App\Enums\SurveyStatus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

/**
 * A user who may manage surveys for one tenant.
 *
 * No seeded role carries the survey permissions yet, so the smallest role that covers the
 * case has to be built here rather than reused.
 */
function makeSurveyManager(Tenant $tenant, string $scope = 'padalinys'): User
{
    $user = makeUser($tenant);

    $role = Role::firstOrCreate(['name' => "Test Survey Manager {$scope}", 'guard_name' => 'web']);
    $role->syncPermissions(
        Permission::query()->whereIn('name', [
            "surveys.create.{$scope}",
            "surveys.read.{$scope}",
            "surveys.update.{$scope}",
            "surveys.delete.{$scope}",
        ])->get()
    );

    $user->duties()->first()->assignRole($role);

    return $user;
}

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeSurveyManager($this->tenant);

    $this->survey = Survey::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('unauthorized access', function (): void {
    test('cannot access index page', function (): void {
        asUser($this->user)->get(route('surveys.index'))->assertStatus(403);
    });

    test('cannot access create page', function (): void {
        asUser($this->user)->get(route('surveys.create'))->assertStatus(403);
    });

    test('cannot store a survey', function (): void {
        asUser($this->user)->post(route('surveys.store'), [
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Apklausa', 'en' => 'Survey'],
        ])->assertStatus(403);

        expect(Survey::count())->toBe(1);
    });

    test('cannot view a survey', function (): void {
        asUser($this->user)->get(route('surveys.show', $this->survey))->assertStatus(403);
    });

    test('cannot update a survey', function (): void {
        asUser($this->user)->patch(route('surveys.update', $this->survey), [
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Kita', 'en' => 'Other'],
        ])->assertStatus(403);
    });

    test('cannot delete a survey', function (): void {
        asUser($this->user)->delete(route('surveys.destroy', $this->survey))->assertStatus(403);

        expect(Survey::count())->toBe(1);
    });

    test('cannot sync questions', function (): void {
        asUser($this->user)->put(route('surveys.syncQuestions', $this->survey), ['questions' => []])
            ->assertStatus(403);
    });

    test('cannot request approval', function (): void {
        asUser($this->user)->post(route('surveys.requestApproval', $this->survey))->assertStatus(403);
    });
});

describe('authorized access', function (): void {
    test('can access index page', function (): void {
        asUser($this->admin)->get(route('surveys.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Surveys/IndexSurvey')
                ->has('surveys')
                ->has('surveys.data')
                ->has('statusOptions')
            );
    });

    test('can access create page', function (): void {
        asUser($this->admin)->get(route('surveys.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Surveys/CreateSurvey'));
    });

    test('can store a survey as a draft', function (): void {
        asUser($this->admin)->post(route('surveys.store'), [
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Studijų kokybė', 'en' => 'Study quality'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'is_anonymous' => true,
        ])->assertSessionHasNoErrors()->assertRedirect();

        // Not latest('created_at') — the fixture survey shares the same timestamp.
        $survey = Survey::query()->where('name->lt', 'Studijų kokybė')->sole();

        expect($survey->status)->toBe(SurveyStatus::Draft);
        expect($survey->getTranslation('name', 'en'))->toBe('Study quality');
    });

    test('rejects an end date before the start date', function (): void {
        asUser($this->admin)->post(route('surveys.store'), [
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Apklausa'],
            'starts_at' => now()->addWeek()->toDateTimeString(),
            'ends_at' => now()->toDateTimeString(),
        ])->assertSessionHasErrors('ends_at');
    });

    test('can view a survey with its questions and templates', function (): void {
        SurveyQuestion::factory()->create(['survey_id' => $this->survey->id, 'title' => 'Q1']);

        asUser($this->admin)->get(route('surveys.show', $this->survey))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Surveys/ShowSurvey')
                ->has('survey.questions', 1)
                ->has('questionTypes')
                ->has('questionTemplates')
                ->where('survey.is_editable', true)
            );
    });

    test('can delete a draft survey', function (): void {
        asUser($this->admin)->delete(route('surveys.destroy', $this->survey))->assertRedirect();

        expect(Survey::count())->toBe(0);
        expect(Survey::withTrashed()->count())->toBe(1);
    });
});

describe('question syncing', function (): void {
    test('replaces the whole question list in order', function (): void {
        SurveyQuestion::factory()->create(['survey_id' => $this->survey->id, 'title' => 'OLD']);

        asUser($this->admin)->put(route('surveys.syncQuestions', $this->survey), [
            'questions' => [
                [
                    'title' => 'Q1',
                    'type' => SurveyQuestionType::FivePoint->value,
                    'group_name' => ['lt' => 'Bendra', 'en' => 'General'],
                    'question' => ['lt' => 'Kaip sekasi?', 'en' => 'How are you?'],
                    'is_required' => true,
                ],
                [
                    'title' => 'Q2',
                    'type' => SurveyQuestionType::List->value,
                    'group_name' => ['lt' => 'Bendra', 'en' => 'General'],
                    'question' => ['lt' => 'Kursas?', 'en' => 'Year?'],
                    'is_required' => false,
                    'options' => [
                        ['code' => 'K1', 'label' => ['lt' => 'Pirmas', 'en' => 'First']],
                    ],
                ],
            ],
        ])->assertRedirect();

        $questions = $this->survey->fresh('questions')->questions;

        expect($questions)->toHaveCount(2);
        expect($questions->pluck('title')->all())->toBe(['Q1', 'Q2']);
        expect($questions->pluck('order')->all())->toBe([0, 1]);
    });

    test('drops options that the question type cannot use', function (): void {
        asUser($this->admin)->put(route('surveys.syncQuestions', $this->survey), [
            'questions' => [[
                'title' => 'Q1',
                'type' => SurveyQuestionType::LongText->value,
                'group_name' => ['lt' => 'Bendra'],
                'question' => ['lt' => 'Komentaras'],
                'options' => [['code' => 'X', 'label' => ['lt' => 'Nereikalingas']]],
            ]],
        ])->assertRedirect();

        expect($this->survey->fresh('questions')->questions->first()->options)->toBeNull();
    });

    test('rejects duplicate question codes', function (): void {
        $payload = [
            'title' => 'Q1',
            'type' => SurveyQuestionType::LongText->value,
            'group_name' => ['lt' => 'Bendra'],
            'question' => ['lt' => 'Klausimas'],
        ];

        asUser($this->admin)->put(route('surveys.syncQuestions', $this->survey), [
            'questions' => [$payload, $payload],
        ])->assertSessionHasErrors('questions');

        expect($this->survey->fresh('questions')->questions)->toHaveCount(0);
    });

    test('requires options for choice questions', function (): void {
        asUser($this->admin)->put(route('surveys.syncQuestions', $this->survey), [
            'questions' => [[
                'title' => 'Q1',
                'type' => SurveyQuestionType::List->value,
                'group_name' => ['lt' => 'Bendra'],
                'question' => ['lt' => 'Klausimas'],
                'options' => [],
            ]],
        ])->assertSessionHasErrors('questions.0.options');
    });

    test('rejects a question code LimeSurvey would not accept as a column name', function (): void {
        asUser($this->admin)->put(route('surveys.syncQuestions', $this->survey), [
            'questions' => [[
                'title' => '1 bad-code',
                'type' => SurveyQuestionType::LongText->value,
                'group_name' => ['lt' => 'Bendra'],
                'question' => ['lt' => 'Klausimas'],
            ]],
        ])->assertSessionHasErrors('questions.0.title');
    });
});

describe('published surveys are frozen', function (): void {
    beforeEach(function (): void {
        $this->published = Survey::factory()->published()->create(['tenant_id' => $this->tenant->id]);
    });

    test('cannot be edited', function (): void {
        asUser($this->admin)->get(route('surveys.edit', $this->published))->assertStatus(403);
    });

    test('cannot be updated', function (): void {
        asUser($this->admin)->patch(route('surveys.update', $this->published), [
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Naujas'],
        ])->assertStatus(403);
    });

    test('cannot have their questions replaced', function (): void {
        asUser($this->admin)->put(route('surveys.syncQuestions', $this->published), ['questions' => []])
            ->assertStatus(403);
    });

    test('cannot be deleted', function (): void {
        asUser($this->admin)->delete(route('surveys.destroy', $this->published))->assertStatus(403);
    });

    test('are reported as not editable to the frontend', function (): void {
        asUser($this->admin)->get(route('surveys.show', $this->published))
            ->assertInertia(fn (Assert $page) => $page
                ->where('survey.is_editable', false)
                ->where('survey.is_published', true)
            );
    });
});

describe('tenant isolation', function (): void {
    test('cannot view a survey from another tenant', function (): void {
        $otherTenant = Tenant::factory()->create();
        $otherSurvey = Survey::factory()->create(['tenant_id' => $otherTenant->id]);

        asUser($this->admin)->get(route('surveys.show', $otherSurvey))->assertStatus(403);
    });

    test('cannot update a survey from another tenant', function (): void {
        $otherTenant = Tenant::factory()->create();
        $otherSurvey = Survey::factory()->create(['tenant_id' => $otherTenant->id]);

        asUser($this->admin)->patch(route('surveys.update', $otherSurvey), [
            'tenant_id' => $otherTenant->id,
            'name' => ['lt' => 'Kita'],
        ])->assertStatus(403);
    });
});
