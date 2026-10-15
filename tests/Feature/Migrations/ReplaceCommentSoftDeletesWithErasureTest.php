<?php

use App\Models\Comment;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

pest()->use(RefreshDatabase::class);

function commentErasureMigration(): object
{
    return require base_path('database/migrations/2026_09_28_160000_replace_comment_soft_deletes_with_erasure.php');
}

test('a comment deleted before the change is erased, not kept hidden', function (): void {
    $migration = commentErasureMigration();
    $migration->down();

    $author = User::factory()->create();
    $this->actingAs($author);
    $institution = Institution::factory()->create();
    $kept = $institution->comment('<p>Lieka</p>');
    $trashed = $institution->comment('<p>Ištrintas, bet vis dar saugomas</p>');
    $trashed->reactions()->create(['user_id' => $author->id, 'emoji' => '👍']);
    DB::table('comments')->where('id', $trashed->id)->update(['deleted_at' => now()]);

    $migration->up();

    $row = DB::table('comments')->where('id', $trashed->id)->first();

    expect(Schema::hasColumn('comments', 'deleted_at'))->toBeFalse()
        ->and($row->body)->toBe('')
        ->and($row->user_id)->toBeNull()
        ->and($row->erased_at)->not->toBeNull()
        ->and(DB::table('comment_reactions')->where('comment_id', $trashed->id)->exists())->toBeFalse()
        ->and(Comment::query()->find($kept->id)->body)->toBe('<p>Lieka</p>');
});
