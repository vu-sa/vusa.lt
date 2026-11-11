import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import LearningAudienceSheet from '../LearningAudienceSheet.vue';
import { audience, editStep, editKnowledge } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import LearningStepForm from '@/Features/Admin/Learning/LearningStepForm.vue';
import { DocumentStatus, LearningAudienceType, LearningImportance, LearningStage, LearningStepKind } from '@/Types/enums';
import { commonStubs } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  Sheet: commonStubs.Dialog,
  SheetContent: commonStubs.DialogContent,
  SheetHeader: commonStubs.DialogHeader,
  SheetTitle: commonStubs.DialogTitle,
  SheetDescription: commonStubs.DialogDescription,
  ConfirmDialog: true,
  // The rich editor needs a real browser; a textarea keeps the per-locale binding testable.
  TiptapEditor: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<textarea :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
  },
};

describe('LearningStepForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('adds an audience through the sheet and submits the audience payload', async () => {
    const wrapper = mount(LearningStepForm, {
      props: { audienceTypes: [LearningAudienceType.Everyone], canTargetEverywhere: true },
      global: { stubs },
    });
    const stepForm = vi.mocked(useForm).mock.results[0]!.value;
    await wrapper.get('[data-testid="add-audience"]').trigger('click');
    const sheet = wrapper.getComponent(LearningAudienceSheet);
    expect(sheet.props('open')).toBe(true);
    await sheet.findComponent({ name: 'SheetForm' }).vm.$emit('submit');
    await nextTick();
    expect(wrapper.findAll('[data-slot="audience-row"]')).toHaveLength(1);
    expect(wrapper.get('[data-slot="audience-row"]').text()).toContain('learning.audience_types.everyone');
    await wrapper.findComponent({ name: 'FormPage' }).vm.$emit('submit');
    const transform = stepForm.transform.mock.calls[0]![0];
    const payload = transform(stepForm);
    expect(payload.audiences).toEqual([{
      type: LearningAudienceType.Everyone, source_id: null, tenant_id: null,
      importance: LearningImportance.Essential, stage: LearningStage.BeforeStart,
      description: { lt: '', en: '' }, order: 0,
    }]);
    expect(stepForm.post).toHaveBeenCalled();
    wrapper.unmount();
  });

  it('labels a new targeted audience and shows collection and per-row validation errors', async () => {
    const wrapper = mount(LearningStepForm, {
      props: {
        audienceTypes: [LearningAudienceType.InstitutionType],
        institutionTypes: [{ id: 10, title: 'Taryba', parent_id: null }],
        tenants: [{ id: 1, shortname: 'MIF' }],
      }, global: { stubs },
    });
    const stepForm = vi.mocked(useForm).mock.results[0]!.value;
    await wrapper.get('[data-testid="add-audience"]').trigger('click');
    const sheet = wrapper.getComponent(LearningAudienceSheet);
    await sheet.get('select#audience-target').setValue('10');
    await sheet.get('#audience-description-lt').setValue('Aptark pasiūlymus');
    await sheet.findComponent({ name: 'SheetForm' }).vm.$emit('submit');
    await nextTick();
    expect(wrapper.get('[data-slot="audience-row"]').text()).toContain('learning.audience_types.institution_type: Taryba · MIF');
    stepForm.errors = { audiences: 'Pridėk auditoriją', 'audiences.0.source_id': 'Pasirink kitą instituciją' };
    await nextTick();
    expect(wrapper.get('[data-testid="step-audiences"]').text()).toContain('Pridėk auditoriją');
    expect(wrapper.get('[data-slot="audience-row"]').text()).toContain('Pasirink kitą instituciją');
    wrapper.unmount();
  });

  it('clears stages for meeting prep and keeps knowledge fields off step forms', async () => {
    const wrapper = mount(LearningStepForm, {
      props: { step: editStep({ audiences: [audience({ type: LearningAudienceType.InstitutionType, source_id: '10' })] }), institutionTypes: [{ id: 10, title: 'Taryba', parent_id: null }] },
      global: { stubs },
    });
    const stepForm = vi.mocked(useForm).mock.results[0]!.value;
    expect(wrapper.find('[data-testid="step-section"]').exists()).toBe(false);
    await wrapper.get('[data-testid="step-kind-meeting_prep"]').trigger('click');
    expect(wrapper.find('[data-testid="step-section"]').exists()).toBe(false);
    await wrapper.get('[data-slot="audience-row"] button').trigger('click');
    const sheet = wrapper.getComponent(LearningAudienceSheet);
    expect(sheet.find('[data-testid="audience-stage"]').exists()).toBe(false);
    await wrapper.findComponent({ name: 'FormPage' }).vm.$emit('submit');
    const payload = stepForm.transform.mock.calls[0]![0](stepForm);
    expect(payload.audiences[0].stage).toBeNull();
    expect(payload).not.toHaveProperty('knowledge_section_id');
    expect(payload).not.toHaveProperty('sources');
    await wrapper.get('[data-testid="step-kind-action"]').trigger('click');
    expect(wrapper.find('[data-testid="step-section"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="step-kind-knowledge"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="step-section"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('offers an action button only for tasks and submits its text and link per language', async () => {
    const wrapper = mount(LearningStepForm, {
      props: { step: editStep({ kind: LearningStepKind.Action, action_label: { lt: 'Atverti', en: '' }, action_url: { lt: '/mano/profile', en: '' } }) },
      global: { stubs },
    });
    const stepForm = vi.mocked(useForm).mock.results[0]!.value;
    expect(wrapper.get<HTMLInputElement>('[data-testid="step-action-url"]').element.value).toBe('/mano/profile');
    await wrapper.get('[data-testid="step-action-label"]').setValue(' Nustatyti pranešimus ');
    await wrapper.findComponent({ name: 'FormPage' }).vm.$emit('submit');
    const payload = stepForm.transform.mock.calls[0]![0](stepForm);
    expect(payload.action_label).toEqual({ lt: 'Nustatyti pranešimus', en: '' });
    expect(payload.action_url).toEqual({ lt: '/mano/profile', en: '' });
    await wrapper.get('[data-testid="step-kind-meeting_prep"]').trigger('click');
    expect(wrapper.find('[data-testid="step-action-url"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('locks system audiences and submits only their id, stage and order', async () => {
    const wrapper = mount(LearningStepForm, {
      props: { step: editStep({ key: 'install_app', is_system: true, kind: LearningStepKind.Action, audiences: [audience({ type: LearningAudienceType.Purpose, label: 'Atstovas' })] }) },
      global: { stubs },
    });
    const stepForm = vi.mocked(useForm).mock.results[0]!.value;
    expect(wrapper.get('[data-testid="system-step-title"]').text()).toBe('learning.system_steps.install_app.title');
    expect(wrapper.find('[data-testid="step-title"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="add-audience"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="audience-row"]').findAll('button')).toHaveLength(1);
    await wrapper.get('[data-slot="audience-row"] button').trigger('click');
    const sheet = wrapper.getComponent(LearningAudienceSheet);
    expect(sheet.find('#audience-type').exists()).toBe(false);
    expect(sheet.find('#audience-importance').exists()).toBe(false);
    expect(sheet.find('#audience-description-lt').exists()).toBe(false);
    await sheet.get('#audience-stage').setValue(LearningStage.TermEnd);
    await sheet.get('#audience-order').setValue(3);
    await sheet.findComponent({ name: 'SheetForm' }).vm.$emit('submit');
    await nextTick();
    await wrapper.findComponent({ name: 'FormPage' }).vm.$emit('submit');
    const payload = stepForm.transform.mock.calls[0]![0](stepForm);
    expect(payload).toEqual({ audiences: [{ id: 'audience-1', stage: LearningStage.TermEnd, order: 3 }], order: 1, is_active: true });
    wrapper.unmount();
  });

  it('keeps a purpose target fixed while allowing its explanation and importance to change', async () => {
    const wrapper = mount(LearningStepForm, {
      props: { step: editStep({ audiences: [audience({ type: LearningAudienceType.Purpose, label: 'Atstovas' })] }) },
      global: { stubs },
    });
    await wrapper.get('[data-slot="audience-row"] button').trigger('click');
    const sheet = wrapper.getComponent(LearningAudienceSheet);
    expect(sheet.find('#audience-type').exists()).toBe(false);
    expect(sheet.find('#audience-importance').exists()).toBe(true);
    await sheet.get('#audience-description-lt').setValue('Tau svarbu');
    await sheet.findComponent({ name: 'SheetForm' }).vm.$emit('submit');
    await nextTick();
    expect(wrapper.get('[data-slot="audience-row"]').text()).toContain('Tau svarbu');
    wrapper.unmount();
  });

});
