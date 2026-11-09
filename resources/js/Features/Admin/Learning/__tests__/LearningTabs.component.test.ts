import { usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { createMockPage } from '@/tests/helpers/createMockPage';
import LearningTabs from '../LearningTabs.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function render(canSeeReps: boolean, url = '/mano/learning') {
  vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url });
  return mount(LearningTabs, {
    props: { canSeeReps },
    global: { stubs: { Link: { props: ['href'], template: '<a :href="href"><slot /></a>' } } },
  });
}

describe('LearningTabs', () => {
  it('hides representatives from members and shows them to coordinators', () => {
    const member = render(false);
    expect(member.findAll('a')).toHaveLength(4);
    member.unmount();
    const coordinator = render(true);
    expect(coordinator.findAll('a')).toHaveLength(5);
    coordinator.unmount();
  });

  it('keeps the library active on a step record', () => {
    const wrapper = render(false, '/mano/learning/steps/01STEP');
    expect(wrapper.get('[aria-current="page"]').attributes('href')).toContain('learningSteps.index');
    expect(wrapper.findAll('[aria-current="page"]')).toHaveLength(1);
    wrapper.unmount();
  });

  it('keeps representatives active on an individual representative path', () => {
    const wrapper = render(true, '/mano/learning/reps/01REP');
    expect(wrapper.get('[aria-current="page"]').attributes('href')).toContain('learningPath.reps');
    wrapper.unmount();
  });
});
