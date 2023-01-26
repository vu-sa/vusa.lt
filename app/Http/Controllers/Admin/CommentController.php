<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModelEnum;
use App\Models\Comment;
use App\Models\User;
use App\Http\Controllers\ResourceController;
use Illuminate\Http\Request;
use Spatie\Enum\Laravel\Rules\EnumRule;
use Illuminate\Support\Str;
use App\Models\Traits\HasDecisions;

class CommentController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function index(User $user)
    {
        $this->authorize('viewAny', [Comment::class, $this->authorizer]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    // public function create(User $user)
    // {
    //     $this->authorize('create', [Comment::class, $this->authorizer]);
    // }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        $this->authorize('create', [Comment::class, $this->authorizer]);

        $validated = $request->validate([
            'commentable_type' => [new EnumRule(ModelEnum::class), 'required'],
            'commentable_id' => 'required|string', // TODO: sometimes ulid|uuid?
            'comment' => 'required|string',
            'decision' => 'nullable|string',
            'route' => 'nullable|string'
        ]);

        // convert to camelCase
        $formatted = Str::ucfirst(Str::camel($validated['commentable_type']));

        $modelClass = 'App\\Models\\' . $formatted;
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
     * @param  \App\Models\User  $user
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function show(User $user, Comment $comment)
    {
        $this->authorize('view', [Comment::class, $comment, $this->authorizer]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user, Comment $comment)
    {
        $this->authorize('update', [Comment::class, $comment, $this->authorizer]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user, Comment $comment)
    {
        $this->authorize('update', [Comment::class, $comment, $this->authorizer]);

        // update comment
        $comment->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user, Comment $comment)
    {
        $this->authorize('delete', [Comment::class, $comment, $this->authorizer]);
        
        // delete comment
        $comment->delete();
    }
}
