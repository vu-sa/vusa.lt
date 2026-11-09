import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { LearningImportance, LearningStepKind } from '@/Types/enums';
import { createMockPage } from '@/tests/helpers/createMockPage';
import LearningStepRow from '../LearningStepRow.vue';
import { step } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage());
  vi.mocked(router.post).mockClear();
});

describe('LearningStepRow', () => {
  it('only offers skipping when the step is skippable', async () => {
    const wrapper = mount(LearningStepRow, { props: { step: step() } });
    expect(wrapper.get('[data-testid="skip-step"]').text()).toBe('learning.path.skip');
    await wrapper.setProps({ step: step({ skippable: false, importance: LearningImportance.Essential }) });
    expect(wrapper.find('[data-testid="skip-step"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it.each([
    ['pending', 'complete-step', 'done'],
    ['pending', 'skip-step', 'skipped'],
    ['done', 'reopen-step', null],
    ['skipped', 'reopen-step', null],
    ['updated', 'complete-step', 'done'],
  ] as const)('posts %s through %s', async (state, button, status) => {
    const data = step({ state });
    const wrapper = mount(LearningStepRow, { props: { step: data } });
    await wrapper.get(`[data-testid="${button}"]`).trigger('click');
    expect(router.post).toHaveBeenCalledWith(route('learningSteps.progress', data.id), { status }, expect.objectContaining({ preserveScroll: true, preserveState: true }));
    wrapper.unmount();
  });

  it('uses the action confirmation for action steps and explains updated content', async () => {
    const wrapper = mount(LearningStepRow, { props: { step: step({ state: 'updated' }) } });
    expect(wrapper.text()).toContain('learning.path.updated');
    expect(wrapper.get('[data-testid="complete-step"]').text()).toBe('learning.path.read');
    await wrapper.setProps({ step: step({ kind: LearningStepKind.Action }) });
    expect(wrapper.get('[data-testid="complete-step"]').text()).toBe('learning.path.complete');
    wrapper.unmount();
  });

  it('shows hidden documents without a source link', () => {
    const wrapper = mount(LearningStepRow, { props: { step: step({ source: { type: 'document', url: null, documentId: 1, documentStatus: null, title: 'Hidden' } }) } });
    expect(wrapper.text()).toContain('learning.path.document_private');
    expect(wrapper.find('a[target="_blank"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('opens sources safely in a new tab and links directly to insights', () => {
    const wrapper = mount(LearningStepRow, { props: { step: step({ source: { type: 'url', url: 'https://example.test/guide', documentId: null, documentStatus: null, title: null } }) } });
    expect(wrapper.get('a[target="_blank"]').attributes('rel')).toBe('noopener noreferrer');
    expect(wrapper.findAll('a').some(link => link.attributes('href')?.endsWith('?tab=insights'))).toBe(true);
    wrapper.unmount();
  });

  it('keeps readonly paths free of mutation buttons', () => {
    const wrapper = mount(LearningStepRow, { props: { step: step(), readonly: true } });
    expect(wrapper.findAll('button')).toHaveLength(0);
    wrapper.unmount();
  });

  it('shows institution contacts with their role and email', () => {
    const wrapper = mount(LearningStepRow, {
      props: {
        step: step({ key: 'meet_institution_contacts', isSystem: true }),
        institutionContacts: [{ id: 'institution', title: 'SPK', contacts: [{ name: 'Rūta', role: 'Sekretorius', email: 'ruta@example.test' }] }],
      },
    });
    expect(wrapper.text()).toContain('SPK');
    expect(wrapper.text()).toContain('Rūta');
    expect(wrapper.text()).toContain('Sekretorius');
    expect(wrapper.get('a[href="mailto:ruta@example.test"]').text()).toBe('ruta@example.test');
    wrapper.unmount();
  });

  it('explains automatic completion without offering ineffective undo', () => {
    const wrapper = mount(LearningStepRow, { props: { step: step({ state: 'done', autoCompleted: true }) } });
    expect(wrapper.text()).toContain('learning.path.auto');
    expect(wrapper.find('[data-testid="reopen-step"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('prevents duplicate saves and offers retry after a validation failure', async () => {
    const wrapper = mount(LearningStepRow, { props: { step: step() } });
    await wrapper.get('[data-testid="complete-step"]').trigger('click');
    expect(wrapper.get('[data-testid="complete-step"]').attributes('disabled')).toBeDefined();
    const callbacks = vi.mocked(router.post).mock.calls[0]?.[2] as {
      onError: (errors: Record<string, string>) => void;
      onFinish: () => void;
    };
    callbacks.onError({ status: 'Try again' });
    callbacks.onFinish();
    await wrapper.vm.$nextTick();
    expect(wrapper.get('[role="alert"]').text()).toBe('learning.path.save_failed');
    expect(wrapper.get('[data-testid="complete-step"]').attributes('disabled')).toBeUndefined();
    wrapper.unmount();
  });
});
