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
    expect(wrapper.findAll('a').some(link => link.attributes('href')?.endsWith('?tab=insights'))).toBe(true);
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

  it.each(['install_app', 'browser_push', 'meet_coordinator', 'meet_institution_contacts', 'check_access', 'review_contacts', 'notification_settings', 'profile_photo', 'orientation', 'follow_institution', 'record_first_meeting', 'read_handover', 'write_handover', null])('only discloses rich system content: %s', key => {
    const wrapper = render(step({ key, isSystem: key !== null }), true);
    expect(wrapper.find('details').exists()).toBe(['install_app', 'browser_push', 'meet_coordinator', 'meet_institution_contacts'].includes(key ?? ''));
    wrapper.unmount();
  });

  it('shows institution contacts with their role and email', () => {
    const wrapper = mount(LearningStepRow, {
      props: {
        step: step({ key: 'meet_institution_contacts', isSystem: true }),
        institutionContacts: [{ id: 'institution', title: 'SPK', contacts: [{ name: 'Rūta', role: 'Sekretorius', email: 'ruta@example.test' }] }],
      },
      global: { stubs: commonStubs },
    });
    expect(wrapper.text()).toContain('SPK');
    expect(wrapper.text()).toContain('Rūta');
    expect(wrapper.text()).toContain('Sekretorius');
    expect(wrapper.get('a[href="mailto:ruta@example.test"]').text()).toBe('ruta@example.test');
    wrapper.unmount();
  });

  it('shows a checked disabled box and accessible auto icon without an undo', async () => {
    const wrapper = render(step({ state: 'done', autoCompleted: true }));
    expect(wrapper.get('[role="checkbox"]').attributes('aria-checked')).toBe('true');
    expect(wrapper.get('[role="checkbox"]').attributes('disabled')).toBeDefined();
    expect(wrapper.get('[role="img"]').attributes('aria-label')).toBe('learning.path.auto_help');
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
