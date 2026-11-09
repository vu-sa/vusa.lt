import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createMockPage } from '@/tests/helpers/createMockPage';
import LearningPathView from '../LearningPathView.vue';
import { path } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => vi.mocked(usePage).mockReturnValue(createMockPage()));

describe('LearningPathView', () => {
  it('shows every station and its rows without disclosures', () => {
    const wrapper = mount(LearningPathView, { props: { path: path() } });
    expect(wrapper.findAll('[data-stage]')).toHaveLength(4);
    expect(wrapper.get('[data-slot="current-stage"]').text()).toBe('learning.path.now');
    expect(wrapper.get('[data-slot="current-stage"]').attributes('aria-current')).toBe('step');
    expect(wrapper.findAll('details')).toHaveLength(0);
    expect(wrapper.findAll('[data-step]')).toHaveLength(3);
    expect(wrapper.text()).toContain('Pagrindas');
    expect(wrapper.text()).toContain('1/1');
    wrapper.unmount();
  });

  it('keeps meeting preparation a plain list without progress controls', () => {
    const wrapper = mount(LearningPathView, { props: { path: path() } });
    const preparation = wrapper.get('[data-slot="meeting-prep"]');
    expect(preparation.text()).toContain('SPK');
    expect(preparation.text()).toContain('Read the agenda');
    expect(preparation.findAll('button, input')).toHaveLength(0);
    wrapper.unmount();
  });

  it('shows an empty state when there are no steps', () => {
    const data = path();
    data.stages.forEach(stage => { stage.steps = []; });
    const wrapper = mount(LearningPathView, { props: { path: data } });
    expect(wrapper.get('[data-slot="empty-state"]').text()).toContain('learning.path.empty_title');
    wrapper.unmount();
  });
});
