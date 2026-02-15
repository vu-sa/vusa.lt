<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModelEnum;
use App\Http\Controllers\AdminController;
use App\Models\Comment;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Enum\Laravel\Rules\EnumRule;

class CommentController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Allowed commentable model types.
     * Only these models can be commented on via user input.
     *
     * @var array<string, class-string>
     */
    private const ALLOWED_COMMENTABLE_TYPES = [
        'reservation' => \App\Models\Reservation::class,
        'reservation-resource' => \App\Models\Pivots\ReservationResource::class,
        'institution' => \App\Models\Institution::class,
        'meeting' => \App\Models\Meeting::class,
        'sharepoint-file' => \App\Models\SharepointFile::class,
    ];

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'commentable_type' => [new EnumRule(ModelEnum::class), 'required'],
            'commentable_id' => 'required',
            'comment' => 'required|string',
            'route' => 'nullable|string',
        ]);

        $typeKey = Str::kebab($validated['commentable_type']);

        if (! isset(self::ALLOWED_COMMENTABLE_TYPES[$typeKey])) {
            return back()->with('error', 'Neleistinas komentaro tipas.');
        }

        $modelClass = self::ALLOWED_COMMENTABLE_TYPES[$typeKey];
        $model = $modelClass::find($validated['commentable_id']);

        if (! $model) {
            return back()->with('error', 'Modelis nerastas.');
        }

        $this->authorize('view', $model);

        $model->comment($validated['comment']);

        return back()->with('success', 'Komentaras pridėtas.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Comment $comment)
    {
        $this->handleAuthorization('update', $comment);

        // update comment
        $comment->update($request->only('comment'));

        return back()->with('success', 'Komentaras atnaujintas.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Comment $comment)
    {
        $this->handleAuthorization('delete', $comment);

        // delete comment
        $comment->delete();

        return back()->with('success', 'Komentaras ištrintas.');
    }
}
