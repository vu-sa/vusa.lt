<?php

use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\Form;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Page;
use App\Models\Problem;
use App\Models\QuickLink;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\StudyProgram;
use App\Models\StudySet;
use App\Models\Tag;
use App\Models\Training;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('exposes deleted_at for soft-deletable models', function (string $modelClass, Closure $createModel, bool $usesFullArray): void {
    /** @var Model $model */
    $model = $createModel();
    $model->delete();

    /** @var Model $fresh */
    $fresh = $modelClass::withTrashed()->findOrFail($model->getKey());

    $arrayPayload = $fresh->toArray();

    expect($arrayPayload)
        ->toHaveKey('deleted_at')
        ->and($arrayPayload['deleted_at'])
        ->not->toBeNull();

    if ($usesFullArray) {
        expect(method_exists($fresh, 'toFullArray'))->toBeTrue();

        $fullArrayPayload = $fresh->toFullArray();

        expect($fullArrayPayload)
            ->toHaveKey('deleted_at')
            ->and($fullArrayPayload['deleted_at'])
            ->not->toBeNull();
    }
})->with([
    'banner' => [Banner::class, fn (): Banner => Banner::factory()->create(), false],
    'calendar' => [Calendar::class, fn (): Calendar => Calendar::factory()->create(), true],
    'category' => [Category::class, fn (): Category => Category::factory()->create(), true],
    'comment' => [
        Comment::class,
        fn (): Comment => Comment::factory()
            ->for(Meeting::factory(), 'commentable')
            ->create(),
        false,
    ],
    'duty' => [Duty::class, fn (): Duty => Duty::factory()->create(), true],
    'form' => [Form::class, fn (): Form => Form::factory()->create(), true],
    'institution' => [Institution::class, fn (): Institution => Institution::factory()->create(), true],
    'meeting' => [Meeting::class, fn (): Meeting => Meeting::factory()->create(), false],
    'navigation' => [Navigation::class, fn (): Navigation => Navigation::factory()->create(), false],
    'news' => [News::class, fn (): News => News::factory()->create(), false],
    'page' => [Page::class, fn (): Page => Page::factory()->create(), false],
    'problem' => [Problem::class, fn (): Problem => Problem::factory()->create(), true],
    'quick link' => [QuickLink::class, fn (): QuickLink => QuickLink::factory()->create(), false],
    'reservation' => [Reservation::class, fn (): Reservation => Reservation::factory()->create(), false],
    'resource' => [Resource::class, fn (): Resource => Resource::factory()->create(), true],
    'study program' => [StudyProgram::class, fn (): StudyProgram => StudyProgram::factory()->create(), true],
    'study set' => [StudySet::class, fn (): StudySet => StudySet::factory()->create(), true],
    'tag' => [Tag::class, fn (): Tag => Tag::factory()->create(), true],
    'training' => [Training::class, fn (): Training => Training::factory()->create(), true],
    'type' => [Type::class, fn (): Type => Type::factory()->create(), true],
    'user' => [User::class, fn (): User => User::factory()->create(), true],
]);
