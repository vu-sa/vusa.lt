import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import InstitutionGraph from '../InstitutionGraph.vue';

import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

const preferences = useAccessibilityPreferences();
let wrapper: ReturnType<typeof mount>;

beforeEach(() => {
  vi.stubGlobal('ResizeObserver', class {
    observe = vi.fn();
    disconnect = vi.fn();
  });
});

afterEach(() => {
  wrapper?.unmount();
  preferences.reset();
  vi.unstubAllGlobals();
});

describe('InstitutionGraph reduced motion', () => {
  it('settles layout before painting and zooms immediately', async () => {
    preferences.reduceMotion.value = true;
    wrapper = mount(InstitutionGraph, {
      props: {
        institutions: [{ id: 1, name: 'A' }, { id: 2, name: 'B' }],
        institutionRelationships: [{ source: 1, target: 2 }],
      },
    });
    const root = wrapper.get('g.zoom-root');
    expect(root.findAll('circle')).toHaveLength(2);
    const positions = root.findAll('circle').map(circle => circle.element.parentElement?.getAttribute('transform'));
    expect(positions.every(Boolean)).toBe(true);

    const svg = wrapper.get('svg').element;
    Object.defineProperty(svg, 'viewBox', { value: { baseVal: { x: -400, y: -300, width: 800, height: 600 } } });
    await wrapper.findAll('button')[0]!.trigger('click');
    expect(root.attributes('transform')).toContain('scale(1.3)');
    expect(root.findAll('circle').map(circle => circle.element.parentElement?.getAttribute('transform'))).toEqual(positions);
  });

  it('settles a running layout when reduced motion is enabled', async () => {
    preferences.reset();
    wrapper = mount(InstitutionGraph, {
      props: { institutions: [{ id: 1, name: 'A' }], institutionRelationships: [] },
    });
    preferences.reduceMotion.value = true;
    await nextTick();
    expect(wrapper.get('circle').element.parentElement?.getAttribute('transform')).toBeTruthy();
  });
});
