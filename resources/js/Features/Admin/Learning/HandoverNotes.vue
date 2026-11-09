<script setup lang="ts">
import type { HandoverNoteData } from '@/Types/learning';
import { formatDateTime } from '@/Utils/dateTime';
import '@/Components/Discussions/comment-content.css';

defineProps<{ notes: HandoverNoteData[] }>();
</script>

<template>
  <div class="divide-y divide-border" data-slot="handover-notes">
    <article v-for="note in notes" :key="note.id" class="space-y-3 py-5 first:pt-0 last:pb-0">
      <header class="space-y-1">
        <h3 class="break-words text-sm font-semibold">{{ note.duty }}</h3>
        <p class="text-xs text-muted-foreground">
          {{ note.author.name }}<template v-if="note.updatedAt || note.createdAt"> · <time :datetime="(note.updatedAt || note.createdAt)!">{{ formatDateTime(note.updatedAt || note.createdAt) }}</time></template>
        </p>
      </header>
      <div class="comment-content min-w-0 break-words text-sm text-foreground [&_a]:break-all" data-slot="handover-body" v-html="note.body" />
    </article>
  </div>
</template>
