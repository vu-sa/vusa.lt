<?php

use App\Models\Institution;
use App\Models\InstitutionFollow;
use App\Models\Meeting;
use App\Models\User;
use App\Services\Learning\SystemSteps\BrowserPushDetector;
use App\Services\Learning\SystemSteps\InstitutionFollowDetector;
use App\Services\Learning\SystemSteps\NotificationSettingsDetector;
use App\Services\Learning\SystemSteps\ProfilePhotoDetector;
use App\Services\Learning\SystemSteps\RecordedMeetingDetector;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('system detectors identify the matching user in one batched query', function (string $detector, string $fixture): void {
    $this->freezeTime();
    $done = User::factory()->create();
    $pending = User::factory()->create();
    DB::table('users')->whereIn('id', [$done->id, $pending->id])->update(['notification_preferences' => null, 'profile_photo_path' => null]);
    match ($fixture) {
        'photo' => DB::table('users')->where('id', $done->id)->update(['profile_photo_path' => '/photo.jpg']),
        'preferences' => DB::table('users')->where('id', $done->id)->update(['notification_preferences' => '{}']),
        'follow' => InstitutionFollow::create(['user_id' => $done->id, 'institution_id' => Institution::factory()->create()->id]),
        'push' => DB::table('push_subscriptions')->insert(['subscribable_type' => MorphMap::alias(User::class), 'subscribable_id' => $done->id, 'endpoint' => 'https://push.example.test/one']),
        'meeting' => activity()->causedBy($done)->performedOn(Meeting::factory()->create())->event('created')->log('created'),
    };
    DB::enableQueryLog();
    DB::flushQueryLog();
    $ids = app($detector)->doneFor(collect([$done->id, $pending->id]));
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($ids->all())->toBe([$done->id]);
    expect($queries)->toHaveCount(1);
})->with([
    'photo' => [ProfilePhotoDetector::class, 'photo'],
    'preferences' => [NotificationSettingsDetector::class, 'preferences'],
    'following' => [InstitutionFollowDetector::class, 'follow'],
    'push' => [BrowserPushDetector::class, 'push'],
    'recorded meeting' => [RecordedMeetingDetector::class, 'meeting'],
]);
