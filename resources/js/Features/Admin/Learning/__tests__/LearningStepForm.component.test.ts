import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

import LearningStepForm from '@/Features/Admin/Learning/LearningStepForm.vue';
import { DocumentStatus, LearningImportance, LearningLayer, LearningStage, LearningStepKind } from '@/Types/enums';

const stubs = {
  FormPage: {
    template: '<form @submit.prevent="$emit(\'submit\')"><slot /><slot name="danger" /></form>',
  },
  FormSection: {
    template: '<section><slot /></section>',
  },
  FormFieldWrapper: {
    template: '<div><slot /></div>',
  },
  MultiLocaleInput: {
    props: ['input'],
    template: '<input />',
  },
  ConfirmDialog: true,
};

describe('LearningStepForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('hides stage for meeting_prep and fixes stage to before_start for tenant layer', async () => {
    const wrapper = mount(LearningStepForm, {
      props: {
        tenants: [{ id: 1, shortname: 'MIF' }],
        institutionTypes: [{ id: 10, title: 'Taryba', parent_id: null }],
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-testid="step-stage-before_start"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="stage-fixed-tenant"]').exists()).toBe(false);

    await wrapper.get('[data-testid="step-kind-meeting_prep"]').trigger('click');
    await nextTick();

    expect(wrapper.find('[data-testid="step-stage-before_start"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="stage-fixed-tenant"]').exists()).toBe(false);

    await wrapper.get('[data-testid="step-kind-knowledge"]').trigger('click');
    await wrapper.get('[data-testid="step-layer-tenant"]').trigger('click');
    await nextTick();

    expect(wrapper.find('[data-testid="step-stage-before_start"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="stage-fixed-tenant"]').exists()).toBe(true);
    expect(wrapper.get('[data-testid="stage-fixed-tenant"]').text()).toBe('learning_library.fields.stage_fixed_before_start');
    const tenant = wrapper.get<HTMLSelectElement>('select#step-tenant');
    expect(tenant.element.value).toBe('1');
    expect(tenant.findAll('option').map(option => option.text())).toContain('MIF');
  });

  it('switches source between document and URL and clears the opposite field', async () => {
    const wrapper = mount(LearningStepForm, {
      props: {
        step: {
          id: 'step-1',
          title: { lt: 'Žingsnis', en: 'Step' },
          description: { lt: '', en: '' },
          kind: LearningStepKind.Knowledge,
          layer: LearningLayer.Foundation,
          stage: LearningStage.BeforeStart,
          importance: LearningImportance.Essential,
          institution_type_id: null,
          duty_type_id: null,
          tenant_id: null,
          per_term: false,
          order: 1,
          is_active: true,
          is_system: false,
          sources: {
            lt: { document_id: 42, url: null },
          },
          source_documents: {
            lt: { id: 42, title: 'Statutas', status: DocumentStatus.Published, date: '2026-01-15' },
          },
        },
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-testid="selected-document-lt"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="source-url-lt"]').exists()).toBe(false);

    await wrapper.get('[data-testid="source-mode-lt-url"]').trigger('click');
    await nextTick();

    expect(wrapper.find('[data-testid="selected-document-lt"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="source-url-lt"]').exists()).toBe(true);

    await wrapper.get('[data-testid="source-url-lt"]').setValue('https://vusa.lt/statutas');
    await wrapper.get('[data-testid="source-mode-lt-document"]').trigger('click');
    await nextTick();

    expect(wrapper.find('[data-testid="source-url-lt"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="document-search-input-lt"]').exists()).toBe(true);
  });

  it('shows the hidden-document warning when picking a hidden document and hides it for a published one', async () => {
    vi.mocked(global.fetch).mockResolvedValueOnce({
      ok: true,
      status: 200,
      json: vi.fn().mockResolvedValue([
        { id: 7, title: 'Paslėptas reglamentas', status: DocumentStatus.Hidden, date: '2026-02-01' },
        { id: 8, title: 'Viešas reglamentas', status: DocumentStatus.Published, date: '2026-02-02' },
      ]),
    } as unknown as Response);

    const wrapper = mount(LearningStepForm, {
      global: { stubs },
    });

    expect(wrapper.find('[data-testid="hidden-document-warning-lt"]').exists()).toBe(false);

    await wrapper.get('[data-testid="document-search-input-lt"]').trigger('focus');
    await flushPromises();

    await wrapper.get('[data-testid="document-hit-lt-7"]').trigger('click');
    await nextTick();

    expect(wrapper.find('[data-testid="hidden-document-warning-lt"]').exists()).toBe(true);
    expect(wrapper.get('[data-testid="hidden-document-warning-lt"]').text()).toBe('learning_library.source.hidden_document_warning');

    await wrapper.get('[data-testid="clear-document-lt"]').trigger('click');
    await nextTick();

    expect(wrapper.find('[data-testid="hidden-document-warning-lt"]').exists()).toBe(false);
  });
});
