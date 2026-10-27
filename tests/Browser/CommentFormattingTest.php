<?php

use App\Models\Meeting;
use App\Models\SupportRequest;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('formatted comments survive saving and editing across responsive layouts', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $request = SupportRequest::factory()->create(['created_by' => $user->id, 'title' => 'Komentarų formatavimas']);
    $path = route('supportRequests.show', $request->id, absolute: false);
    $page = loginAsAdmin($user, $path, 390, 900);
    $composer = '.comment-composer .ProseMirror';
    $page->assertPresent('[data-testid=comment-toolbar]');
    $page->fill($composer, 'Pirmas punktas');
    $page->click('[data-testid=tiptap-bulletList]');
    $page->assertPresent('.ProseMirror ul');
    $page->keys($composer, ['End', 'Enter']);
    $page->page()->locator($composer)->type('Antras punktas');
    $page->keys($composer, ['Enter', 'Enter']);
    $page->page()->locator($composer)->type('Pabaiga');
    $page->keys($composer, 'Control+a');
    $page->click('[data-testid=tiptap-format-bold]');
    $page->assertPresent('.ProseMirror strong');
    $page->press('Komentuoti');
    $page->assertSee('Pirmas punktas')->assertSee('Antras punktas');

    $comment = $request->comments()->firstOrFail();
    expect($comment->body)->toContain('<ul>', '<strong>Pirmas punktas</strong>', 'Antras punktas')
        ->and(substr_count($comment->body, '<li>'))->toBe(2);
    navigateTo($page, $path);
    $page->assertPresent('[data-slot=comment-thread] .comment-content ul');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);
        foreach ([false, true] as $dark) {
            $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');
            expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);
            $page->screenshotElement('[data-slot=discussion-panel]', filename: 'comments-'.$width.($dark ? '-dark' : '-light'));
        }
    }

    $page->click('[data-testid=comment-actions]');
    $page->click('[data-testid=comment-edit]');
    $page->fill('[data-slot=comment-thread] .ProseMirror', 'Pataisytas punktas');
    $page->click('[data-slot=comment-thread] [data-testid=tiptap-orderedList]');
    $page->press('Išsaugoti');
    $page->assertSee('Pataisytas punktas');
    expect($comment->fresh()->body)->toContain('<ol>');

    $page->resize(390, 900);
    $page->press('Apklausa');
    $page->assertPresent('[role=dialog] [data-testid=comment-toolbar]');
    $page->click('[role=dialog] [data-testid=tiptap-link]');
    $page->fill('[data-testid=link-url]', '/mano/meetings');
    $page->fill('[data-testid=link-text]', 'Posėdžiai');
    $page->keys('[data-testid=link-url]', 'Enter');
    $page->assertPresent('[role=dialog] .ProseMirror a[href="/mano/meetings"]');
    $page->click('[role=dialog] [data-testid=comment-mention]');
    $page->assertPresent('[role=dialog] [data-comment-mention-menu]');
    $page->keys('[role=dialog] .ProseMirror', 'Escape');
    $page->assertNotPresent('[data-comment-mention-menu]')->assertPresent('[role=dialog]');
    $page->click('[role=dialog] [data-testid=comment-mention]');
    $page->click('[role=dialog] [data-comment-mention-menu] button');
    $page->assertPresent('[role=dialog] .ProseMirror [data-type=mention]');
    $page->screenshotElement('[role=dialog]', filename: 'comments-poll-phone-dark');
    $page->assertNoJavaScriptErrors();
});

test('a reply can be posted and reloaded on both discussion surfaces', function (string $type): void {
    $user = makeTenantUserWithRole('Komunikacijos koordinatorius', Tenant::query()->firstOrFail());
    if ($type === 'meeting') {
        $commentable = Meeting::factory()->create();
        $commentable->institutions()->attach($user->duties()->firstOrFail()->institution_id);
        $path = route('meetings.show', $commentable->id, absolute: false);
    } else {
        $commentable = SupportRequest::factory()->create(['created_by' => $user->id]);
        $path = route('supportRequests.show', $commentable->id, absolute: false);
    }
    $this->actingAs($user);
    $root = $commentable->comment('<p>Pradinis komentaras</p>');
    $page = loginAsAdmin($user, $path, 390, 900);
    $page->assertSee('Pradinis komentaras');

    $page->click('[data-slot=comment-thread] button:has-text("Atsakyti")');
    $page->fill('[data-slot=comment-thread] .ProseMirror', 'Atsakymas į komentarą');
    $page->click('[data-slot=comment-thread] .comment-composer button:has-text("Atsakyti")');
    $page->assertSee('Atsakymas į komentarą');

    $reply = $root->replies()->firstOrFail();
    expect($reply->parent_id)->toBe($root->id)
        ->and($reply->thread_root_id)->toBe($root->id)
        ->and($reply->body)->toContain('Atsakymas į komentarą')
        ->and($commentable->comments()->count())->toBe(2);
    navigateTo($page, $path);
    $page->assertSee('Atsakymas į komentarą')->assertNoJavaScriptErrors();
    $surface = $type === 'meeting' ? '[aria-labelledby=record-activity-title]' : '[data-slot=discussion-panel]';
    $page->screenshotElement($surface, filename: 'comment-reply-'.$type);
})->with(['meeting', 'supportRequest']);
