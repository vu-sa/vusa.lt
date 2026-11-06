import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import GoalStepList from '../GoalStepList.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('GoalStepList', () => {
  it('keeps the step visible without binding an unsupported reference', () => {
    const wrapper = mount(GoalStepList, {
      props: {
        canUpdate: false,
        otherSide: 'goal',
        steps: [{
          id: 'step-1', title: { lt: 'Susitarimas', en: 'Agreement' }, description: null,
          happened_on: '2026-01-01', goal_id: null, problem_id: null,
          url: 'javascript:void(0)',
        }],
      },
    });

    expect(wrapper.find('[href="javascript:void(0)"]').exists()).toBe(false);
    expect(wrapper.get('[data-testid="goal-step-list"]').text()).toMatch(/Susitarimas|Agreement/);
  });
});
