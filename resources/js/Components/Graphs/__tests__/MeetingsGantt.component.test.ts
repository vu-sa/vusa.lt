import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import { scopedGlobals } from '@/tests/helpers/scopedGlobals';

import MeetingsGantt from '../MeetingsGantt.vue';

const { stubGlobal, restoreGlobals } = scopedGlobals();

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const institutions = [
  { id: 'senatas', name: 'VU Senatas', tenant_id: '1', types: [{ id: 'council', title: 'Taryba' }, { id: 'senate', title: 'Senatas' }] },
  { id: 'kolegija', name: 'Studijų kolegija', tenant_id: '1', types: [{ id: 'council', title: 'Taryba' }] },
];

const nextFrame = () => new Promise(resolve => requestAnimationFrame(() => resolve(undefined)));

describe('MeetingsGantt', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'], now: new Date(2026, 10, 26, 12) });
    localStorage.clear();
    stubGlobal('fetch', vi.fn(async () => ({ ok: true, json: async () => ({ success: true, data: [] }) })));
    stubGlobal('ResizeObserver', class { observe() {} disconnect() {} });
  });

  afterEach(() => {
    wrapper?.unmount();
    restoreGlobals();
    vi.useRealTimers();
  });

  async function mountGantt(props: Record<string, unknown> = {}) {
    localStorage.setItem('gantt-settings', JSON.stringify({ groupBy: 'type' }));
    wrapper = mount(MeetingsGantt, {
      attachTo: document.body,
      props: {
        institutions,
        meetings: [
          { id: 'm1', institution_id: 'senatas', start_time: new Date(), completion_status: 'incomplete' },
        ],
        gaps: [],
        ...props,
      },
      global: { stubs: { MeetingsGanttToolbar: true } },
    });
    await flushPromises();
    await nextFrame();
  }

  it('repeats an institution under each of its type sections, with its meetings on each row', async () => {
    await mountGantt();

    const labels = wrapper.findAll('[data-slot="meetings-gantt"] .grid > div').map(el => el.text()).filter(Boolean);
    expect(labels.filter(text => text.includes('VU Senatas'))).toHaveLength(2);
    expect(labels).toContain('Taryba');
    expect(wrapper.findAll('.meeting-icon')).toHaveLength(2);
  });

  it('marks a meeting an open records request asks about and brackets an activity question', async () => {
    const today = new Date();
    const monthAgo = new Date(today.getTime() - 30 * 86400000);
    const inAWeek = new Date(today.getTime() + 7 * 86400000);
    const iso = (date: Date) => date.toISOString().slice(0, 10);

    await mountGantt({
      activityRequests: [
        { institution_id: 'senatas', campaign: 'missing_meetings', period_start: iso(monthAgo), period_end: iso(inAWeek), sent_at: null, expires_at: inAWeek.toISOString(), recipients_count: 1 },
        { institution_id: 'kolegija', campaign: 'activity_confirmation', period_start: iso(monthAgo), period_end: iso(today), sent_at: null, expires_at: inAWeek.toISOString(), recipients_count: 2 },
      ],
    });

    expect(wrapper.findAll('.meeting-request-badge')).toHaveLength(2);
    expect(wrapper.findAll('.activity-request-chip')).toHaveLength(1);
    // The label column says which rows have a request: Senatas twice (two type sections), Kolegija once.
    expect(wrapper.findAll('[data-slot="gantt-row-request"]')).toHaveLength(3);

    await wrapper.setProps({ showActivityRequests: false });
    await nextFrame();

    expect(wrapper.findAll('.meeting-request-badge')).toHaveLength(0);
    expect(wrapper.findAll('.activity-request-chip')).toHaveLength(0);
    expect(wrapper.findAll('[data-slot="gantt-row-request"]')).toHaveLength(0);
  });

  it('fetches a meeting agenda on first hover and shows it in the tooltip', async () => {
    let resolveAgenda: (items: Array<{ id: string; title: string }>) => void = () => {};
    const agendaLoader = vi.fn(() => new Promise<Array<{ id: string; title: string }>>((resolve) => {
      resolveAgenda = resolve;
    }));
    await mountGantt({
      meetings: [{ id: 'm1', institution_id: 'kolegija', start_time: new Date(), agenda_items_count: 2 }],
      agendaLoader,
    });

    wrapper.find('.meeting-icon').element.dispatchEvent(new MouseEvent('mouseenter'));
    const tooltip = () => document.querySelector('.gantt-unified-tooltip')?.textContent ?? '';
    expect(agendaLoader).toHaveBeenCalledWith(expect.objectContaining({ id: 'm1', institution_id: 'kolegija' }));
    expect(tooltip()).toMatch(/agenda_loading|Kraunama darbotvarkė/);

    resolveAgenda([{ id: 'a1', title: 'Studijų programos keitimas' }]);
    await flushPromises();
    expect(tooltip()).toContain('Studijų programos keitimas');
  });

  it('does not stripe a row as having no members while its member history is still loading', async () => {
    await mountGantt({ showDutyMembers: true, dutyMembers: [], inactivePeriods: [], membersPending: ['kolegija'] });

    const striped = wrapper.findAll('.inactive-period').length;
    // Senatas (no members, history loaded) is striped; Studijų kolegija (pending) is not.
    expect(striped).toBeGreaterThan(0);

    await wrapper.setProps({ membersPending: ['kolegija', 'senatas'] });
    await flushPromises();
    await nextFrame();
    expect(wrapper.findAll('.inactive-period')).toHaveLength(0);
  });

  it('makes a meeting a labelled link that opens on Space without scrolling and shows its tooltip on focus', async () => {
    await mountGantt({ meetings: [{ id: 'm1', institution_id: 'kolegija', title: 'Kolegijos posėdis', start_time: new Date() }] });
    const icon = wrapper.find('.meeting-icon').element as SVGGElement;

    expect(icon.getAttribute('role')).toBe('link');
    expect(icon.getAttribute('aria-label')).toContain('Kolegijos posėdis');

    icon.dispatchEvent(new FocusEvent('focus'));
    expect(document.querySelector('.gantt-unified-tooltip')?.textContent).toContain('Kolegijos posėdis');

    stubGlobal('route', (name: string, id?: string) => `/${name}/${id ?? ''}`);
    const space = new KeyboardEvent('keydown', { key: ' ', cancelable: true, bubbles: true });
    icon.dispatchEvent(space);
    expect(space.defaultPrevented).toBe(true);
    expect(router.visit).toHaveBeenCalledWith(expect.stringContaining('m1'));
  });

  it('leaves arrow keys on the zoom slider to the slider', async () => {
    await mountGantt();
    const scroller = wrapper.find('[data-slot="meetings-gantt"] .overflow-auto').element as HTMLElement;
    const slider = document.createElement('span');
    slider.setAttribute('role', 'slider');
    slider.tabIndex = 0;
    wrapper.element.appendChild(slider);
    slider.focus();
    const before = scroller.scrollLeft;

    slider.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));
    expect(scroller.scrollLeft).toBe(before);
  });

  it('moves the label column by scrolling it, so its group headers can stay pinned', async () => {
    await mountGantt();
    const scroller = wrapper.find('[data-slot="meetings-gantt"] .overflow-auto').element as HTMLElement;
    const labels = wrapper.find('[data-slot="meetings-gantt"] > div').element as HTMLElement;
    Object.defineProperty(labels, 'scrollTop', { value: 0, writable: true });

    scroller.scrollTop = 120;
    scroller.dispatchEvent(new Event('scroll'));

    expect(labels.scrollTop).toBe(120);
    expect((labels.querySelector('.grid') as HTMLElement).style.transform).toBe('');
  });
});
