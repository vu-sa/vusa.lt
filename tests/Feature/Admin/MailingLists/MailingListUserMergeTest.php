<?php

use App\Actions\MergeUsers;
use App\Models\MailingList;
use App\Models\MailingListExclusion;
use App\Models\MailingListMember;
use App\Models\MailingListSource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('merging users preserves mailing list identities and authorship', function (): void {
    $kept = User::factory()->create();
    $sourceUser = User::factory()->create();
    $list = MailingList::factory()->for(Tenant::main())->create();
    $member = MailingListMember::factory()->for($list)->create(['user_id' => $sourceUser->id]);
    $source = MailingListSource::factory()->for($list)->create(['created_by' => $sourceUser->id]);
    $exclusion = MailingListExclusion::factory()->for($list)->create(['created_by' => $sourceUser->id]);

    MergeUsers::execute($kept, collect([$sourceUser]), $kept->email);

    $this->assertModelMissing($sourceUser);
    expect($member->fresh()->user_id)->toBe($kept->id);
    expect($source->fresh()->created_by)->toBe($kept->id);
    expect($exclusion->fresh()->created_by)->toBe($kept->id);
});
