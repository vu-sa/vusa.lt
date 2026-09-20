<?php

use App\Models\Institution;
use App\Models\Task;
use App\Models\User;
use App\Notifications\InstitutionActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->create();
    $this->recipient = User::factory()->create();
});

function periodicityGapNotification(Institution $institution, array $metadata = []): InstitutionActivityNotification
{
    return new InstitutionActivityNotification(Task::factory()->create(['metadata' => $metadata]), $institution);
}

describe('the periodicity-gap reminder answers itself (U21)', function (): void {
    test('yes records a meeting and no files a check-in, both on Pradžia with the institution filled in', function (): void {
        $notification = periodicityGapNotification($this->institution);

        expect($notification->primaryAction())->toBe([
            'label' => __('notifications.action_register_meeting'),
            'url' => route('dashboard', ['window' => 'meeting.create', 'institution' => $this->institution->id]),
        ])->and($notification->secondaryAction())->toBe([
            'label' => __('notifications.action_report_activity'),
            'url' => route('dashboard', ['window' => 'check-in', 'institution' => $this->institution->id]),
        ]);
    });

    test('the second action is declared an answer, so mail draws it as a button', function (): void {
        expect(periodicityGapNotification($this->institution)->secondaryActionIsAnswer())->toBeTrue();
    });

    test('the title asks the question and stays within a subject line', function (): void {
        $title = periodicityGapNotification($this->institution)->title($this->recipient);

        expect($title)->toBe(__('notifications.periodicity_gap_question_title', ['institution' => $this->institution->name]))
            ->and(mb_strlen($title))->toBeLessThanOrEqual(80);
    });

    test('the body says how long it has been when that is known, and still makes sense when it is not', function (): void {
        $withDays = periodicityGapNotification($this->institution, ['effective_days_since_activity' => 41])->body($this->recipient);
        $without = periodicityGapNotification($this->institution)->body($this->recipient);

        expect($withDays)->toContain('41', $this->institution->name)
            ->and($without)->toBe(__('notifications.periodicity_gap_body'));
    });
});
