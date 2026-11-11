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
    props: { step: step(), inPath, canUpdate, coordinators: [], institutionContacts: [] },
    global: { stubs: {
      ...commonStubs,
      Sheet: commonStubs.Dialog, SheetContent: commonStubs.DialogContent,
      SheetHeader: commonStubs.DialogHeader, SheetTitle: commonStubs.DialogTitle,
    } },
  });
}

it('puts completion beside the title and has no insights thread', async () => {
  const wrapper = render();
  expect(wrapper.findAll('h1')).toHaveLength(1);
  expect(wrapper.get('h1').text()).toBe('Read the guide');
  const heading = wrapper.get('[data-slot="step-heading"]');
  expect(heading.element.firstElementChild?.getAttribute('data-slot')).toBe('learning-completion-control');
  expect(heading.find('h1').exists()).toBe(true);
  expect(wrapper.find('#insights').exists()).toBe(false);
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

it('offers only the step edit action to its managers', () => {
  const wrapper = render(true, true);
  expect(wrapper.get('[data-action="edit"] a').attributes('href')).toBe(route('learningSteps.edit', 'step-1'));
  expect(wrapper.find('[data-action="glossary"]').exists()).toBe(false);
  wrapper.unmount();
});

it('places primary and manager actions in the page header beside the heading', async () => {
  const wrapper = render(true, true);
  await wrapper.setProps({ step: step({ actionUrl: '/mano/profile' }) });
  const header = wrapper.get('[data-slot="overview-title-band"]');
  expect(header.find('[data-slot="step-heading"]').exists()).toBe(true);
  expect(header.find('[data-slot="step-actions"]').exists()).toBe(true);
  expect(wrapper.get('[data-slot="step-heading"]').find('[data-slot="step-actions"]').exists()).toBe(false);
  wrapper.unmount();
});

it('renders the step description as rich content', async () => {
  const wrapper = render();
  await wrapper.setProps({ step: step({ description: '<p><strong>Žiūrėk</strong></p><div data-youtube-video><iframe src="https://www.youtube-nocookie.com/embed/abc"></iframe></div>' }) });
  const description = wrapper.get('[data-slot="step-description"]');
  expect(description.find('strong').text()).toBe('Žiūrėk');
  expect(description.find('iframe').attributes('src')).toContain('youtube-nocookie.com/embed/abc');
});
