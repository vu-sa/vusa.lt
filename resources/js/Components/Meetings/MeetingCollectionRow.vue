<template>
  <CollectionRow
    :actions="actions ?? []"
    class="px-3 py-2.5 sm:px-4"
    data-slot="meeting-collection-row"
    :data-meeting-id="meeting.id"
    @select="emit('select', $event)"
  >
    <Link
      :href="route('meetings.show', meeting.id)"
      prefetch
      data-collection-open
      class="group/row block min-w-0 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
    >
      <span class="line-clamp-2 font-bold text-foreground group-hover/row:text-brand">
        {{ institutionName || meeting.title || $t('Be pavadinimo') }}
      </span>
      <span class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground">
        <template v-for="(part, index) in facts" :key="part">
          <span v-if="index > 0" aria-hidden="true">·</span>
          <span>{{ part }}</span>
        </template>
        <template v-if="pinned">
          <span v-if="facts.length > 0" aria-hidden="true">·</span>
          <span class="text-status-info">{{ $t('Ką tik atnaujinta') }}</span>
        </template>
      </span>
    </Link>
    <MeetingOutcome v-if="!compact" :outcome="meeting" class="mt-1.5" />
  </CollectionRow>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import MeetingOutcome from './MeetingOutcome.vue';

import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import { MeetingType } from '@/Types/MeetingType';

const props = defineProps<{
  meeting: MeetingSearchResult;
  actions?: AdminRecordAction[];
  /** Just changed by the user and shown ahead of an index that has not caught up. */
  pinned?: boolean;
  /** Beside the detail card, which already says how it went. */
  compact?: boolean;
}>();

const emit = defineEmits<{ select: [key: string] }>();

const isEmail = computed(() => props.meeting.type === MeetingType.Email);

// Not every meeting is a posėdis (.ai/rules/lang.md): an e-mail one is a decision.
const formatLabel = computed(() => {
  if (isEmail.value) {
    return $t('Sprendimas el. paštu');
  }

  return props.meeting.type === MeetingType.Remote ? $t('Nuotolinis posėdis') : null;
});

const institutionName = computed(() => props.meeting.institution_name_lt || props.meeting.institution_name_en);

// The title carries the date, so the sub-line only adds what tells two meetings apart.
const facts = computed(() => [
  institutionName.value ? props.meeting.title || $t('Be pavadinimo') : null,
  formatLabel.value,
  props.meeting.tenant_shortname,
].filter((part): part is string => Boolean(part)));
</script>
