import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import InstitutionRelationGraph from '../InstitutionRelationGraph.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const related = [
  { id: 'i1', name: 'Pirma institucija', direction: 'outgoing', type: 'direct', authorized: true },
  { id: 'i2', name: 'Antra institucija', direction: 'incoming', type: 'direct', authorized: false },
  { id: 'i3', name: 'Trečia institucija', direction: 'sibling', type: 'within-type', authorized: true },
];

const mountGraph = (props: Record<string, unknown> = {}) =>
  mount(InstitutionRelationGraph, {
    props: { centerName: 'Centrinė', related, ...props },
    global: { stubs: { ...commonStubs } },
  });

describe('InstitutionRelationGraph', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders one clickable leaf node per related institution', () => {
    const wrapper = mountGraph();

    expect(wrapper.findAll('g[role="link"]')).toHaveLength(related.length);
  });

  it('caps the number of rendered nodes and shows a "+N" note', () => {
    const many = Array.from({ length: 22 }, (_, i) => ({
      id: `m${i}`,
      name: `Institucija ${i}`,
      direction: 'outgoing',
      type: 'direct',
      authorized: true,
    }));
    const wrapper = mountGraph({ related: many });

    expect(wrapper.findAll('g[role="link"]').length).toBeLessThan(many.length);
    expect(wrapper.text()).toContain('6'); // 22 - 16 capped = +6
  });

  it('renders an arrow marker only for directions that are present', () => {
    const wrapper = mountGraph();
    const markerIds = wrapper.findAll('marker').map(m => m.attributes('id'));

    expect(markerIds).toContain('rel-arrow-outgoing');
    expect(markerIds).toContain('rel-arrow-incoming');
    // sibling links have no arrow marker
    expect(markerIds).not.toContain('rel-arrow-sibling');
  });

  it('shows an edge tooltip on hover and hides it on leave', async () => {
    const wrapper = mountGraph();
    const hitLines = wrapper.findAll('line').filter(l => l.attributes('stroke') === 'transparent');

    expect(hitLines).toHaveLength(related.length);
    expect(wrapper.find('.bg-popover').exists()).toBe(false);

    await hitLines[0].trigger('mouseenter', { clientX: 10, clientY: 10 });
    const tip = wrapper.find('.bg-popover');
    expect(tip.exists()).toBe(true);
    expect(tip.text()).toContain('Pirma institucija');

    await hitLines[0].trigger('mouseleave');
    expect(wrapper.find('.bg-popover').exists()).toBe(false);
  });

  it('navigates to the institution on click', async () => {
    const wrapper = mountGraph();

    await wrapper.findAll('g[role="link"]')[0].trigger('click');

    expect(router.visit).toHaveBeenCalledTimes(1);
    expect(router.visit).toHaveBeenCalledWith(expect.stringContaining('institutions.show'));
  });
});
