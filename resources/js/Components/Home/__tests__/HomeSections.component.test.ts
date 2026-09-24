import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { ClipboardList } from 'lucide-vue-next';

import CoordinatorCard from '../CoordinatorCard.vue';
import FollowedInstitutionsList from '../FollowedInstitutionsList.vue';
import InstitutionsNeedingAttention from '../InstitutionsNeedingAttention.vue';
import RecentlyEditedList from '../RecentlyEditedList.vue';
import UpcomingMeetingsList from '../UpcomingMeetingsList.vue';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { commonStubs } from '@/tests/stubs';

describe('OverviewSection', () => {
  it('collapses an empty section to one line instead of an empty box', () => {
    const wrapper = mount(OverviewSection, {
      props: { title: 'Artimiausi posėdžiai', empty: true, emptyText: 'nieko nesuplanuota' },
      slots: { default: '<ul class="body" />' },
    });

    expect(wrapper.text()).toBe('Artimiausi posėdžiai — nieko nesuplanuota');
    expect(wrapper.find('.body').exists()).toBe(false);
    expect(wrapper.find('h2').exists()).toBe(false);
  });

  it('shows the heading, the body and the way to the full list when there is content', () => {
    const wrapper = mount(OverviewSection, {
      props: { title: 'Artimiausi posėdžiai', href: '/mano/meetings', hrefLabel: 'Visi posėdžiai' },
      slots: { default: '<ul class="body" />' },
    });

    expect(wrapper.find('h2').text()).toBe('Artimiausi posėdžiai');
    expect(wrapper.find('.body').exists()).toBe(true);
    expect(wrapper.find('a').attributes('href')).toBe('/mano/meetings');
  });

  it('adds the home heading treatment only when requested', () => {
    const wrapper = mount(OverviewSection, {
      props: { title: 'Mano užduotys', icon: ClipboardList, variant: 'home' },
      slots: { default: '<p>Turinys</p>' },
    });

    expect(wrapper.find('h2').classes()).toContain('uppercase');
    expect(wrapper.find('h2 svg').exists()).toBe(true);
    expect(wrapper.find('header').classes()).toContain('border-b');
  });
});

describe('CoordinatorCard', () => {
  it('renders nothing when no coordinator is configured', () => {
    expect(mount(CoordinatorCard, { props: { coordinators: [] } }).html()).toBe('<!--v-if-->');
  });

  it('names the coordinator and offers one tap to write to them', () => {
    const wrapper = mount(CoordinatorCard, {
      props: { coordinators: [{ name: 'Jonas Jonaitis', email: 'jonas@vusa.lt', profile_photo_path: null, duty: 'Koordinatorius' }] },
    });

    expect(wrapper.text()).toContain('Tavo koordinatorius');
    expect(wrapper.text()).toContain('Jonas Jonaitis');
    expect(wrapper.text()).toContain('Koordinatorius');
    expect(wrapper.find('a[href="mailto:jonas@vusa.lt"]').text()).toContain('Parašyti');
  });

  it('shows the person without a contact button when they have no email', () => {
    const wrapper = mount(CoordinatorCard, {
      props: { coordinators: [{ name: 'Jonas Jonaitis', email: null, profile_photo_path: null, duty: null }] },
    });

    expect(wrapper.text()).toContain('Jonas Jonaitis');
    expect(wrapper.find('a[href^="mailto:"]').exists()).toBe(false);
  });

  it('names every coordinator, and what each covers, when the rep sits in several padaliniai', () => {
    const wrapper = mount(CoordinatorCard, {
      props: {
        coordinators: [
          { id: '1', name: 'Jonas Jonaitis', email: 'jonas@vusa.lt', profile_photo_path: null, duty: null, institutions: ['MIF SA'] },
          { id: '2', name: 'Ona Onaitė', email: 'ona@vusa.lt', profile_photo_path: null, duty: null, institutions: ['FF SA', 'FF taryba'] },
        ],
      },
    });

    expect(wrapper.text()).toContain('Tavo koordinatoriai');
    expect(wrapper.findAll('a[href^="mailto:"]')).toHaveLength(2);
    expect(wrapper.text()).toContain('FF SA, FF taryba');
  });

  it('keeps a single coordinator free of the coverage line', () => {
    const wrapper = mount(CoordinatorCard, {
      props: { coordinators: [{ id: '1', name: 'Jonas Jonaitis', email: null, profile_photo_path: null, duty: null, institutions: ['MIF SA'] }] },
    });

    expect(wrapper.text()).toContain('Tavo koordinatorius');
    expect(wrapper.text()).not.toContain('MIF SA');
  });
});

describe('RecentlyEditedList', () => {
  it('leaves no trace, not even an all-clear line, when the person has changed nothing', () => {
    const wrapper = mount(RecentlyEditedList, { props: { records: [] } });

    expect(wrapper.text()).toBe('');
  });

  it('lists records with their entity mark, linking to where they open', () => {
    const wrapper = mount(RecentlyEditedList, {
      props: { records: [{ type: 'meeting', id: 'm1', title: 'Senato posėdis', href: '/mano/meetings/m1', changed_at: new Date().toISOString() }] },
    });

    const link = wrapper.find('a');
    expect(link.attributes('href')).toBe('/mano/meetings/m1');
    expect(link.text()).toContain('Senato posėdis');
    expect(link.find('[data-slot="entity-type-mark"]').attributes('data-entity-type')).toBe('meeting');
  });
});

describe('UpcomingMeetingsList', () => {
  it('collapses to one line with nothing scheduled', () => {
    const wrapper = mount(UpcomingMeetingsList, { props: { meetings: [] } });

    expect(wrapper.text()).toContain('artimiausiu metu nieko nesuplanuota');
  });

  it('links each meeting to its record', () => {
    const wrapper = mount(UpcomingMeetingsList, {
      props: { meetings: [{ id: 'm1', title: 'Senato posėdis', start_time: '2026-09-25T07:00:00Z', institution_name: 'Senato atstovai' }] },
    });

    expect(wrapper.find('ul a').attributes('href')).toContain('meetings.show');
    expect(wrapper.text()).toContain('Senato atstovai');
  });

  const meeting = (index: number, isFollowed = false) => ({
    id: `m${index}`,
    title: `Posėdis ${index}`,
    start_time: '2026-09-25T07:00:00Z',
    institution_name: 'Senato atstovai',
    is_followed: isFollowed,
  });

  it('shows three and offers the rest in a dialog only when there are more', async () => {
    const three = mount(UpcomingMeetingsList, { props: { meetings: [1, 2, 3].map(index => meeting(index)) }, global: { stubs: commonStubs } });
    expect(three.find('[data-slot="upcoming-meetings-more"]').exists()).toBe(false);

    const five = mount(UpcomingMeetingsList, {
      props: { meetings: [1, 2, 3, 4, 5].map(index => meeting(index)), total: 12 },
      global: { stubs: commonStubs },
    });
    expect(five.findAll('[data-slot="upcoming-meetings"] li')).toHaveLength(3);
    await five.find('[data-slot="upcoming-meetings-more"]').trigger('click');
    // The server total, not only the rows it sent.
    expect(five.text()).toContain('· 12');
    expect(five.findAll('[data-slot="upcoming-meetings-dialog-list"] li')).toHaveLength(5);
  });

  it('marks a meeting reached only through a follow', () => {
    const wrapper = mount(UpcomingMeetingsList, { props: { meetings: [meeting(1, true), meeting(2)] } });

    expect(wrapper.findAll('[data-slot="upcoming-meeting-followed"]')).toHaveLength(1);
  });
});

describe('FollowedInstitutionsList', () => {
  it('lists the followed institutions and links to all of them', () => {
    const wrapper = mount(FollowedInstitutionsList, {
      props: {
        followed: {
          items: [{ id: 'i1', name: 'Senatas', is_muted: true, activity_status: 'healthy' }],
          total: 12,
        },
      },
    });

    expect(wrapper.text()).toContain('Senatas');
    expect(wrapper.find('[aria-label="Pranešimai nutildyti"]').exists()).toBe(true);
    expect(wrapper.find('header a').attributes('href')).toContain('institutions.index');
  });
});

describe('InstitutionsNeedingAttention', () => {
  const institution = {
    id: 'i1',
    name: 'Senato atstovai',
    status: 'overdue' as const,
    requires_action: true,
    priority: 3,
    periodicity_days: 30,
    effective_days_since_activity: 45,
    progress_percentage: null,
    last_activity_type: 'meeting' as const,
    last_activity_at: null,
    last_meeting_at: null,
    next_meeting_at: null,
    active_check_in_until: null,
  };

  it('marks an overdue institution with its canonical status and offers to record a meeting for it', async () => {
    const wrapper = mount(InstitutionsNeedingAttention, { props: { institutions: [institution] } });

    expect(wrapper.find('[data-slot="status-badge"]').attributes('data-status-role')).toBe('danger');

    await wrapper.find('button').trigger('click');
    expect(wrapper.emitted('record')).toEqual([[institution]]);
  });

  it('collapses to one line when every institution is on time', () => {
    const wrapper = mount(InstitutionsNeedingAttention, { props: { institutions: [] } });

    expect(wrapper.find('ul').exists()).toBe(false);
    expect(wrapper.text()).toContain('Tavo institucijos');
  });

  it('shows only the first rows of a long list and opens the rest in a searchable dialog', async () => {
    const institutions = Array.from({ length: 7 }, (_, i) => ({ ...institution, id: `i${i}`, name: `Institucija ${i}` }));
    const wrapper = mount(InstitutionsNeedingAttention, { props: { institutions, limit: 5 }, global: { stubs: commonStubs } });
    const dialogList = () => wrapper.get('[data-slot="institutions-needing-attention-dialog-list"]');

    expect(wrapper.findAll('[data-slot="institutions-needing-attention"]')[0]!.findAll('li')).toHaveLength(5);

    await wrapper.get('[data-slot="institutions-needing-attention-more"]').trigger('click');
    expect(dialogList().findAll('li')).toHaveLength(7);

    await wrapper.get('input[type="search"]').setValue('Institucija 6');
    expect(dialogList().findAll('li')).toHaveLength(1);
  });

  it('closes the dialog before recording a meeting from it', async () => {
    const institutions = Array.from({ length: 7 }, (_, i) => ({ ...institution, id: `i${i}` }));
    const wrapper = mount(InstitutionsNeedingAttention, { props: { institutions, limit: 5 }, global: { stubs: commonStubs } });

    await wrapper.get('[data-slot="institutions-needing-attention-more"]').trigger('click');
    await wrapper.get('[data-slot="institutions-needing-attention-dialog-list"] button').trigger('click');

    expect(wrapper.emitted('record')).toEqual([[institutions[0]]]);
    expect(wrapper.find('[data-slot="institutions-needing-attention-dialog-list"]').exists()).toBe(false);
  });

  it('offers no dialog when the list fits', () => {
    const wrapper = mount(InstitutionsNeedingAttention, { props: { institutions: [institution], limit: 5 } });

    expect(wrapper.find('[data-slot="institutions-needing-attention-more"]').exists()).toBe(false);
  });

  it('names the padalinys when the row carries one', () => {
    const wrapper = mount(InstitutionsNeedingAttention, { props: { institutions: [{ ...institution, tenant_name: 'VU SA MIF' }] } });

    expect(wrapper.text()).toContain('VU SA MIF');
  });
});
