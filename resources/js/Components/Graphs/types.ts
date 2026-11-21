import type { AgendaItemType, Vote } from '@/Composables/useAgendaItemStyling';

export type RepresentativeActivityCategory = 'today' | 'week' | 'month' | 'stale' | 'never';

/** A meeting's tooltip agenda, fetched on first hover; null when it could not be loaded. */
export type GanttAgendaLoader = (meeting: { id: string | number; institution_id: string | number }) => Promise<Array<{
  id: string;
  title: string;
  type?: AgendaItemType;
  votes?: Vote[];
}> | null>;
