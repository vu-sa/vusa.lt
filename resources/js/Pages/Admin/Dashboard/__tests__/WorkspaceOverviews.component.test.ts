import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import ShowOrganizacija from '@/Pages/Admin/Dashboard/ShowOrganizacija.vue';
import ShowSistema from '@/Pages/Admin/Dashboard/ShowSistema.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string, params: Record<string, unknown> = {}) => {
  const query = Object.entries(params).map(([key, value]) => `${key}=${value}`).join('&');

  return `/mocked/${name}${query ? `?${query}` : ''}`;
});

const mountPage = (component: object, props: Record<string, unknown>) =>
  mount(component, { props, global: { stubs: { ...commonStubs } } });

const numbers = (wrapper: ReturnType<typeof mount>) =>
  Object.fromEntries(wrapper.findAll('[data-number]').map(link => [link.attributes('data-number'), link.attributes('href')]));

describe('ShowOrganizacija overview', () => {
  const counts = { endingSoon: 2, emptyDuties: 1, duties: 40, members: 120 };
  const ending = { id: 'a', duty_id: 'd1', duty: 'Pirmininkas', user: 'Jonas', ends_on: '2026-10-01' };

  it('links every number to the page that lists what it counts', () => {
    expect(numbers(mountPage(ShowOrganizacija, { counts, endingTerms: [ending], recentlyEdited: [] }))).toEqual({
      ending: '/mocked/dutiables.timeline',
      empty: '/mocked/duties.index',
      duties: '/mocked/duties.index',
      members: '/mocked/users.index',
    });
  });

  it('lists the terms that end soon, each opening its duty', () => {
    const wrapper = mountPage(ShowOrganizacija, { counts, endingTerms: [ending], recentlyEdited: [] });

    expect(wrapper.find('[data-slot="ending-terms"] a').attributes('href')).toContain('duties.show');
    expect(wrapper.text()).toContain('Pirmininkas');
    expect(wrapper.text()).toContain('Jonas');
  });

  it('moves the attention band to the status list when no term ends', async () => {
    const wrapper = mountPage(ShowOrganizacija, { counts, endingTerms: [], recentlyEdited: [] });
    await nextTick();

    expect(wrapper.find('[data-slot="ending-terms"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="overview-status-list"]').text()).toContain('organizacija.overview.ending_empty');
  });

  it('draws no number, and no attention band, for what the user may not open', () => {
    const wrapper = mountPage(ShowOrganizacija, {
      counts: { endingSoon: null, emptyDuties: null, duties: null, members: 120 },
      endingTerms: [],
      recentlyEdited: [],
    });

    expect(Object.keys(numbers(wrapper))).toEqual(['members']);
    expect(wrapper.text()).not.toContain('organizacija.overview.ending');
  });
});

describe('ShowSistema overview', () => {
  const counts = { openRequests: 3, queuedMail: 7, roles: 12, users: 900, futureDutyHolders: 8 };

  it('links every number to its list', () => {
    expect(numbers(mountPage(ShowSistema, { counts, newRequests: [], problems: [] }))).toEqual({
      open_requests: '/mocked/mySupportRequests.index?tab=all',
      queued_mail: '/mocked/mailQueue',
      roles: '/mocked/roles.index',
      users: '/mocked/users.index',
      future_duty_holders: '/mocked/users.index?future_duty=scheduled',
    });
  });

  it('says everything runs when no check is failing', () => {
    const wrapper = mountPage(ShowSistema, { counts, newRequests: [], problems: [] });

    expect(wrapper.find('[data-slot="system-ok"]').exists()).toBe(true);
    expect(wrapper.find('[data-slot="system-problems"]').exists()).toBe(false);
  });

  it('names each failing check, in the error colour only when it is an error', () => {
    const wrapper = mountPage(ShowSistema, {
      counts,
      newRequests: [],
      problems: [{ check: 'mail', status: 'error' }, { check: 'digest', status: 'warning' }],
    });

    const [mail, digest] = wrapper.findAll('[data-slot="system-problems"] li');
    expect(mail.classes()).toContain('text-status-danger');
    expect(digest.classes()).toContain('text-status-attention');
  });

  it('lists new support requests, each opening its record', () => {
    const wrapper = mountPage(ShowSistema, {
      counts,
      newRequests: [{ id: 'r1', title: 'Neveikia prisijungimas', reporter: 'Ona', created_at: null }],
      problems: [],
    });

    expect(wrapper.find('[data-slot="new-requests"]').text()).toContain('Neveikia prisijungimas');
  });

  it('hides what a user may not open', () => {
    const wrapper = mountPage(ShowSistema, {
      counts: { openRequests: null, queuedMail: null, roles: null, users: 5, futureDutyHolders: null },
      newRequests: [],
    });

    expect(Object.keys(numbers(wrapper))).toEqual(['users']);
    expect(wrapper.find('[data-slot="system-ok"]').exists()).toBe(false);
  });
});
