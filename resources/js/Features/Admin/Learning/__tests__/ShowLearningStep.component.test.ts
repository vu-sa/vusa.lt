import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { beforeEach, expect, it, vi } from 'vitest';

import ShowLearningStep from '@/Pages/Admin/Learning/ShowLearningStep.vue';
import { enterLearningStep } from '@/Composables/useRecordTrail';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import { step } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useRecordTrail');

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage());
  vi.mocked(router.post).mockClear();
  vi.mocked(enterLearningStep).mockClear();
});

function render(inPath = true, canUpdate = false) {
  return mount(ShowLearningStep, {
    props: { step: step(), inPath, canUpdate, coordinators: [], institutionContacts: [], glossaryOptions: [] },
    global: { stubs: {
      ...commonStubs,
      DiscussionPanel: true,
      Sheet: commonStubs.Dialog, SheetContent: commonStubs.DialogContent,
      SheetHeader: commonStubs.DialogHeader, SheetTitle: commonStubs.DialogTitle,
    } },
  });
}

it('puts completion beside the title and insights immediately after the full instructions', async () => {
  const wrapper = render();
  expect(wrapper.findAll('h1')).toHaveLength(1);
  expect(wrapper.get('h1').text()).toBe('Read the guide');
  const heading = wrapper.get('[data-slot="step-heading"]');
  expect(heading.element.firstElementChild?.getAttribute('data-slot')).toBe('learning-completion-control');
  expect(heading.find('h1').exists()).toBe(true);
  expect(wrapper.get('#insights h2').text()).toBe('learning.path.insights');
  expect(wrapper.text()).toContain('Prepare to represent students.');
  expect(wrapper.find('[role="tablist"]').exists()).toBe(false);
  await wrapper.get('[role="checkbox"]').trigger('click');
  expect(router.post).toHaveBeenCalledWith(route('learningSteps.progress', 'step-1'), { status: 'done' }, expect.any(Object));
  expect(enterLearningStep).toHaveBeenCalledWith({ id: 'step-1', title: 'Read the guide' });
  await wrapper.setProps({ step: step({ id: 'second', title: 'Second step' }) });
  expect(enterLearningStep).toHaveBeenLastCalledWith({ id: 'second', title: 'Second step' });
  wrapper.unmount();
});

it('keeps out-of-path completion and manager actions unavailable to readers', async () => {
  const wrapper = render(false);
  expect(wrapper.get('[role="checkbox"]').attributes('disabled')).toBeDefined();
  await wrapper.get('[role="checkbox"]').trigger('click');
  expect(router.post).not.toHaveBeenCalled();
  expect(wrapper.find('[data-action="edit"]').exists()).toBe(false);
  expect(wrapper.find('[data-action="glossary"]').exists()).toBe(false);
  expect(wrapper.find('[data-slot="step-glossary-editor"]').exists()).toBe(false);
  wrapper.unmount();
});

it('opens the glossary editor in a sheet through the manager menu', async () => {
  const wrapper = render(true, true);
  expect(wrapper.find('[data-slot="step-glossary-editor"]').exists()).toBe(false);
  expect(wrapper.get('[data-action="edit"] a').attributes('href')).toBe(route('learningSteps.edit', 'step-1'));
  await wrapper.get('[data-action="glossary"]').trigger('click');
  expect(wrapper.get('[role="dialog"]').text()).toContain('learning.path.manage_terms');
  expect(wrapper.find('[role="dialog"] [data-slot="learning-step-glossary-editor"]').exists()).toBe(true);
  wrapper.unmount();
});
