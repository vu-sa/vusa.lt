import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import LearningAudienceSheet from '../LearningAudienceSheet.vue';
import { audience, editStep, editKnowledge } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import KnowledgeItemForm from '@/Features/Admin/Learning/KnowledgeItemForm.vue';
import { KnowledgeItemKind, DocumentStatus, LearningAudienceType, LearningImportance, LearningStage, LearningStepKind } from '@/Types/enums';
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

describe('KnowledgeItemForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('swaps title, why and source when switching locale between LT and EN', async () => {
    const wrapper = mount(KnowledgeItemForm, {
      props: {
        item: {
          id: 'step-1',
          title: { lt: 'LT pavadinimas', en: 'EN title' },
          description: { lt: 'LT paaiškinimas', en: 'EN explanation' },
          abbreviation: { lt: '', en: '' },
          summary: { lt: 'LT santrauka', en: '' },
          kind: KnowledgeItemKind.Term,
          knowledge_section_id: null,
          tenant_id: null,
          audiences: [audience()],
          is_active: true,
          counts_in_progress: true,
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

  it('keeps both source drafts when switching and submits only the active source', async () => {
    const wrapper = mount(KnowledgeItemForm, {
      props: {
        item: {
          id: 'step-1',
          title: { lt: 'Žingsnis', en: 'Step' },
          description: { lt: '', en: '' },
          abbreviation: { lt: '', en: '' },
          summary: { lt: 'LT santrauka', en: '' },
          kind: KnowledgeItemKind.Term,
          knowledge_section_id: null,
          tenant_id: null,
          audiences: [audience()],
          is_active: true,
          counts_in_progress: true,
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
    const form = vi.mocked(useForm).mock.results[0]!.value;
    await wrapper.findComponent({ name: 'FormPage' }).vm.$emit('submit');
    const payload = form.transform.mock.calls[0]![0];
    expect(payload(form).sources.lt).toEqual({ document_id: null, url: 'https://vusa.lt/statutas' });
    await wrapper.get('[data-testid="source-mode-lt-document"]').trigger('click');
    await nextTick();

    expect(wrapper.find('[data-testid="source-url-lt"]').exists()).toBe(false);
    expect(wrapper.get('[data-testid="selected-document-lt"]').text()).toContain('Statutas');
    expect(payload(form).sources.lt).toEqual({ document_id: 42, url: null });
    await wrapper.get('[data-testid="source-mode-lt-url"]').trigger('click');
    expect(wrapper.get<HTMLInputElement>('[data-testid="source-url-lt"]').element.value).toBe('https://vusa.lt/statutas');
    await wrapper.get('[data-testid="source-mode-lt-document"]').trigger('click');
    await wrapper.get('[data-testid="clear-document-lt"]').trigger('click');
    await wrapper.get('[data-testid="source-mode-lt-url"]').trigger('click');
    await wrapper.get('[data-testid="source-mode-lt-document"]').trigger('click');
    expect(wrapper.find('[data-testid="selected-document-lt"]').exists()).toBe(false);
    wrapper.unmount();
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

    const wrapper = mount(KnowledgeItemForm, {
      props: {},
      global: { stubs },
    });

    expect(wrapper.find('[data-testid="hidden-document-warning-lt"]').exists()).toBe(false);

    await wrapper.get('[data-testid="document-search-input-lt"]').trigger('focus');
    await flushPromises();

    await wrapper.get('[data-testid="document-hit-lt-7"]').trigger('click');
    await nextTick();

    expect(wrapper.find('[data-testid="hidden-document-warning-lt"]').exists()).toBe(true);
    expect(wrapper.get('[data-testid="hidden-document-warning-lt"]').text()).toBe('learning_library.source.hidden_document_warning');

    await wrapper.get('[data-testid="source-mode-lt-url"]').trigger('click');
    await wrapper.get('[data-testid="source-mode-lt-document"]').trigger('click');
    expect(wrapper.get('[data-testid="selected-document-lt"]').text()).toContain('Paslėptas reglamentas');
    expect(vi.mocked(useForm).mock.results[0]!.value.sources.lt.document_id).toBe(7);

    await wrapper.get('[data-testid="clear-document-lt"]').trigger('click');
    await nextTick();

    expect(wrapper.find('[data-testid="hidden-document-warning-lt"]').exists()).toBe(false);
  });
  it('opens an English-only link on "Nuoroda" in Lithuanian too, and keeps it on save', async () => {
    const wrapper = mount(KnowledgeItemForm, {
      props: { item: editKnowledge({ sources: { en: { url: 'https://vusa.lt/en-only', document_id: null } } }) },
      global: { stubs },
    });
    expect(wrapper.find('[data-testid="source-url-lt"]').exists()).toBe(true);
    const form = vi.mocked(useForm).mock.results[0]!.value;
    await wrapper.findComponent({ name: 'FormPage' }).vm.$emit('submit');
    const payload = form.transform.mock.calls[0]![0](form);
    expect(payload.sources).toEqual({ en: { document_id: null, url: 'https://vusa.lt/en-only' } });
    wrapper.unmount();
  });

  it('turns a link pasted into the document search into a link source', async () => {
    const wrapper = mount(KnowledgeItemForm, { props: { item: editKnowledge({ sources: {} }) }, global: { stubs } });
    await wrapper.get('[data-testid="document-search-input-lt"]').setValue('https://e-seimas.lrs.lt/akt');
    expect(wrapper.get<HTMLInputElement>('[data-testid="source-url-lt"]').element.value).toBe('https://e-seimas.lrs.lt/akt');
    wrapper.unmount();
  });

  it('submits the article kind, sources and section without a stage', async () => {
    const wrapper = mount(KnowledgeItemForm, {
      props: { item: editKnowledge({ sources: { lt: { url: 'https://example.test/read', document_id: null } } }), canManageAudiences: true },
      global: { stubs },
    });
    const form = vi.mocked(useForm).mock.results[0]!.value;
    expect(wrapper.get('[data-slot="knowledge-audience-hint"]').text()).toBe('learning_library.sections.path_description');
    await wrapper.findComponent({ name: 'FormPage' }).vm.$emit('submit');
    const payload = form.transform.mock.calls[0]![0](form);
    expect(payload.kind).toBe('term');
    expect(payload).not.toHaveProperty('order');
    expect(payload.sources.lt.url).toBe('https://example.test/read');
    expect(payload.audiences[0]).not.toHaveProperty('stage');
    expect(form.put).toHaveBeenCalledWith(route('knowledgeItems.update', 'knowledge-1'), expect.any(Object));
    wrapper.unmount();
  });

  it('sends the article fields and leaves audiences to those who build paths', async () => {
    const wrapper = mount(KnowledgeItemForm, {
      props: { item: editKnowledge({ abbreviation: { lt: 'SKVC', en: '' }, tenant_id: 3 }), pageTenants: [{ id: 3, shortname: 'VU SA MIF' }] },
      global: { stubs },
    });
    expect(wrapper.find('[data-testid="step-audiences"]').exists()).toBe(false);
    expect(wrapper.get<HTMLTextAreaElement>('[data-testid="article-summary"]').element.value).toBe('LT santrauka');
    const form = vi.mocked(useForm).mock.results[0]!.value;
    await wrapper.findComponent({ name: 'FormPage' }).vm.$emit('submit');
    const payload = form.transform.mock.calls[0]![0](form);
    expect(payload).not.toHaveProperty('audiences');
    expect(payload.abbreviation.lt).toBe('SKVC');
    expect(payload.summary.lt).toBe('LT santrauka');
    expect(payload.tenant_id).toBe(3);
    wrapper.unmount();
  });
  it('preserves global scope when a single-tenant coordinator edits an article', () => {
    const wrapper = mount(KnowledgeItemForm, {
      props: { item: editKnowledge({ tenant_id: null }), canPublishEverywhere: false, pageTenants: [{ id: 3, shortname: 'VU SA MIF' }] },
      global: { stubs },
    });
    const form = vi.mocked(useForm).mock.results[0]!.value;
    expect(form.tenant_id).toBeNull();
    wrapper.unmount();
  });

});
