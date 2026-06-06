<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Live discussion updates pushed to everyone viewing a commentable. This is a
 * separate concern from CommentPosted (which drives notifications): this event
 * only hydrates open discussion panels so new comments, edits, deletions,
 * resolves and reactions appear without a reload.
 *
 * The payload is assembled by the controller (which has the request context),
 * so the broadcast carries no per-user `can`/`reacted_by_me` truth — receiving
 * clients recompute those locally.
 */
class CommentBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  'created'|'updated'|'deleted'|'resolved'|'reaction'|'poll'  $action
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $channelName,
        public string $action,
        public array $payload,
    ) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel($this->channelName)];
    }

    public function broadcastAs(): string
    {
        return 'comment.'.$this->action;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
