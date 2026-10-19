<?php

use App\Models\InstitutionActivityRequest;
use App\Models\NotificationDigestQueue;
use App\Models\Task;
use App\Models\User;
use App\Notifications\InstitutionActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

function automaticActivityRequest(User $recipient, string $createdAt, array $attributes = []): InstitutionActivityRequest
{
    $request = InstitutionActivityRequest::factory()->create([
        'recipient_id' => $recipient->id, 'requested_by_id' => null, 'task_id' => Task::factory()->create()->id, ...$attributes,
    ]);
    $request->forceFill(['created_at' => $createdAt])->save();

    return $request;
}

function queuedActivityDigestItem(InstitutionActivityRequest $request): NotificationDigestQueue
{
    return NotificationDigestQueue::create([
        'user_id' => $request->recipient_id, 'notification_id' => (string) Str::uuid(),
        'notification_class' => InstitutionActivityNotification::class, 'category' => 'task',
        'data' => ['activity_request_ids' => [$request->id]],
    ]);
}

beforeEach(function (): void {
    $this->recipient = User::factory()->create();
    $this->first = automaticActivityRequest($this->recipient, '2026-10-01 10:00:01');
    $this->second = automaticActivityRequest($this->recipient, '2026-10-01 10:00:05');
    $this->nextDay = automaticActivityRequest($this->recipient, '2026-10-02 10:00:01');
    $this->manual = InstitutionActivityRequest::factory()->create(['requested_by_id' => User::factory()->create()->id]);
    $this->manual->forceFill(['created_at' => '2026-10-01 11:00:00'])->save();
});

test('merges each day of automatic requests into one send and leaves manual sends alone', function (): void {
    $manualSend = $this->manual->send_id;

    $this->artisan('activity-requests:regroup')->assertSuccessful();

    $sendOf = fn (InstitutionActivityRequest $request) => $request->fresh()->send_id;
    expect($sendOf($this->second))->toBe($sendOf($this->first))
        ->and($sendOf($this->nextDay))->not->toBe($sendOf($this->first))
        ->and($sendOf($this->manual))->toBe($manualSend);
});

test('merges pending digest items of one regrouped send into one', function (): void {
    $kept = queuedActivityDigestItem($this->first);
    queuedActivityDigestItem($this->second);
    queuedActivityDigestItem($this->nextDay);

    $this->artisan('activity-requests:regroup')->assertSuccessful();

    expect(NotificationDigestQueue::query()->count())->toBe(2)
        ->and($kept->fresh()->data['activity_request_ids'])->toEqualCanonicalizing([$this->first->id, $this->second->id]);
});

test('a dry run writes nothing and a second run changes nothing', function (): void {
    queuedActivityDigestItem($this->first);
    queuedActivityDigestItem($this->second);
    $sends = fn () => InstitutionActivityRequest::query()->orderBy('id')->pluck('send_id', 'id')->all();
    $before = $sends();

    $this->artisan('activity-requests:regroup --dry-run')->assertSuccessful();
    expect($sends())->toBe($before)->and(NotificationDigestQueue::query()->count())->toBe(2);

    $this->artisan('activity-requests:regroup')->assertSuccessful();
    $after = $sends();
    $this->artisan('activity-requests:regroup')->assertSuccessful();
    expect($sends())->toBe($after)->and(NotificationDigestQueue::query()->count())->toBe(1);
});

test('links in emails sent before the regroup still open and answer the question', function (): void {
    $emailedPage = $this->second->answerUrl();
    $emailedSubmit = $this->second->submitUrl();

    $this->artisan('activity-requests:regroup')->assertSuccessful();

    expect($this->second->fresh()->send_id)->toBe($this->first->fresh()->send_id);
    $this->get($emailedPage)->assertOk()->assertSee($this->second->institution->name)->assertSee($this->first->institution->name);
    $this->post($emailedSubmit, ['answer' => 'not_mine'])->assertRedirect($emailedPage);
    expect($this->second->fresh()->answered_at)->not->toBeNull();
});
