import { mount } from '@vue/test-utils';
import { useForm, usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import StepSheetForm from '@/Features/Admin/Goals/StepSheetForm.vue';
import type { GoalStep, StepParent } from '@/Features/Admin/Goals/types';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string, params: Record<string, string> = {}) => `/mocked-route/${name}?${new URLSearchParams(params).toString()}`);

const SheetFormStub = {
  props: ['open', 'title'],
  emits: ['submit', 'cancel', 'update:open'],
  template: '<section v-if="open"><h1>{{ title }}</h1><slot /><button type="button" data-testid="submit" @click="$emit(\'submit\')">save</button></section>',
};

const step: GoalStep = {
  id: 'step-1',
  title: { lt: 'Raštas', en: '' },
  description: null,
  happened_on: '2026-09-01',
  goal_id: 'goal-1',
  problem_id: 'problem-1',
  performers: [{ id: 'user-2', name: 'Kolegė' }],
};

const currentUser = { id: 'user-1', name: 'Aš' };

const mountForm = (parent: StepParent, existing: GoalStep | null = null) => mount(StepSheetForm, {
  props: { open: true, parent, step: existing, linkOptions: [] },
  global: {
    stubs: {
      SheetForm: SheetFormStub,
      MultiLocaleInput: { template: '<div />' },
      DatePicker: { template: '<div />' },
      MultiCollectionSelectDialog: { template: '<div><slot name="trigger" /></div>' },
    },
  },
});

const lastForm = () => vi.mocked(useForm).mock.results.at(-1)?.value;

describe('StepSheetForm.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { user: currentUser } }));
  });

  it('logs a new step under the goal it was opened from', async () => {
    const wrapper = mountForm({ type: 'goal', id: 'goal-1' });

    await wrapper.find('[data-testid="submit"]').trigger('click');

    expect(lastForm().post).toHaveBeenCalledWith('/mocked-route/goals.steps.store?goal=goal-1', expect.any(Object));
  });

  it('edits a step through the problem it was opened from', async () => {
    const wrapper = mountForm({ type: 'problem', id: 'problem-1' }, step);

    await wrapper.find('[data-testid="submit"]').trigger('click');

    expect(lastForm().patch).toHaveBeenCalledWith('/mocked-route/problems.steps.update?problem=problem-1&step=step-1', expect.any(Object));
  });

  it('sends only the link to the other side, never the parent id', async () => {
    const wrapper = mountForm({ type: 'problem', id: 'problem-1' }, step);

    await wrapper.find('[data-testid="submit"]').trigger('click');

    const transform = lastForm().transform.mock.calls[0][0];
    const sent = transform({ title: step.title, happened_on: step.happened_on, goal_id: 'goal-1', problem_id: 'problem-1', url: '  ' });

    expect(sent).toMatchObject({ goal_id: 'goal-1', url: null, performers: ['user-2'] });
    expect(sent).not.toHaveProperty('problem_id');
  });

  it('suggests the recorder as the one who did a new step', async () => {
    const wrapper = mountForm({ type: 'goal', id: 'goal-1' });

    await wrapper.find('[data-testid="submit"]').trigger('click');

    const transform = lastForm().transform.mock.calls[0][0];

    expect(transform({ title: step.title, happened_on: step.happened_on, goal_id: null, problem_id: null, url: '' }).performers)
      .toEqual([currentUser.id]);
  });
});
