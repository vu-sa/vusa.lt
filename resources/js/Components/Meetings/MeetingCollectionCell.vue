<template>
  <span v-if="column.key === 'date'" class="tabular-nums">{{ formatDate(new Date((item.start_time ?? 0) * 1000)) }}</span>
  <CollectionPrimaryCell
    v-else-if="column.key === 'title'"
    :match="item._searchMatch"
    :title-matches="item._searchTitleMatches"
    :title="hideInstitution ? item.title : (getSearchTranslation(item, 'institution_name') || item.title)"
    :sub="!hideInstitution && titleOmitsDate(item.title) ? item.title : undefined"
    :title-lines="2"
    :href="linkless ? undefined : route('meetings.show', item.id)"
  />
  <MeetingOutcome v-else-if="column.key === 'status'" :outcome="item" show="completion" show-complete />
  <MeetingOutcome v-else-if="column.key === 'alignment'" :outcome="item" show="alignment" />
  <MeetingOutcome v-else-if="column.key === 'outcome'" :outcome="item" show="counts" />
  <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(timestampOf(column.key)) }}</span>
</template>

<script setup lang="ts">
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import { getSearchTranslation } from '@/Composables/useTranslatedTitle';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import { formatDate, formatRecordDate } from '@/Utils/dateTime';

import MeetingOutcome from './MeetingOutcome.vue';
import { titleOmitsDate } from './meetingTitle';

/** One table cell of a meetings collection (`meetingColumns`). */
const props = defineProps<{
  item: MeetingSearchResult;
  column: CollectionColumn;
  /** Inside the institution's own record, the title column shows the meeting, not the institution. */
  hideInstitution?: boolean;
  /** Trashed rows cannot be opened. */
  linkless?: boolean;
}>();

// The index sends both timestamps, though the shared search type does not declare them.
const timestampOf = (key: 'created_at' | 'updated_at') =>
  (props.item as unknown as Record<string, number | string | null | undefined>)[key];
</script>
