<?php

namespace App\Actions;

use App\Http\Requests\IndexMailQueueRequest;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BuildMailQueuePage
{
    public function execute(IndexMailQueueRequest $request): LengthAwarePaginator
    {
        $query = NotificationDigestQueue::query()
            ->select('user_id')
            ->selectRaw('count(*) as items_count')
            ->selectRaw('min(created_at) as oldest_at')
            ->selectRaw('max(created_at) as newest_at')
            ->groupBy('user_id');

        $search = $request->validated('search');
        if (is_string($search) && $search !== '') {
            $userIds = User::query()
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->select('id');
            $query->whereIn('user_id', $userIds);
        }

        $sort = $request->getSorting()[0] ?? null;
        $sortId = is_array($sort) ? ($sort['id'] ?? null) : null;
        $column = in_array($sortId, ['items_count', 'oldest_at', 'newest_at'], true) ? $sortId : 'items_count';
        $query->orderBy($column, is_array($sort) && ($sort['desc'] ?? true) === false ? 'asc' : 'desc');

        $recipientPage = $query->paginate($request->getPerPage())->withQueryString();

        $itemsByUser = NotificationDigestQueue::query()
            ->with('user:id,name,email,profile_photo_path')
            ->whereIn('user_id', $recipientPage->getCollection()->pluck('user_id'))
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id');

        return $recipientPage->through(fn (NotificationDigestQueue $recipient): array => $this->describeRecipient(
            $itemsByUser->get($recipient->user_id, new Collection),
            (int) $recipient->getAttribute('items_count'),
        ));
    }

    /** @param Collection<int, NotificationDigestQueue> $items */
    private function describeRecipient(Collection $items, int $itemsCount): array
    {
        $first = $items->first();

        return [
            'user_id' => $first->user_id,
            'user' => $first->user?->only(['id', 'name', 'email', 'profile_photo_path']),
            'items_count' => $itemsCount,
            'oldest_at' => $items->min('created_at')?->toISOString(),
            'newest_at' => $items->max('created_at')?->toISOString(),
            'items' => $items->map(fn (NotificationDigestQueue $item): array => [
                'id' => $item->id,
                'category' => $item->category,
                'notification_class' => class_basename($item->notification_class),
                'title' => $item->data['title'] ?? null,
                'body' => $item->data['body'] ?? null,
                'url' => $item->data['url'] ?? null,
                'created_at' => $item->created_at?->toISOString(),
            ])->values()->all(),
        ];
    }
}
