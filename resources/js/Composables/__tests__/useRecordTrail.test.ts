import { usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope, nextTick, reactive } from 'vue';

import {
  clearTrail,
  enterAgendaItem,
  enterInstitution,
  enterMeeting,
  useRecordTrail,
} from '../useRecordTrail';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const senatas = { id: 'senatas', name: 'Vilniaus universiteto senatas' };
const taryba = { id: 'taryba', name: 'Vilniaus universiteto taryba' };
const meeting = { id: 'm1', start_time: '2026-09-12T10:00:00', institutions: [senatas] };
const jointMeeting = { id: 'm2', start_time: '2026-09-20T10:00:00', institutions: [senatas, taryba] };

const page = reactive({ ...createMockPage(), component: 'Admin/Representation/ShowMeeting' });

function mountTrail() {
  return effectScope().run(() => useRecordTrail())!;
}

describe('useRecordTrail', () => {
  beforeEach(() => {
    clearTrail();
    page.component = 'Admin/Representation/ShowMeeting';
    vi.mocked(usePage).mockReturnValue(page as ReturnType<typeof usePage>);
  });

  it('builds the whole trail walking from an institution down to an agenda item', () => {
    const { crumbFor } = mountTrail();

    enterInstitution(senatas);
    enterMeeting(meeting);
    enterAgendaItem({ id: 'a1', title: 'Dėl studijų programų' }, 3, meeting);

    expect(crumbFor('institucijos')).toMatchObject({ href: '/mocked-route/institutions.show?institution=senatas', label: senatas.name });
    expect(crumbFor('posedziai')).toMatchObject({ href: '/mocked-route/meetings.show?meeting=m1', label: '2026-09-12' });
    expect(crumbFor('darbotvarkes_klausimai')).toMatchObject({ href: '/mocked-route/agendaItems.show?agendaItem=a1', title: 'Dėl studijų programų' });
    expect(crumbFor('apzvalga')).toBeUndefined();
  });

  it('derives the parents when an agenda item is opened directly', () => {
    const { crumbFor } = mountTrail();

    enterAgendaItem({ id: 'a1', title: 'Dėl studijų programų' }, 3, meeting);

    expect(crumbFor('institucijos')?.id).toBe('senatas');
    expect(crumbFor('posedziai')?.id).toBe('m1');
  });

  it('remembers the agenda item after stepping back up to its meeting and institution', () => {
    const { crumbFor } = mountTrail();

    enterAgendaItem({ id: 'a1', title: 'Dėl studijų programų' }, 3, meeting);
    enterMeeting(meeting);
    enterInstitution(senatas);

    expect(crumbFor('posedziai')?.id).toBe('m1');
    expect(crumbFor('darbotvarkes_klausimai')?.id).toBe('a1');
  });

  it('drops the children that do not belong to a newly opened record', () => {
    const { crumbFor } = mountTrail();

    enterAgendaItem({ id: 'a1', title: 'Dėl studijų programų' }, 3, meeting);
    enterMeeting(jointMeeting);

    expect(crumbFor('darbotvarkes_klausimai')).toBeUndefined();

    enterInstitution(taryba);

    expect(crumbFor('posedziai')?.id).toBe('m2');

    enterInstitution({ id: 'kita', name: 'Kita institucija' });

    expect(crumbFor('posedziai')).toBeUndefined();
  });

  it('keeps the institution the user came through on a joint meeting', () => {
    const { crumbFor } = mountTrail();

    enterInstitution(taryba);
    enterMeeting(jointMeeting);

    expect(crumbFor('institucijos')?.id).toBe('taryba');
  });

  it('forgets the trail once the user leaves the record pages', async () => {
    const { crumbFor } = mountTrail();

    enterMeeting(meeting);
    page.component = 'Admin/Representation/IndexMeeting';
    await nextTick();

    expect(crumbFor('posedziai')).toBeUndefined();
    expect(crumbFor('institucijos')).toBeUndefined();
  });
});
