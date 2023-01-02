<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Models\Comment;
use App\Models\Doing;
use App\Models\User;
use App\Events\UserComments;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    
    public function __construct()
    {
        $this->authorizeResource(Comment::class, 'comment');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function index(User $user)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function create(User $user)
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        // check if request commentable type is App\Models\Doing
        if ($request->commentable_type != 'App\Models\Doing') {
            return redirect()->back()->with('error', 'Įvyko klaida');
        } 

        $model = $request->commentable_type::find($request->commentable_id);

        $model->addComment($request->comment, $user);

        // dispatch event
        UserComments::dispatch($user, $model, $request->route);

        // $commenter, $modelCommentedOn, $route, $parentModelId = null

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
        //
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
        //
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
        //
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
        // delete comment
        $comment->delete();
    }
}
