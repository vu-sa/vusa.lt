import { beforeAll, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import { commonStubs } from '@/tests/stubs';

// The zoom Slider measures itself on mount; jsdom ships no ResizeObserver.
class ResizeObserverStub {
  observe() {}
  unobserve() {}
  disconnect() {}
}

beforeAll(() => {
  vi.stubGlobal('ResizeObserver', ResizeObserverStub);
});

const { default: DutiableTimelineToolbar } = await import('../DutiableTimelineToolbar.vue');
const { getTimelineColors } = await import('../timelineColors');

function mountToolbar(overrides: Record<string, unknown> = {}) {
  return mount(DutiableTimelineToolbar, {
    props: {
      scope: { type: 'institution' as const, id: 'inst-1', label: 'Parlamentas' },
      visibleCount: 3,
      includeEnded: true,
      monthWidthPx: 64,
      timelineColors: getTimelineColors(false),
      cadenceOptions: [{ value: 'cad-1', label: '2024–2025', count: 2 }],
      tenantOptions: [],
      cadenceIds: [],
      tenantKeys: [],
      ...overrides,
    },
    global: { stubs: commonStubs },
  });
}

describe('DutiableTimelineToolbar', () => {
  /**
   * "Show ended" persists between visits, so the state that *hides* rows has to be readable
   * from the closed trigger — otherwise you come back to a chart quietly leaving half its
   * rows out. Showing them is the default and hides nothing, so it is not marked.
   */
  it('marks the cadence filter only while ended periods are hidden', () => {
    const hidden = mountToolbar({ includeEnded: false });
    const shown = mountToolbar({ includeEnded: true });

    const indicator = '[aria-label="dutiables.timeline.ended_hidden"]';
    expect(hidden.find(indicator).exists()).toBe(true);
    expect(shown.find(indicator).exists()).toBe(false);
  });

  it('opens the institution the chart is scoped to', () => {
    const link = mountToolbar().find('a');

    expect(link.exists()).toBe(true);
    expect(link.text()).toBe('Parlamentas');
  });

  it('leaves a scope it cannot link to as plain text', () => {
    const wrapper = mountToolbar({ scope: null });

    expect(wrapper.find('a').exists()).toBe(false);
  });
});
