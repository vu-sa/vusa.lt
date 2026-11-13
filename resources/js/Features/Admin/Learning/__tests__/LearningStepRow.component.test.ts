import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { LearningStepData, KnowledgeItemData } from '@/Types/learning';
import { LearningImportance, LearningStepKind } from '@/Types/enums';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

import LearningStepRow from '../LearningStepRow.vue';

import { step, knowledge } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage());
  vi.mocked(router.post).mockClear();
});

const render = (data: LearningStepData | KnowledgeItemData = step(), readonly = false) => mount(LearningStepRow, {
  props: { step: data, readonly },
  global: { stubs: commonStubs },
});

describe('LearningStepRow', () => {
  it('gives displayed coordinator contacts more bottom space', async () => {
    const wrapper = render(step({ key: 'meet_coordinator' }));
    expect(wrapper.classes()).toContain('pb-3');
    await wrapper.setProps({ coordinators: [{
      id: 'coordinator', name: 'Vardas Pavardė', email: 'coordinator@example.test',
      profile_photo_path: null, duty: 'Koordinatorius', pronouns: {}, use_original_duty_name: false, institutions: [],
    }] });
    expect(wrapper.get('[data-slot="learning-coordinators"]').text()).toContain('Vardas Pavardė');
    expect(wrapper.classes()).toContain('pb-6');
    await wrapper.setProps({ coordinators: [] });
    expect(wrapper.find('[data-slot="learning-coordinators"]').exists()).toBe(false);
    expect(wrapper.classes()).toContain('pb-3');
    wrapper.unmount();
  });

  it('only offers skipping in the row menu when the step is skippable', async () => {
    const wrapper = render();
    await wrapper.get('[data-testid="skip-step"]').trigger('click');
    expect(router.post).toHaveBeenCalledWith(route('learningSteps.progress', 'step-1'), { status: 'skipped' }, expect.any(Object));
    await wrapper.setProps({ step: step({ skippable: false, importance: LearningImportance.Essential }) });
    expect(wrapper.find('[data-testid="skip-step"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it.each([
    ['pending', 'done'], ['done', null], ['skipped', null], ['updated', 'done'],
  ] as const)('posts %s directly through the checkbox', async (state, status) => {
    const data = step({ state });
    const wrapper = render(data);
    await wrapper.get('[role="checkbox"]').trigger('click');
    expect(router.post).toHaveBeenCalledWith(route('learningSteps.progress', data.id), { status }, expect.objectContaining({ preserveScroll: true, preserveState: true }));
    wrapper.unmount();
  });

  it('shows updated content and inline actions without redundant done text', async () => {
    const wrapper = render(step({ state: 'updated' }));
    expect(wrapper.text()).toContain('learning.path.updated');
    await wrapper.setProps({ step: step({ kind: LearningStepKind.Action, actionUrl: '/mano/profile', state: 'done' }) });
    expect(wrapper.get('a[href="/mano/profile"]').text()).toBe('learning.path.open');
    expect(wrapper.text()).not.toContain('Atlikta');
    expect(wrapper.text()).not.toContain('learning.states.done');
    expect(wrapper.find('[role="checkbox"]').attributes('aria-checked')).toBe('true');
    wrapper.unmount();
  });

  it('shows hidden documents without a source link', () => {
    const wrapper = render(knowledge({ source: { type: 'document', url: null, documentId: 1, documentStatus: null, title: 'Hidden' } }));
    expect(wrapper.text()).toContain('learning.path.document_private');
    expect(wrapper.find('a[target="_blank"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('Read the guide');
    wrapper.unmount();
  });

  it('links a knowledge item title to its page and leaves its insights to the article', () => {
    const data = knowledge({ source: { type: 'url', url: 'https://example.test/guide', documentId: null, documentStatus: null, title: null } });
    const wrapper = render(data);
    const links = wrapper.get('[data-slot="step-title-row"]').findAll('a');
    expect(links[0]!.text()).toBe(data.title);
    expect(links[0]!.attributes('href')).toMatch(/^\/mocked-route\/knowledgeItems\.show/);
    expect(links).toHaveLength(1);
    expect(wrapper.get('[data-slot="learning-step-action"]').attributes('href')).toBe(data.source?.url);
    wrapper.unmount();
  });

  it('keeps readonly paths free of mutations', async () => {
    const wrapper = render(step(), true);
    expect(wrapper.get('[role="checkbox"]').attributes('disabled')).toBeDefined();
    await wrapper.get('[role="checkbox"]').trigger('click');
    expect(router.post).not.toHaveBeenCalled();
    expect(wrapper.find('[data-testid="skip-step"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it.each(['install_app', 'browser_push', 'meet_institution_contacts', null])('keeps rich instructions on the step page: %s', (key) => {
    const data = step({ key, isSystem: key !== null, audienceNotes: [{ label: 'SPK', description: 'Audience instructions' }] });
    const wrapper = render(data, true);
    expect(wrapper.find('details').exists()).toBe(false);
    expect(wrapper.find('[data-slot="audience-notes"]').exists()).toBe(false);
    expect(wrapper.findComponent({ name: 'LearningStepContent' }).exists()).toBe(false);
    wrapper.unmount();
  });

  it('puts the primary action label before its arrow and opens documents in a new tab', () => {
    const wrapper = render(knowledge({ source: { type: 'url', url: 'https://example.test/read', documentId: null, documentStatus: null, title: null } }));
    const action = wrapper.get('[data-slot="learning-step-action"]');
    expect(action.element.firstChild?.textContent?.trim()).toBe('learning.path.read_document');
    expect(action.element.lastElementChild?.tagName.toLowerCase()).toBe('svg');
    expect(action.attributes('target')).toBe('_blank');
    wrapper.unmount();
  });

  it('labels an action with its own button text and keeps platform pages in the app', () => {
    const wrapper = render(step({ kind: LearningStepKind.Action, actionUrl: '/mano/profile/notifications', actionLabel: 'Nustatyti pranešimus' }));
    const action = wrapper.get('[data-slot="learning-step-action"]');
    expect(action.text()).toBe('Nustatyti pranešimus');
    expect(action.attributes('target')).toBeUndefined();
    expect(action.find('svg').exists()).toBe(true);
    wrapper.unmount();
  });

  it.each([
    [LearningImportance.Essential, 'bg-brand-fill', 'uppercase'],
    [LearningImportance.Recommended, 'border-border', 'uppercase'],
    [LearningImportance.Optional, 'hover:bg-accent', 'normal-case'],
  ])('weighs the %s action button by importance', (importance, look, voice) => {
    const wrapper = render(step({ kind: LearningStepKind.Action, actionUrl: '/mano/profile', importance }));
    const classes = wrapper.get('[data-slot="learning-step-action"]').classes();
    expect(classes).toContain(look);
    expect(classes).toContain(voice);
    wrapper.unmount();
  });

  it('leaves the description to the step page and has no separate open button', () => {
    const wrapper = render(step({ description: 'Full instructions.' }));
    expect(wrapper.text()).not.toContain('Full instructions.');
    expect(wrapper.find('a[aria-label="learning.path.open_step"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('shows a checked disabled box and accessible auto icon without an undo', async () => {
    const wrapper = render(step({ state: 'done', autoCompleted: true }));
    expect(wrapper.get('[role="checkbox"]').attributes('aria-checked')).toBe('true');
    expect(wrapper.get('[role="checkbox"]').attributes('aria-disabled')).toBe('true');
    expect(wrapper.get('[role="img"][aria-label="learning.path.auto_help"]').attributes('aria-label')).toBe('learning.path.auto_help');
    await wrapper.get('[role="checkbox"]').trigger('click');
    expect(router.post).not.toHaveBeenCalled();
    expect(wrapper.find('[data-testid="skip-step"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('prevents duplicate saves and offers retry after a validation failure', async () => {
    const wrapper = render();
    await wrapper.get('[role="checkbox"]').trigger('click');
    expect(wrapper.get('[role="checkbox"]').attributes('disabled')).toBeDefined();
    const callbacks = vi.mocked(router.post).mock.calls[0]?.[2] as {
      onError: (errors: Record<string, string>) => void;
      onFinish: () => void;
    };
    callbacks.onError({ status: 'Try again' });
    callbacks.onFinish();
    await wrapper.vm.$nextTick();
    expect(wrapper.get('[role="alert"]').text()).toBe('learning.path.save_failed');
    expect(wrapper.get('[role="checkbox"]').attributes('disabled')).toBeUndefined();
    await wrapper.get('[role="checkbox"]').trigger('click');
    expect(router.post).toHaveBeenCalledTimes(2);
    wrapper.unmount();
  });
});

it('uses a document icon without redundant type text, and an article without a source opens its own page', () => {
  const wrapper = mount(LearningStepRow, { props: { step: knowledge() }, global: { stubs: commonStubs } });
  expect(wrapper.find('[aria-label="learning.path.kind_document"]').exists()).toBe(true);
  expect(wrapper.get('[data-slot="learning-step-action"]').attributes('href')).toContain('knowledgeItems.show');
  expect(wrapper.get('[data-slot="learning-step-action"]').attributes('target')).toBeUndefined();
  expect(wrapper.text()).not.toContain('learning.path.kind_task');
  expect(wrapper.text()).not.toContain('learning.path.kind_document');
  wrapper.unmount();
});

it.each(['onError', 'onHttpException', 'onNetworkError'] as const)('rolls back an optimistic tick on %s and allows retry', async (failure) => {
  const wrapper = render();
  await wrapper.get('[role="checkbox"]').trigger('click');
  expect(wrapper.get('[role="checkbox"]').attributes('aria-checked')).toBe('true');
  const callbacks = vi.mocked(router.post).mock.calls[0]![2]! as { onFinish: () => void; onError: () => void; onHttpException: () => void; onNetworkError: () => void };
  const fail = callbacks[failure] as () => void;
  fail();
  callbacks.onFinish!();
  await wrapper.vm.$nextTick();
  expect(wrapper.get('[role="checkbox"]').attributes('aria-checked')).toBe('false');
  expect(wrapper.find('[role="alert"]').exists()).toBe(true);
  await wrapper.get('[role="checkbox"]').trigger('click');
  expect(router.post).toHaveBeenCalledTimes(2);
  wrapper.unmount();
});
