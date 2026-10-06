import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import ProblemSummaryList, { type ProblemSummary } from '@/Components/Problems/ProblemSummaryList.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string, id?: string) => `/mocked-route/${name}${id ? `/${id}` : ''}`);

const problems: ProblemSummary[] = [
  { id: 'p1', title: 'Bendrabučių vietos', status: 'in_progress', occurred_at: '2026-08-01', tenant: { id: 1, shortname: 'VU SA CHGF' } },
];

describe('ProblemSummaryList.vue', () => {
  it('links each problem to its record with its status', () => {
    const wrapper = mount(ProblemSummaryList, { props: { problems } });

    expect(wrapper.find('a').attributes('href')).toContain('problems.show');
    expect(wrapper.text()).toContain('Bendrabučių vietos');
    expect(wrapper.text()).toContain('Vykdoma');
    expect(wrapper.find('[data-testid="problem-unlink"]').exists()).toBe(false);
  });

  it('offers unlinking only when removable and says which problem', async () => {
    const wrapper = mount(ProblemSummaryList, { props: { problems, removable: true } });

    await wrapper.find('[data-testid="problem-unlink"]').trigger('click');

    expect(wrapper.emitted('remove')?.[0]).toEqual([problems[0]]);
  });
});
