<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModelEnum;
use App\Http\Controllers\LaravelResourceController;
use App\Models\Comment;
use App\Models\Traits\HasDecisions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Enum\Laravel\Rules\EnumRule;

class CommentController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->authorize('viewAny', [Comment::class, $this->authorizer]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Comment::class, $this->authorizer]);

        $validated = $request->validate([
            'commentable_type' => [new EnumRule(ModelEnum::class), 'required'],
            'commentable_id' => 'required|string', // TODO: sometimes ulid|uuid?
            'comment' => 'required|string',
            'decision' => 'nullable|string',
            'route' => 'nullable|string',
        ]);

        // convert to camelCase
        $formatted = Str::ucfirst(Str::camel($validated['commentable_type']));

        $modelClass = 'App\\Models\\'.$formatted;
        $model = $modelClass::find($request->commentable_id);

        // TODO: Add authorization
        if ($request->decision && class_uses($model, HasDecisions::class)) {
            $this->authorize('update', [$model::class, $model, $this->authorizer]);
            $model->decision($request->decision, $this->authorizer);
        }

        $model->comment($request->comment, $request->decision);

        return redirect()->back()->with('success', 'Komentaras pridėtas.');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Comment $comment)
    {
        return $this->authorize('view', [Comment::class, $comment, $this->authorizer]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Comment $comment)
    {
        $this->authorize('update', [Comment::class, $comment, $this->authorizer]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', [Comment::class, $comment, $this->authorizer]);

        // update comment
        $comment->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', [Comment::class, $comment, $this->authorizer]);

        // delete comment
        $comment->delete();
    }
}
