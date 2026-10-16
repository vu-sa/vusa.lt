<?php

namespace App\Actions;

use App\Models\MailingList;
use App\Services\Mailman\HyperkittyClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class FetchMailingListArchive
{
    private float $deadline;

    public function __construct(private readonly HyperkittyClient $hyperkitty) {}

    public function execute(MailingList $mailingList): int
    {
        return Cache::lock('mailing-list:archive:'.$mailingList->id, 900)->block(2, function () use ($mailingList): int {
            $this->deadline = microtime(true) + 600;
            $mailingList->refresh();
            if (! $this->hyperkitty->hasArchive($mailingList->address)) {
                $mailingList->update(['archive_fetched_at' => now()]);

                return 0;
            }

            $added = $this->fetchNewMessages($mailingList);
            if ($mailingList->archive_backfilled_at === null) {
                $added += $this->backfill($mailingList);
            }

            $mailingList->update([
                'last_post_at' => $mailingList->messages()->max('sent_at'),
                'posts_last_year' => $mailingList->messages()->where('sent_at', '>=', now()->subYear())->count(),
                'archive_fetched_at' => now(),
            ]);

            return $added;
        });
    }

    private function fetchNewMessages(MailingList $list): int
    {
        $added = 0;
        $head = null;
        $offset = 0;
        do {
            $page = $this->hyperkitty->emailPage($list->address, $offset);
            $head ??= $page['results'][0]['message_id_hash'] ?? null;
            foreach ($page['results'] as $email) {
                if (microtime(true) >= $this->deadline) {
                    return $added;
                }
                if ($email['message_id_hash'] === $list->archive_head_hash) {
                    $list->update(['archive_head_hash' => $head]);

                    return $added;
                }
                $added += $this->store($list, $email);
            }
            $offset += count($page['results']);
            // The first page establishes the head; older history has a separate resumable cursor.
            if ($list->archive_head_hash === null || $page['next'] === null) {
                $list->update(['archive_head_hash' => $head]);

                return $added;
            }
        } while ($added < 500);

        return $added;
    }

    private function backfill(MailingList $list): int
    {
        $added = 0;
        for ($batch = 0; $batch < 10; $batch++) {
            $offset = $list->archive_backfill_offset;
            $page = $this->hyperkitty->emailPage($list->address, $offset);
            if ($offset > 0 && $page['count'] !== $list->archive_backfill_total) {
                $offset = $page['count'] < $list->archive_backfill_total
                    ? 0 : $offset + $page['count'] - $list->archive_backfill_total;
                $page = $this->hyperkitty->emailPage($list->address, $offset);
            }
            foreach ($page['results'] as $email) {
                if (microtime(true) >= $this->deadline) {
                    return $added;
                }
                $added += $this->store($list, $email);
            }
            $list->update([
                'archive_backfill_offset' => $offset + count($page['results']),
                'archive_backfill_total' => $page['count'],
                'archive_backfilled_at' => $page['next'] === null ? now() : null,
            ]);
            if ($page['next'] === null) {
                break;
            }
        }

        return $added;
    }

    /** @param array<string, mixed> $email */
    private function store(MailingList $list, array $email): int
    {
        $hash = $email['message_id_hash'];
        if ($list->messages()->where('message_id_hash', $hash)->exists()) {
            return 0;
        }
        $detail = $this->hyperkitty->emailDetail($list->address, $hash);
        $list->messages()->firstOrCreate(['message_id_hash' => $hash], [
            'thread_id' => HyperkittyClient::threadId($email['thread'] ?? null),
            'subject' => isset($email['subject']) ? mb_substr((string) $email['subject'], 0, 255) : null,
            'sender_name' => isset($email['sender_name']) ? mb_substr((string) $email['sender_name'], 0, 255) : null,
            'sender_email' => HyperkittyClient::senderEmail($email['sender']['address'] ?? null),
            'sent_at' => isset($email['date']) ? Carbon::parse($email['date'])->setTimezone(config('app.timezone')) : null,
            'body_text' => $detail['body_text'],
            'attachments' => $detail['attachments'],
            'url' => $this->hyperkitty->messageUrl($list->address, $hash),
        ]);

        return 1;
    }
}
