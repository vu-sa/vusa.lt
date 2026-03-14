<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModelEnum;
use App\Http\Controllers\AdminController;
use App\Models\Comment;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Enum\Laravel\Rules\EnumRule;

class CommentController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

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
            'stage' => 'nullable|integer|min:1|max:5',
        ]);

        // convert to camelCase
        $formatted = Str::ucfirst(Str::camel($validated['commentable_type']));

        if ($formatted === 'ReservationResource') {
            $modelClass = 'App\\Models\\Pivots\\ReservationResource';
        } else {
            $modelClass = 'App\\Models\\'.$formatted;
        }

        $model = $modelClass::find($request->commentable_id);

        $model->comment($request->comment, array_filter([
            'stage' => $validated['stage'] ?? null,
        ]));

        return back()->with('success', 'Komentaras pridėtas.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, Comment $comment)
    {
        $this->handleAuthorization('update', $comment);

        $validated = $request->validate([
            'comment' => 'required|string',
        ]);

        $validated['comment'] = strip_tags($validated['comment'], '<p><br><strong><em><b><i><u><s><ul><ol><li><a><blockquote><code><pre><h1><h2><h3><h4><h5><h6>');

        $comment->update($validated);

        return back()->with('success', 'Komentaras atnaujintas.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse
     */
    public function destroy(Comment $comment)
    {
        $this->handleAuthorization('delete', $comment);

        // delete comment
        $comment->delete();

        return back()->with('success', 'Komentaras ištrintas.');
    }
}
