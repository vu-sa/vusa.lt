import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ref, watch } from 'vue';

import { formatDate } from '@/Utils/dateTime';

export interface TrailCrumb {
  id: string;
  href: string;
  label: string;
  /** The unabbreviated name, for `title` and the accessible name. */
  title: string;
}

interface Trail {
  institution?: TrailCrumb;
  meeting?: TrailCrumb & { institutionIds: string[] };
  agendaItem?: TrailCrumb & { meetingId: string };
}

interface TrailInstitution { id: string | number; name: string }
interface TrailMeeting { id: string | number; start_time: string; institutions?: TrailInstitution[] }
interface TrailAgendaItem { id: string | number; title: string }

/** The record pages that keep the trail; opening any other page forgets it. */
export const TRAIL_PAGES = new Set([
  'Admin/People/ShowInstitution',
  'Admin/Representation/ShowMeeting',
  'Admin/Representation/ShowAgendaItem',
]);

/** Catalog section key → the trail record that section's tab stands in for. */
const SECTION_RECORDS: Record<string, keyof Trail> = {
  institucijos: 'institution',
  posedziai: 'meeting',
  darbotvarkes_klausimai: 'agendaItem',
};

const trail = ref<Trail>({});

const institutionCrumb = (institution: TrailInstitution): TrailCrumb => ({
  id: String(institution.id),
  href: route('institutions.show', { institution: institution.id }),
  label: institution.name,
  title: institution.name,
});

export function enterInstitution(institution: TrailInstitution): void {
  const id = String(institution.id);
  const { meeting, agendaItem } = trail.value;
  const keepsMeeting = meeting?.institutionIds.includes(id) ?? false;

  trail.value = {
    institution: institutionCrumb(institution),
    meeting: keepsMeeting ? meeting : undefined,
    agendaItem: keepsMeeting ? agendaItem : undefined,
  };
}

export function enterMeeting(meeting: TrailMeeting): void {
  const id = String(meeting.id);
  const institutions = meeting.institutions ?? [];
  const institutionIds = institutions.map(institution => String(institution.id));
  const { institution, agendaItem } = trail.value;
  const date = formatDate(meeting.start_time);

  // A joint meeting keeps whichever of its institutions the user came through.
  const parent = institution && institutionIds.includes(institution.id)
    ? institution
    : institutions[0] && institutionCrumb(institutions[0]);

  trail.value = {
    institution: parent,
    meeting: {
      id,
      href: route('meetings.show', { meeting: meeting.id }),
      label: date,
      title: [date, ...institutions.map(item => item.name)].join(' · '),
      institutionIds,
    },
    agendaItem: agendaItem?.meetingId === id ? agendaItem : undefined,
  };
}

export function enterAgendaItem(agendaItem: TrailAgendaItem, position: number, meeting?: TrailMeeting): void {
  if (meeting) {
    enterMeeting(meeting);
  }
  else {
    trail.value = {};
  }

  trail.value = {
    ...trail.value,
    agendaItem: {
      id: String(agendaItem.id),
      href: route('agendaItems.show', { agendaItem: agendaItem.id }),
      label: $t('shell.trail.agenda_item', { position: String(position) }),
      title: agendaItem.title,
      meetingId: meeting ? String(meeting.id) : '',
    },
  };
}

export function clearTrail(): void {
  trail.value = {};
}

/** For the shell: which record a section tab stands in for while the user moves along the trail. */
export function useRecordTrail() {
  const page = usePage();

  watch(() => page.component, (component) => {
    if (!TRAIL_PAGES.has(component)) {
      clearTrail();
    }
  }, { immediate: true });

  const crumbFor = (sectionKey: string): TrailCrumb | undefined => {
    const record = SECTION_RECORDS[sectionKey];

    return record ? trail.value[record] : undefined;
  };

  return { trail, crumbFor };
}
