import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// The shared mock's form is a plain object; the row needs to re-render when a checkbox changes.
vi.mock('@inertiajs/vue3', async () => {
  const [mock, { reactive }] = await Promise.all([import('@/mocks/inertia.mock'), import('vue')]);
  return { ...mock, useForm: vi.fn((data: Record<string, unknown>) => reactive(mock.useForm(data))) };
});

import { useForm } from '@inertiajs/vue3';

import EditDocumentSettings from '../EditDocumentSettings.vue';

const mountPage = (recommendation: Record<string, unknown> = {}) => mount(EditDocumentSettings, {
  props: {
    selected_content_types: [],
    available_content_types: [],
    recommendations: [{ document_id: '1', phrases: ['įstatai', 'VU SA įstatai'], enabled: true, show_without_query: true, ...recommendation }],
    selected_documents: [{ id: '1', title: 'VU SA Įstatai', is_active: true }],
  },
  global: {
    stubs: {
      FormPage: { template: '<form @submit.prevent="$emit(\'submit\')"><slot /></form>', emits: ['submit'] },
      CollectionSelectDialog: true,
      SpotlightPopover: { template: '<div><slot /></div>' },
      MultiSelect: true,
    },
  },
});

describe('EditDocumentSettings', () => {
  it('edits phrases as one comma-separated line and saves them as a trimmed list', async () => {
    const wrapper = mountPage();
    const input = wrapper.get<HTMLInputElement>('#recommendation-phrases-0');
    expect(input.element.value).toBe('įstatai, VU SA įstatai');

    await input.setValue(' įstat ,VU SA įstatai, , ');
    await wrapper.get('form').trigger('submit');
    const transform = vi.mocked(useForm).mock.results.at(-1)!.value.transform.mock.calls[0][0];
    expect(transform({ important_content_types: [], recommendations: [{ document_id: '1', phrases_text: input.element.value, enabled: true, show_without_query: true }] }).recommendations)
      .toEqual([{ document_id: '1', phrases: ['įstat', 'VU SA įstatai'], enabled: true, show_without_query: true }]);
  });

  it('dims a recommendation paused through the inverted checkbox', async () => {
    const wrapper = mountPage();
    const pause = wrapper.findAll('label').find(label => label.text().includes('settings.document_settings.pause'))!;
    await pause.get('button[role="checkbox"]').trigger('click');
    expect(wrapper.get('[data-testid="document-recommendation"]').classes()).toContain('opacity-60');
  });
});
