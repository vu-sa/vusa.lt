<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\Comment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentPollVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('react', $this->route('comment'));
    }

    /**
     * The "is this even a poll, and is it still open" guards run here rather than in the
     * controller so they still fire *before* the option is validated — otherwise voting on a
     * closed poll would report an unknown-option error instead of saying the poll is closed.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        $comment = $this->comment();

        abort_unless($comment->isPoll(), 422, 'This comment is not a poll.');
        abort_if($comment->pollIsClosed(), 422, 'This poll is closed.');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $optionIds = collect($this->comment()->pollOptions())->pluck('id')->all();

        return [
            'option_id' => ['required', 'string', Rule::in($optionIds)],
        ];
    }

    protected function comment(): Comment
    {
        /** @var Comment $comment */
        $comment = $this->route('comment');

        return $comment;
    }
}
