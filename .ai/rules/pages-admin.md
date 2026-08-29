---
paths:
  - 'app/Http/Controllers/Admin/MailQueueController.php,app/Console/Commands/PruneNotificationDigests.php,resources/js/Pages/Admin/MailQueue.vue'
---

# Pages Admin

## The mail queue is the notification digest backlog
"Mail queue" in the UI means `notification_digest_queue`, not Laravel's `jobs` table: one row per notification, and ProcessNotificationDigests turns each user's rows into one digest email. So /mano/mail-queue lists recipients, and an item is a line that email will contain. Reading it needs the same gate as system status (`viewAny` on Role); discarding other people's pending mail is super-admin only. From the CLI, `notifications:prune-digests --all --force` empties it; without `--all` it only drops items past `--older-than`.
