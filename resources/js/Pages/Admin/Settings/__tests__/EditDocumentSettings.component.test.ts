import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { useForm } from '@inertiajs/vue3';

import type { MockForm } from '@/tests/helpers/createMockForm';

import EditDocumentSettings from '../EditDocumentSettings.vue';

const mountPage = (recommendation: Record<string, unknown> = {}) => mount(EditDocumentSettings, {
  props: {
    canEdit: true,
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

    const form = vi.mocked(useForm).mock.results.at(-1)!.value as unknown as MockForm;
    const submit = vi.fn(() => {
      const transform = form.transform.mock.calls[0]?.[0];
      return transform({
        important_content_types: form.important_content_types,
        recommendations: form.recommendations,
      });
    });
    form.post.mockImplementation(submit);

    await input.setValue(' įstat ,VU SA įstatai, , ');
    await wrapper.get('form').trigger('submit');

    expect(form.post).toHaveBeenCalledExactlyOnceWith('/mocked-route/settings.documents.update');
    expect(submit).toHaveReturnedWith({
      important_content_types: [],
      recommendations: [{ document_id: '1', phrases: ['įstat', 'VU SA įstatai'], enabled: true, show_without_query: true }],
    });
  });

  it('dims a recommendation paused through the inverted checkbox', async () => {
    const wrapper = mountPage();
    const pause = wrapper.findAll('label').find(label => label.text().includes('settings.document_settings.pause'))!;
    await pause.get('button[role="checkbox"]').trigger('click');
    expect(wrapper.get('[data-testid="document-recommendation"]').classes()).toContain('opacity-60');
  });
});
