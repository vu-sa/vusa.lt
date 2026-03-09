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

        // convert to camelCase
        $formatted = Str::ucfirst(Str::camel($validated['commentable_type']));

        if ($formatted === 'ReservationResource') {
            $modelClass = 'App\\Models\\Pivots\\ReservationResource';
        } else {
            $modelClass = 'App\\Models\\'.$formatted;
        }

        $model = $modelClass::find($request->commentable_id);

        $model->comment($request->comment);

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
        $comment->update($request->all());

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
