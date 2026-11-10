import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { LearningImportance, LearningStepKind } from '@/Types/enums';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import LearningStepRow from '../LearningStepRow.vue';
import { step } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage());
  vi.mocked(router.post).mockClear();
});

const render = (data = step(), readonly = false) => mount(LearningStepRow, {
  props: { step: data, readonly },
  global: { stubs: commonStubs },
});

describe('LearningStepRow', () => {
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
    const wrapper = render(step({ source: { type: 'document', url: null, documentId: 1, documentStatus: null, title: 'Hidden' } }));
    expect(wrapper.text()).toContain('learning.path.document_private');
    expect(wrapper.find('a[target="_blank"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('Read the guide');
    wrapper.unmount();
  });

  it('uses the document title as the safe source link and links directly to insights', () => {
    const data = step({ source: { type: 'url', url: 'https://example.test/guide', documentId: null, documentStatus: null, title: null } });
    const wrapper = render(data);
    const source = wrapper.get('a[target="_blank"]');
    expect(source.text()).toBe(data.title);
    expect(source.attributes('href')).toBe(data.source?.url);
    expect(source.attributes('rel')).toBe('noopener noreferrer');
    expect(wrapper.findAll('a').some(link => link.attributes('href')?.endsWith('#insights'))).toBe(true);
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

  it.each(['install_app', 'browser_push', 'meet_institution_contacts', 'check_access', 'review_contacts', null])('keeps rich instructions on the step page: %s', key => {
    const data = step({ key, isSystem: key !== null, audienceNotes: [{ label: 'SPK', description: 'Audience instructions' }] });
    const wrapper = render(data, true);
    expect(wrapper.find('details').exists()).toBe(false);
    expect(wrapper.find('[data-slot="audience-notes"]').exists()).toBe(false);
    expect(wrapper.get('a[aria-label="learning.path.open_step"]').attributes('href')).toMatch(/^\/mocked-route\/learningSteps\.show/);
    expect(wrapper.findComponent({ name: 'LearningStepContent' }).exists()).toBe(false);
    wrapper.unmount();
  });

  it.each([
    { source: { type: 'url' as const, url: 'https://example.test/read', documentId: null, documentStatus: null, title: null } },
    { kind: LearningStepKind.Action, actionUrl: '/mano/profile' },
  ])('puts the primary action label before its arrow and keeps an eye link', overrides => {
    const wrapper = render(step(overrides));
    const action = wrapper.get('[data-slot="learning-step-action"]');
    expect(action.element.firstChild?.textContent?.trim()).toMatch(/learning.path.(read_document|open)/);
    expect(action.element.lastElementChild?.tagName.toLowerCase()).toBe('svg');
    expect(action.classes()).toContain('hover:border-brand');
    expect(wrapper.get('a[aria-label="learning.path.open_step"]').attributes('title')).toBe('learning.path.open_step');
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

 it('uses a document icon and labeled terms without redundant type text', () => {
    const wrapper = mount(LearningStepRow, { props: { step: step({ glossaryTerms: [{ id: 'term-1', term: 'Mandatas', definition: 'Atstovavimo laikotarpis' }] }), showGlossary: true }, global: { stubs: commonStubs } });
    expect(wrapper.find('[aria-label="learning.path.kind_document"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('learning.path.related_terms');
    expect(wrapper.text()).not.toContain('learning.path.kind_task');
    expect(wrapper.text()).not.toContain('learning.path.kind_document');
    wrapper.unmount();
 });

it('clamps the system summary and leaves full instructions on the step page', () => {
  const wrapper = render(step({ key: 'review_contacts', isSystem: true, description: 'Full account instructions.' }));
  expect(wrapper.text()).toContain('learning.system_steps.review_contacts.summary');
  expect(wrapper.text()).not.toContain('Full account instructions.');
  expect(wrapper.find('details').exists()).toBe(false);
  expect(wrapper.find('p.line-clamp-2').exists()).toBe(true);
  wrapper.unmount();
});

it('shows coordinator contacts beside the step without a disclosure', () => {
  const wrapper = mount(LearningStepRow, {
    props: { step: step({ key: 'meet_coordinator', isSystem: true }), coordinators: [{ id: 'coordinator', name: 'Rūta', email: 'ruta@example.test', profile_photo_path: null, duty: 'Koordinatorius', pronouns: {}, use_original_duty_name: true, institutions: [] }] },
    global: { stubs: commonStubs },
  });
  expect(wrapper.find('details').exists()).toBe(false);
  expect(wrapper.get('[data-slot="learning-coordinators"]').text()).toContain('Rūta');
  expect(wrapper.find('a[href="mailto:ruta@example.test"]').exists()).toBe(true);
  wrapper.unmount();
});

