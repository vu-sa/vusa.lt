<?php

use App\Notifications\CommentPostedNotification;

beforeEach(function (): void {
    $this->object = ['modelClass' => 'Meeting', 'name' => 'Taryba', 'url' => '/meeting/1'];
    $this->commenter = ['modelClass' => 'User', 'name' => 'Jonas'];
    $this->recipient = new stdClass;
});

test('the excerpt separates blocks, keeps mention labels and decodes entities', function (): void {
    $notification = new CommentPostedNotification(
        '<p>Pirmas &amp; antras <strong>punktas</strong>.</p><p><span data-id="123">@Ona</span><br>Trečias</p><ul><li>Vienas</li><li>Du&nbsp;žodžiai</li></ul>',
        $this->object,
        $this->commenter,
    );

    expect($notification->excerpt($this->recipient))->toBe('Pirmas & antras punktas. @Ona Trečias Vienas Du žodžiai');
});

test('an empty comment has no excerpt or dangling quote in its digest line', function (string $body): void {
    app()->setLocale('en');
    $notification = new CommentPostedNotification($body, $this->object, $this->commenter);

    expect($notification->excerpt($this->recipient))->toBeNull()
        ->and($notification->digestLine($this->recipient))->toBe('Jonas left a comment · Taryba');
})->with(['', '<p><br></p>', '<p>&nbsp; </p>']);

test('the excerpt truncates long user content after 200 characters', function (): void {
    $notification = new CommentPostedNotification('<p>'.str_repeat('ą', 201).'</p>', $this->object, $this->commenter);

    expect($notification->excerpt($this->recipient))->toBe(str_repeat('ą', 200).'...');
});

test('comment bodies distinguish activity, polls and mentions in both locales', function (string $locale, string $posted, string $poll, string $mention, string $pollMention): void {
    app()->setLocale($locale);

    expect(new CommentPostedNotification('Tekstas', $this->object, $this->commenter)->body($this->recipient))->toBe($posted)
        ->and(new CommentPostedNotification('Tekstas', $this->object, $this->commenter, isPoll: true)->body($this->recipient))->toBe($poll)
        ->and(new CommentPostedNotification('Tekstas', $this->object, $this->commenter, isMention: true)->body($this->recipient))->toBe($mention)
        ->and(new CommentPostedNotification('Tekstas', $this->object, $this->commenter, isMention: true, isPoll: true)->body($this->recipient))->toBe($pollMention);
})->with([
    ['lt', 'Jonas paliko komentarą', 'Jonas pradėjo apklausą', 'Jonas paminėjo tave komentare', 'Jonas paminėjo tave apklausoje'],
    ['en', 'Jonas left a comment', 'Jonas started a poll', 'Jonas mentioned you in a comment', 'Jonas mentioned you in a poll'],
]);

test('a poll has its own subject', function (string $locale, string $comment, string $poll): void {
    app()->setLocale($locale);

    expect(new CommentPostedNotification('Tekstas', $this->object, $this->commenter)->title($this->recipient))->toBe($comment)
        ->and(new CommentPostedNotification('Tekstas', $this->object, $this->commenter, isPoll: true)->title($this->recipient))->toBe($poll);
})->with([
    ['lt', 'Naujas komentaras: Taryba', 'Nauja apklausa: Taryba'],
    ['en', 'New comment: Taryba', 'New poll: Taryba'],
]);

test('the bell, broadcast, digest and push receive the comment excerpt', function (): void {
    app()->setLocale('en');
    $notification = new CommentPostedNotification('<p>Keep this item.</p>', $this->object, $this->commenter);

    expect($notification->toArray($this->recipient)['excerpt'])->toBe('Keep this item.')
        ->and($notification->toBroadcast($this->recipient)->data['excerpt'])->toBe('Keep this item.')
        ->and($notification->toDigestItem($this->recipient)['digestLine'])->toBe('Jonas left a comment · Taryba — “Keep this item.”')
        ->and($notification->toWebPush($this->recipient, $notification)->toArray()['body'])->toBe('Jonas left a comment — “Keep this item.”');
});

test('push truncates the excerpt inside locale quotes within 120 characters', function (string $locale, string $opening, string $closing): void {
    app()->setLocale($locale);
    $notification = new CommentPostedNotification(str_repeat('ą', 200), $this->object, $this->commenter);

    $body = $notification->toWebPush($this->recipient, $notification)->toArray()['body'];

    expect(mb_strlen($body))->toBe(120)
        ->and($body)->toContain(' — '.$opening.'ą')->toEndWith('…'.$closing);
})->with([
    ['lt', '„', '“'],
    ['en', '“', '”'],
]);

test('push reserves room for a quoted excerpt even with a long author name', function (): void {
    app()->setLocale('en');
    $notification = new CommentPostedNotification('A comment', $this->object, [
        ...$this->commenter, 'name' => str_repeat('ą', 150),
    ]);

    $body = $notification->toWebPush($this->recipient, $notification)->toArray()['body'];

    expect(mb_strlen($body))->toBe(120)
        ->and($body)->toEndWith('… — “A…”');
});

test('the excerpt separates all supported block endings', function (string $tag): void {
    $notification = new CommentPostedNotification('<'.$tag.'>First</'.$tag.'>Second', $this->object, $this->commenter);

    expect($notification->excerpt($this->recipient))->toBe('First Second');
})->with(['p', 'li', 'ul', 'ol', 'blockquote', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
