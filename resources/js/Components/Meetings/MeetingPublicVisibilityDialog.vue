<template>
  <Dialog :open @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-lg">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Globe class="size-4" aria-hidden="true" />
          {{ $t('meetings.visibility.title') }}
        </DialogTitle>
        <DialogDescription class="mt-2 text-sm text-muted-foreground">
          {{ $t(INTRO_KEYS[mode]) }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-5 pt-2 text-sm">
        <section v-if="shownKeys.length" data-slot="visibility-shown">
          <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
            {{ mode === 'calendar_event' ? $t('meetings.visibility.shown_event_heading') : $t('meetings.visibility.shown_heading') }}
          </h3>
          <ul class="space-y-1.5">
            <li v-for="key in shownKeys" :key class="flex items-start gap-2">
              <Eye class="mt-0.5 size-4 shrink-0 text-status-success" aria-hidden="true" />
              <span>{{ $t(`meetings.visibility.shown.${key}`) }}</span>
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
              <span>{{ $t(`meetings.visibility.never.${key}`) }}</span>
            </li>
          </ul>
        </section>

        <p class="text-xs text-muted-foreground">
          {{ $t('meetings.privacy.documents_hint') }}
        </p>
        <p class="text-xs text-muted-foreground">
          {{ $t('meetings.visibility.files_link_note') }}
          <a :href="filesDocsHref" target="_blank" rel="noopener" class="underline underline-offset-4 hover:text-foreground">{{ $t('Plačiau') }}</a>
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

import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { useDocsHref } from '@/Composables/useDocsHref';
import type { MeetingPublicVisibility } from '@/Components/Meetings/meetingPublicVisibility';

const props = defineProps<{
  open: boolean;
  mode: MeetingPublicVisibility['mode'];
}>();

defineEmits<{
  'update:open': [value: boolean];
}>();

const INTRO_KEYS = {
  public_page: 'meetings.visibility.public_intro',
  calendar_event: 'meetings.visibility.calendar_event_intro',
  calendar_draft: 'meetings.visibility.calendar_draft_intro',
  internal: 'meetings.visibility.internal_intro',
} as const;

// The lists follow ContactController::showMeeting() and PublicPageController::meetingBehind().
const shownKeys = computed(() => {
  if (props.mode === 'public_page') {
    return ['basics', 'agenda', 'representatives', 'documents'];
  }

  return props.mode === 'calendar_event' ? ['agenda', 'documents'] : [];
});
const neverKeys = ['files', 'tasks', 'comments'] as const;

const filesDocsHref = useDocsHref('/visak/failai');
</script>
