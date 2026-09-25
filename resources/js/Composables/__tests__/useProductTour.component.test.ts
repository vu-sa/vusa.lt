import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import { driver, type Config } from 'driver.js';

import { useProductTour } from '../useProductTour';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('driver.js', () => ({ driver: vi.fn(() => ({ drive: vi.fn(), destroy: vi.fn() })) }));
vi.mock('../useApi', () => ({
  useApiMutation: vi.fn(() => ({ execute: vi.fn().mockResolvedValue(undefined) })),
}));

// jsdom lays nothing out; a rendered element is one that reports a box.
function anchor(name: string, rendered: boolean): HTMLElement {
  const el = document.createElement('div');
  el.dataset.tour = name;
  el.getClientRects = () => (rendered ? [{}] : []) as unknown as DOMRectList;
  document.body.appendChild(el);
  return el;
}

function startWith(steps: Config['steps']) {
  const Host = defineComponent({
    setup() {
      const tour = useProductTour({ tourId: 'test-tour-v1', steps: steps! });
      tour.startTour(true);
      return () => null;
    },
  });
  mount(Host);

  return vi.mocked(driver).mock.calls.at(-1)?.[0]?.steps ?? [];
}

afterEach(() => {
  document.body.innerHTML = '';
  vi.mocked(driver).mockClear();
});

describe('useProductTour anchors', () => {
  it('highlights the rendered copy of an anchor that exists in both the phone and desktop chrome', () => {
    anchor('search', false);
    const shown = anchor('search', true);

    const steps = startWith([{ element: '[data-tour="search"]', popover: { title: 'Search' } }]);

    expect(steps.map(step => step.element)).toEqual([shown]);
  });

  it('skips a step whose anchor is missing or hidden, and keeps one without an anchor', () => {
    anchor('hidden', false);

    const steps = startWith([
      { popover: { title: 'Welcome' } },
      { element: '[data-tour="hidden"]', popover: { title: 'Hidden' } },
      { element: '[data-tour="missing"]', popover: { title: 'Missing' } },
    ]);

    expect(steps.map(step => step.popover?.title)).toEqual(['Welcome']);
  });
});
