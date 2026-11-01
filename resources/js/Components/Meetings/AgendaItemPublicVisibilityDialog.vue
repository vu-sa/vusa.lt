<template>
  <Dialog :open @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-lg">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Globe class="size-4" aria-hidden="true" />
          {{ $t('meetings.visibility.title') }}
        </DialogTitle>
        <DialogDescription class="mt-2 text-sm text-muted-foreground">
          {{ $t(`meetings.visibility.item.intro.${introKey}`) }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-5 pt-2 text-sm">
        <section data-slot="visibility-shown">
          <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
            {{ $t(shownHeading) }}
          </h3>
          <ul class="space-y-1.5">
            <li v-for="key in shownKeys" :key class="flex items-start gap-2">
              <Eye :class="['mt-0.5 size-4 shrink-0', published ? 'text-status-success' : 'text-status-info']" aria-hidden="true" />
              <span>{{ $t(`meetings.visibility.item.shown.${key}`) }}</span>
            </li>
          </ul>
        </section>

        <section data-slot="visibility-never">
          <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
            {{ $t('meetings.visibility.never_heading') }}
          </h3>
          <ul class="space-y-1.5">
            <li v-for="key in neverKeys" :key class="flex items-start gap-2">
              <EyeOff class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
              <span>{{ $t(`meetings.visibility.item.never.${key}`) }}</span>
            </li>
          </ul>
        </section>

        <p class="text-xs text-muted-foreground">
          {{ $t('meetings.privacy.documents_hint') }}
        </p>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, EyeOff, Globe } from '@lucide/vue';
import { DialogDescription } from 'reka-ui';

import { isShownPublicly, type MeetingPublicVisibility } from '@/Components/Meetings/meetingPublicVisibility';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

const props = defineProps<{
  open: boolean;
  mode: MeetingPublicVisibility['mode'];
  isPrivate: boolean;
}>();

defineEmits<{
  'update:open': [value: boolean];
}>();

const published = computed(() => isShownPublicly({ mode: props.mode, url: null }));

const introKey = computed(() => {
  if (props.isPrivate) {
    return 'private';
  }

  return published.value ? props.mode : 'unpublished';
});

const shownHeading = computed(() => {
  if (!published.value) {
    return 'meetings.visibility.item.would_show_heading';
  }

  return props.mode === 'calendar_event' ? 'meetings.visibility.shown_event_heading' : 'meetings.visibility.shown_heading';
});

// The shown list follows PublicAgendaItemRow, and for a private item AgendaItemPresenter::redacted().
const shownKeys = computed(() => (props.isPrivate
  ? ['position', 'public_title']
  : ['title', 'status', 'description', 'student_position', 'time', 'votes', 'brought_by_students']));

const neverKeys = computed(() => (props.isPrivate
  ? ['content', 'outcome', 'notes', 'comments', 'problems', 'history']
  : ['vote_notes', 'notes', 'comments', 'problems', 'history']));
</script>
