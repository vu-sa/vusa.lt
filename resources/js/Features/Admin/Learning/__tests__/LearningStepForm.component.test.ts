import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

import LearningStepForm from '@/Features/Admin/Learning/LearningStepForm.vue';
import { DocumentStatus, LearningImportance, LearningLayer, LearningStage, LearningStepKind } from '@/Types/enums';
import { commonStubs } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  ConfirmDialog: true,
};

describe('LearningStepForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('swaps title, why and source when switching locale between LT and EN', async () => {
    const wrapper = mount(LearningStepForm, {
      props: {
        step: {
          id: 'step-1',
          title: { lt: 'LT pavadinimas', en: 'EN title' },
          description: { lt: 'LT paaiškinimas', en: 'EN explanation' },
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
            en: { document_id: null, url: 'https://vusa.lt/en-guide' },
          },
          source_documents: {
            lt: { id: 42, title: 'Statutas LT', status: DocumentStatus.Published, date: '2026-01-15' },
          },
        },
      },
      global: { stubs },
    });

    expect(wrapper.get<HTMLInputElement>('[data-testid="step-title"]').element.value).toBe('LT pavadinimas');
    expect(wrapper.get<HTMLTextAreaElement>('[data-testid="step-description"]').element.value).toBe('LT paaiškinimas');
    expect(wrapper.find('[data-testid="selected-document-lt"]').exists()).toBe(true);
    expect(wrapper.get('[data-testid="selected-document-lt"]').text()).toContain('Statutas LT');
    expect(wrapper.find('[data-testid="source-url-en"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('learning_library.fields.source_hint');
    expect(wrapper.text()).not.toContain('learning_library.source.en_optional_hint');

    const localeButtons = wrapper.get('[role="group"][aria-label="Kalba"]').findAll('button');
    await localeButtons[1]!.trigger('click');
    await nextTick();

    expect(wrapper.get<HTMLInputElement>('[data-testid="step-title"]').element.value).toBe('EN title');
    expect(wrapper.get<HTMLTextAreaElement>('[data-testid="step-description"]').element.value).toBe('EN explanation');
    expect(wrapper.find('[data-testid="selected-document-lt"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="source-url-en"]').exists()).toBe(true);
    expect(wrapper.get<HTMLInputElement>('[data-testid="source-url-en"]').element.value).toBe('https://vusa.lt/en-guide');
    expect(wrapper.text()).toContain('learning_library.source.en_optional_hint');

    await wrapper.get('[data-testid="step-title"]').setValue('Updated EN title');
    await wrapper.get('[data-testid="step-description"]').setValue('Updated EN explanation');
    await wrapper.get('[data-testid="source-url-en"]').setValue('https://vusa.lt/en-updated');

    await localeButtons[0]!.trigger('click');
    await nextTick();

    expect(wrapper.get<HTMLInputElement>('[data-testid="step-title"]').element.value).toBe('LT pavadinimas');
    expect(wrapper.get<HTMLTextAreaElement>('[data-testid="step-description"]').element.value).toBe('LT paaiškinimas');
    expect(wrapper.find('[data-testid="selected-document-lt"]').exists()).toBe(true);

    await localeButtons[1]!.trigger('click');
    await nextTick();

    expect(wrapper.get<HTMLInputElement>('[data-testid="step-title"]').element.value).toBe('Updated EN title');
    expect(wrapper.get<HTMLTextAreaElement>('[data-testid="step-description"]').element.value).toBe('Updated EN explanation');
    expect(wrapper.get<HTMLInputElement>('[data-testid="source-url-en"]').element.value).toBe('https://vusa.lt/en-updated');
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

  it('shows only the tenant selector for a padalinys curator and limits system steps to read-only title plus stage, order and active', () => {
    const curatorWrapper = mount(LearningStepForm, {
      props: {
        layers: [LearningLayer.Tenant],
        tenants: [{ id: 1, shortname: 'MIF' }],
      },
      global: { stubs },
    });

    expect(curatorWrapper.find('[data-testid="step-layer-tenant"]').exists()).toBe(false);
    expect(curatorWrapper.get<HTMLSelectElement>('select#step-tenant').element.value).toBe('1');

    const systemWrapper = mount(LearningStepForm, {
      props: {
        step: {
          id: 'sys-1',
          key: 'check_access',
          title: { lt: '', en: '' },
          description: { lt: '', en: '' },
          kind: LearningStepKind.Action,
          layer: LearningLayer.Platform,
          stage: LearningStage.BeforeStart,
          importance: LearningImportance.Essential,
          institution_type_id: null,
          duty_type_id: null,
          tenant_id: null,
          per_term: false,
          order: 2,
          is_active: true,
          is_system: true,
          sources: {},
        },
      },
      global: { stubs },
    });

    expect(systemWrapper.get('[data-testid="system-step-title"]').text()).toBe('learning.system_steps.check_access.title');
    expect(systemWrapper.find('[data-testid="step-title"]').exists()).toBe(false);
    expect(systemWrapper.find('[data-testid="panel-who"]').exists()).toBe(false);
    expect(systemWrapper.find('[data-testid="step-importance-essential"]').exists()).toBe(false);
    expect(systemWrapper.find('[data-testid="step-per-term"]').exists()).toBe(false);
    expect(systemWrapper.find('[data-testid="step-stage-before_start"]').exists()).toBe(true);
    expect(systemWrapper.find('[data-testid="step-order"]').exists()).toBe(true);
    expect(systemWrapper.find('[data-testid="step-is-active"]').exists()).toBe(true);
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
