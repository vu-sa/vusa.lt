import { ArrowUpRight, Pencil, Trash2 } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { CollectionColumn } from '@/Components/Collection/types';
import type { CollectionSource } from '@/Composables/useCollectionSource';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { useRowAbilities } from '@/Composables/useRowAbilities';
import { meetingCompletionStatuses, type MeetingCompletionStatus } from '@/Constants/statusIcons';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';

/** The meetings index and every embedded meetings list share these, so a meeting reads the same everywhere. */
export const meetingKey = (meeting: MeetingSearchResult) => String(meeting.id);

// The filter popover and chips name a status exactly as the row badge does (U10).
export const meetingValueLabel = (field: string, value: string) => (
  field === 'completion_status' && value in meetingCompletionStatuses
    ? $t(meetingCompletionStatuses[value as MeetingCompletionStatus].label)
    : undefined
);

export const meetingColumns = (options: { titleLabel?: string } = {}): CollectionColumn[] => [
  { key: 'date', label: $t('Data'), class: 'w-32', sortField: 'start_time' },
  { key: 'title', label: options.titleLabel ?? $t('Institucija') },
  { key: 'status', label: $t('Būsena'), class: 'w-40' },
  { key: 'alignment', label: $t('Balsavimas'), class: 'w-44' },
  { key: 'outcome', label: $t('Klausimai'), class: 'w-32', sortField: 'agenda_items_count' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', sortField: 'created_at', defaultHidden: true },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', sortField: 'updated_at', defaultHidden: true },
];

/** Atidaryti + ⋯ (Redaguoti, Ištrinti), each offered only where the server says the user may. */
export function useMeetingRowActions(
  source: CollectionSource<MeetingSearchResult>,
  visibleIds: () => Array<string | number> = () => source.items.value.map(item => item.id),
) {
  const rowAbilities = useRowAbilities('meetings', visibleIds);

  const actions = useCollectionRecordActions({
    source,
    routePrefix: 'meetings',
    onMutated: id => rowAbilities.invalidate([id]),
    canRestore: () => false,
    canForceDelete: () => false,
  });

  const actionsFor = (meeting: MeetingSearchResult): AdminRecordAction[] => [
    { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, href: route('meetings.show', meeting.id), role: 'open', primary: true },
    ...(rowAbilities.can(meeting.id, 'update') ? [{ key: 'edit', label: $t('Redaguoti'), icon: Pencil, role: 'edit' as const, menuOnly: true, href: route('meetings.edit', { meeting: meeting.id, action: 'edit' }) }] : []),
    ...(rowAbilities.can(meeting.id, 'delete') ? [{ key: `delete:${meeting.id}:${meeting.title}`, label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
  ];

  return { actions, actionsFor, rowAbilities };
}
