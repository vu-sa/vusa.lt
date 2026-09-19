import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import CoordinatorCard from '../CoordinatorCard.vue';
import HomeSection from '../HomeSection.vue';
import InstitutionsNeedingAttention from '../InstitutionsNeedingAttention.vue';
import RecentlyEditedList from '../RecentlyEditedList.vue';
import UpcomingMeetingsList from '../UpcomingMeetingsList.vue';

describe('HomeSection', () => {
  it('collapses an empty section to one line instead of an empty box', () => {
    const wrapper = mount(HomeSection, {
      props: { title: 'Artimiausi posėdžiai', empty: true, emptyText: 'nieko nesuplanuota' },
      slots: { default: '<ul class="body" />' },
    });

    expect(wrapper.text()).toBe('Artimiausi posėdžiai — nieko nesuplanuota');
    expect(wrapper.find('.body').exists()).toBe(false);
    expect(wrapper.find('h2').exists()).toBe(false);
  });

  it('shows the heading, the body and the way to the full list when there is content', () => {
    const wrapper = mount(HomeSection, {
      props: { title: 'Artimiausi posėdžiai', href: '/mano/meetings', hrefLabel: 'Visi posėdžiai' },
      slots: { default: '<ul class="body" />' },
    });

    expect(wrapper.find('h2').text()).toBe('Artimiausi posėdžiai');
    expect(wrapper.find('.body').exists()).toBe(true);
    expect(wrapper.find('a').attributes('href')).toBe('/mano/meetings');
  });
});

describe('CoordinatorCard', () => {
  it('renders nothing when no coordinator is configured', () => {
    expect(mount(CoordinatorCard, { props: { coordinator: null } }).html()).toBe('<!--v-if-->');
  });

  it('names the coordinator and offers one tap to write to them', () => {
    const wrapper = mount(CoordinatorCard, {
      props: { coordinator: { name: 'Jonas Jonaitis', email: 'jonas@vusa.lt', profile_photo_path: null, duty: 'Koordinatorius' } },
    });

    expect(wrapper.text()).toContain('Tavo koordinatorius');
    expect(wrapper.text()).toContain('Jonas Jonaitis');
    expect(wrapper.text()).toContain('Koordinatorius');
    expect(wrapper.find('a[href="mailto:jonas@vusa.lt"]').text()).toContain('Parašyti');
  });

  it('shows the person without a contact button when they have no email', () => {
    const wrapper = mount(CoordinatorCard, {
      props: { coordinator: { name: 'Jonas Jonaitis', email: null, profile_photo_path: null, duty: null } },
    });

    expect(wrapper.text()).toContain('Jonas Jonaitis');
    expect(wrapper.find('a[href^="mailto:"]').exists()).toBe(false);
  });
});

describe('RecentlyEditedList', () => {
  it('hides itself down to one line when the person has changed nothing', () => {
    const wrapper = mount(RecentlyEditedList, { props: { records: [] } });

    expect(wrapper.find('ul').exists()).toBe(false);
    expect(wrapper.text()).toContain('Neseniai redaguota');
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
});
