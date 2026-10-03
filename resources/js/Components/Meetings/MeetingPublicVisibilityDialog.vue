<template>
  <Dialog :open @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-lg">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Globe class="size-4" aria-hidden="true" />
          {{ $t('meetings.visibility.title') }}
        </DialogTitle>
        <DialogDescription class="mt-2 text-sm text-muted-foreground">
          {{ isPublic ? $t('meetings.visibility.public_intro') : $t('meetings.visibility.internal_intro') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-5 pt-2 text-sm">
        <section v-if="isPublic" data-slot="visibility-shown">
          <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
            {{ $t('meetings.visibility.shown_heading') }}
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
          {{ $t('meetings.visibility.files_link_note') }}
          <a :href="filesDocsHref" target="_blank" rel="noopener" class="underline underline-offset-4 hover:text-foreground">{{ $t('Plačiau') }}</a>
        </p>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, EyeOff, Globe } from 'lucide-vue-next';
import { DialogDescription } from 'reka-ui';

import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { useDocsHref } from '@/Composables/useDocsHref';

defineProps<{
  open: boolean;
  /** Mirrors Meeting::isPubliclyVisible(); the lists follow ContactController::showMeeting(). */
  isPublic: boolean;
}>();

defineEmits<{
  'update:open': [value: boolean];
}>();

const shownKeys = ['basics', 'agenda', 'representatives', 'documents'] as const;
const neverKeys = ['files', 'tasks', 'comments'] as const;

const filesDocsHref = useDocsHref('/visak/failai');
</script>
