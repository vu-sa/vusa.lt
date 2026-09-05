import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import EventCalendarElement from '../EventCalendarElement.vue';
import { resolveBand } from '@/Components/RichContent/bandLayout';
import type { Calendar } from '@/Types/contentParts';

/**
 * The calendar band's title used to be a hardcoded string, ignoring `element.json_content.title`
 * entirely — this covers the fix (the field now actually renders) alongside the new full-screen
 * editable title. How it *looks* is Storybook's job; jsdom cannot resolve Tailwind's `dark:` variant.
 */
function makeElement(title = ''): { json_content: Calendar['json_content']; options: Calendar['options'] } {
  return { json_content: { title }, options: null };
}

function makeEvent(id: number, daysFromNow: number) {
  return {
    id,
    title: `Event ${id}`,
    date: new Date(Date.now() + daysFromNow * 24 * 60 * 60 * 1000).toISOString(),
    end_date: null,
    location: null,
    is_all_day: false,
    category: null,
    images: [],
  };
}

const stubs = {
  SmartLink: { props: ['href'], template: '<a :href="href"><slot /></a>' },
  CalendarSyncModal: { template: '<div />' },
};

describe('EventCalendarElement', () => {
  it('renders the authored title, falling back to the default heading when blank', () => {
    const withTitle = mount(EventCalendarElement, {
      props: { element: makeElement('Šio mėnesio renginiai'), resolved: { type: 'calendar', items: [] } },
      global: { stubs },
    });
    expect(withTitle.find('h2').text()).toBe('Šio mėnesio renginiai');

    const blank = mount(EventCalendarElement, {
      props: { element: makeElement(''), resolved: { type: 'calendar', items: [] } },
      global: { stubs },
    });
    expect(blank.find('h2').text()).toBe('Artimiausi renginiai');
  });

  it('is not editable by default — the title renders as plain text', () => {
    const wrapper = mount(EventCalendarElement, {
      props: { element: makeElement('Renginiai'), resolved: { type: 'calendar', items: [] } },
      global: { stubs },
    });
    expect(wrapper.find('[contenteditable]').exists()).toBe(false);
  });

  it('in full-screen editor mode, editing the title bubbles update:element', async () => {
    const wrapper = mount(EventCalendarElement, {
      props: {
        element: makeElement('Renginiai'),
        resolved: { type: 'calendar', items: [] },
        editable: true,
        blockKey: 'calendar-1',
      },
      global: { stubs },
    });

    const title = wrapper.find('h2 [contenteditable]');
    expect(title.exists()).toBe(true);

    title.element.textContent = 'Artimiausi mūsų renginiai';
    await title.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200)); // RCInlineText's debounce

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as { json_content: Calendar['json_content'] }).json_content.title).toBe('Artimiausi mūsų renginiai');
  });

  it('in full-screen editor mode, editing the eyebrow bubbles update:element', async () => {
    const wrapper = mount(EventCalendarElement, {
      props: {
        element: makeElement('Renginiai'),
        resolved: { type: 'calendar', items: [] },
        editable: true,
        blockKey: 'calendar-1',
      },
      global: { stubs },
    });

    const eyebrow = wrapper.find('[data-slot="eyebrow-label"] [contenteditable]');
    expect(eyebrow.exists()).toBe(true);

    eyebrow.element.textContent = 'Šio mėnesio įvykiai';
    await eyebrow.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200)); // RCInlineText's debounce

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as { json_content: Calendar['json_content'] }).json_content.eyebrow).toBe('Šio mėnesio įvykiai');
  });

  it('renders every server-resolved event, not capped at a hardcoded 5', () => {
    // CalendarBlockResolver now does the "how many, which ones" work server-side via
    // options.limit — the display used to always re-cap to 5 regardless, which made
    // the limit field in CalendarBlockToolbar.vue look like it did nothing.
    const items = Array.from({ length: 7 }, (_, i) => makeEvent(i + 1, i + 1));
    const wrapper = mount(EventCalendarElement, {
      props: { element: makeElement(''), resolved: { type: 'calendar', items } },
      global: { stubs },
    });

    expect(wrapper.findAll('li')).toHaveLength(7);
  });

  it('still drops an event that is fully in the past, as a client-side safety net', () => {
    const past = makeEvent(1, -5);
    const future = makeEvent(2, 5);
    const wrapper = mount(EventCalendarElement, {
      props: { element: makeElement(''), resolved: { type: 'calendar', items: [past, future] } },
      global: { stubs },
    });

    expect(wrapper.findAll('li')).toHaveLength(1);
    expect(wrapper.text()).toContain('Event 2');
    expect(wrapper.text()).not.toContain('Event 1');
  });

  it('renders full-bleed (rc-viewport) by default, when width is unset', () => {
    // `calendar` is `bandRole: 'band'` (Types/index.ts) — the real app always computes
    // `band` via bandLayout.ts's resolveBand()/resolveBands() and passes it down; this
    // mirrors that instead of relying on the component's own (standalone-only) fallback.
    const element = makeElement('');
    const band = resolveBand({ type: 'calendar', options: element.options }, 0);
    const wrapper = mount(EventCalendarElement, {
      props: { element, resolved: { type: 'calendar', items: [] }, band },
      global: { stubs },
    });

    expect(wrapper.get('section').classes()).toContain('rc-viewport');
  });

  it('does not escape to full viewport width when narrowed to content/wide — the width picker must have an effect', () => {
    const content = { json_content: { title: '' }, options: { width: 'content' as const } };
    const contentBand = resolveBand({ type: 'calendar', options: content.options }, 0);
    const contentWrapper = mount(EventCalendarElement, {
      props: { element: content, resolved: { type: 'calendar', items: [] }, band: contentBand },
      global: { stubs },
    });
    expect(contentWrapper.get('section').classes()).not.toContain('rc-viewport');

    const wide = { json_content: { title: '' }, options: { width: 'wide' as const } };
    const wideBand = resolveBand({ type: 'calendar', options: wide.options }, 0);
    const wideWrapper = mount(EventCalendarElement, {
      props: { element: wide, resolved: { type: 'calendar', items: [] }, band: wideBand },
      global: { stubs },
    });
    expect(wideWrapper.get('section').classes()).not.toContain('rc-viewport');
  });

  it('honors presentation: "plain" — no band ground/border at all', () => {
    const element = { json_content: { title: '' }, options: { presentation: 'plain' as const } };
    const band = resolveBand({ type: 'calendar', options: element.options }, 0);
    const wrapper = mount(EventCalendarElement, {
      props: { element, resolved: { type: 'calendar', items: [] }, band },
      global: { stubs },
    });

    const classes = wrapper.get('section').classes();
    expect(classes).not.toContain('rc-viewport');
    expect(classes).not.toContain('rc-band');
  });

  it('re-syncs its display when the resolved payload changes — options changes must not freeze on the first fetch', async () => {
    const element = makeElement('');
    const wrapper = mount(EventCalendarElement, {
      props: { element, resolved: { type: 'calendar', items: [makeEvent(1, 1)] } },
      global: { stubs },
    });
    expect(wrapper.findAll('li')).toHaveLength(1);

    // Simulates the editor re-fetching this block's preview after an options change
    // (e.g. the limit field) and the parent re-rendering with the new payload.
    await wrapper.setProps({ resolved: { type: 'calendar', items: [makeEvent(2, 1), makeEvent(3, 2)] } });

    expect(wrapper.findAll('li')).toHaveLength(2);
  });
});
