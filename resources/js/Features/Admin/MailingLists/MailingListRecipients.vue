<template>
  <div class="space-y-8" data-slot="mailing-list-recipients">
    <section v-if="diff && canUpdate" class="space-y-3" data-testid="mailing-list-diff">
      <h3 class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
        {{ $t('mailing_lists.diff.title') }}
      </h3>

      <p v-if="diff.error" class="text-sm text-destructive">
        {{ diff.error }}
      </p>
      <p v-else-if="!diff.add?.length && !diff.remove?.length" class="text-sm text-muted-foreground">
        {{ $t('mailing_lists.diff.in_sync') }}
      </p>
      <template v-else>
        <p v-if="!managed" class="text-sm text-muted-foreground">
          {{ $t('mailing_lists.diff.preview_hint') }}
        </p>
        <p v-if="diff.needs_review" class="border-l-2 border-status-attention-border pl-3 text-sm">
          {{ $t('mailing_lists.diff.needs_review') }}
        </p>

        <div class="grid gap-6 lg:grid-cols-2">
          <div v-if="diff.add?.length">
            <p class="mb-1 text-sm font-medium">
              {{ $t('mailing_lists.diff.add', { count: String(diff.add.length) }) }}
            </p>
            <ul class="divide-y divide-border border-y border-border">
              <li v-for="entry in diff.add" :key="entry.email" class="flex min-h-11 items-center justify-between gap-3 py-2">
                <span class="min-w-0">
                  <span class="block truncate text-sm">{{ entry.display_name ?? entry.email }}</span>
                  <span class="block truncate text-xs text-muted-foreground">{{ entry.email }} · {{ entry.reasons.join(' · ') }}</span>
                </span>
                <Button variant="ghost" size="sm" voice="sentence" class="pointer-coarse:min-h-11" data-testid="diff-exclude" @click="exclude(entry.email)">
                  {{ $t('mailing_lists.diff.exclude') }}
                </Button>
              </li>
            </ul>
          </div>

          <div v-if="diff.remove?.length">
            <p class="mb-1 text-sm font-medium">
              {{ $t('mailing_lists.diff.remove', { count: String(diff.remove.length) }) }}
            </p>
            <ul class="divide-y divide-border border-y border-border">
              <li v-for="entry in diff.remove" :key="entry.email" class="flex min-h-11 items-center justify-between gap-3 py-2">
                <span class="min-w-0">
                  <span class="block truncate text-sm">{{ entry.display_name ?? entry.email }}</span>
                  <span v-if="entry.display_name" class="block truncate text-xs text-muted-foreground">{{ entry.email }}</span>
                </span>
                <Button variant="ghost" size="sm" voice="sentence" class="pointer-coarse:min-h-11" data-testid="diff-keep" @click="keep(entry)">
                  {{ $t('mailing_lists.diff.keep') }}
                </Button>
              </li>
            </ul>
          </div>
        </div>
      </template>
    </section>

    <section class="space-y-3">
      <p v-if="!managed" class="text-xs text-muted-foreground">
        {{ $t('mailing_lists.recipients.unmanaged_note') }}
      </p>
      <p v-if="!roster.length" class="text-sm text-muted-foreground">
        {{ $t('mailing_lists.recipients.empty') }}
      </p>
      <ul v-else class="divide-y divide-border">
        <li v-for="entry in roster" :key="entry.email" class="flex min-h-11 flex-col justify-center gap-0.5 py-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
          <span class="min-w-0">
            <span class="block truncate text-sm font-medium">{{ entry.display_name ?? entry.email }}</span>
            <span v-if="entry.display_name" class="block truncate text-xs text-muted-foreground">{{ entry.email }}</span>
          </span>
          <span class="text-xs text-muted-foreground sm:max-w-[50%] sm:text-right">
            {{ entry.reasons?.length ? entry.reasons.join(' · ') : $t('mailing_lists.recipients.unknown_reason') }}
          </span>
        </li>
      </ul>
    </section>
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import type { MailingListDiffPayload, MailingListRosterEntry } from './types';

import { Button } from '@/Components/ui/button';

const props = defineProps<{
  mailingListId: string;
  roster: MailingListRosterEntry[];
  diff: MailingListDiffPayload | null;
  managed: boolean;
  canUpdate: boolean;
}>();

const reload = { preserveScroll: true, except: ['messages'] };

/** Someone Mailman has but no source explains: keep them as an explicit manual member. */
function keep(entry: { email: string; display_name: string | null }): void {
  router.post(route('mailingLists.sources.store', props.mailingListId), {
    type: 'email',
    email: entry.email,
    display_name: entry.display_name,
    address_mode: 'holder',
  }, reload);
}

function exclude(email: string): void {
  router.post(route('mailingLists.exclusions.store', props.mailingListId), { email }, reload);
}
</script>
