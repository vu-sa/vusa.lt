<template>
  <div data-slot="mailing-list-archive">
    <p v-if="!messages.length" class="text-sm text-muted-foreground">
      {{ $t('mailing_lists.archive.empty') }}
    </p>
    <ul v-else class="divide-y divide-border">
      <li v-for="message in messages" :key="message.id" class="py-3">
        <button
          type="button"
          class="flex min-h-11 w-full flex-col items-start gap-1 text-left sm:flex-row sm:justify-between sm:gap-4"
          :aria-expanded="expanded === message.id"
          @click="expanded = expanded === message.id ? null : message.id"
        >
          <span class="min-w-0">
            <span class="block text-sm font-medium text-foreground">{{ message.subject || '—' }}</span>
            <span class="block text-xs text-muted-foreground">
              {{ message.sender_name || message.sender_email }}
              <template v-if="message.attachments.length"> · {{ $t('mailing_lists.archive.attachments') }}: {{ message.attachments.length }}</template>
            </span>
          </span>
          <span class="shrink-0 text-xs tabular-nums text-muted-foreground">{{ message.sent_at ? formatDate(message.sent_at) : '' }}</span>
        </button>

        <div v-if="expanded === message.id" class="mt-2 space-y-3">
          <p class="whitespace-pre-line break-words text-sm text-foreground">
            {{ message.body_text }}
          </p>
          <ul v-if="message.attachments.length" class="flex flex-wrap gap-2">
            <li v-for="attachment in message.attachments" :key="attachment.name">
              <a
                v-if="safeUrl(attachment.url)" :href="safeUrl(attachment.url)" target="_blank" rel="noopener noreferrer"
                class="inline-flex min-h-11 items-center gap-1.5 break-all text-sm hover:text-brand"
              >
                <Paperclip class="size-4" aria-hidden="true" />
                {{ attachment.name }}
              </a>
              <span v-else class="inline-flex items-center gap-1.5 text-sm"><Paperclip class="size-4" aria-hidden="true" />{{ attachment.name }}</span>
            </li>
          </ul>
          <a
            v-if="safeUrl(message.url)" :href="safeUrl(message.url)" target="_blank" rel="noopener noreferrer"
            class="inline-flex min-h-11 items-center gap-1.5 text-sm text-muted-foreground hover:text-brand"
          >
            <ExternalLink class="size-4" aria-hidden="true" />
            {{ $t('mailing_lists.archive.open') }}
          </a>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ExternalLink, Paperclip } from '@lucide/vue';
import { ref } from 'vue';

import type { MailingListMessageItem } from './types';

import { formatDate } from '@/Utils/dateTime';

defineProps<{
  messages: MailingListMessageItem[];
}>();

const expanded = ref<number | null>(null);

function safeUrl(value: string | null): string | undefined {
  if (!value) return undefined;
  try {
    const url = new URL(value);
    return ['https:', 'http:'].includes(url.protocol) ? url.href : undefined;
  }
  catch {
    return undefined;
  }
}
</script>
