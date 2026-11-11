import { useForm } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

import LearningStepGlossaryEditor from '@/Features/Admin/Learning/LearningStepGlossaryEditor.vue';

const stubs = {
  MultiSelect: {
    props: ['modelValue', 'options'],
    emits: ['update:modelValue'],
    template: `
      <div>
        <button
          v-for="option in options"
          :key="option.id"
          type="button"
          :data-testid="\`term-option-\${option.id}\`"
          @click="$emit('update:modelValue', [...modelValue, option])"
        >
          {{ option.term }}
        </button>
      </div>
    `,
  },
};

describe('LearningStepGlossaryEditor', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('emits the selected term_ids payload and submits to knowledgeItems.glossary.sync', async () => {
    const options = [
      { id: 'term-1', term: 'Senatas' },
      { id: 'term-2', term: 'SPK' },
      { id: 'term-3', term: 'Taryba' },
    ];

    const wrapper = mount(LearningStepGlossaryEditor, {
      props: {
        stepId: 'step-99',
        selected: [{ id: 'term-1', term: 'Senatas' }],
        options,
      },
      global: { stubs },
    });

    await wrapper.get('[data-testid="term-option-term-2"]').trigger('click');
    await nextTick();
    await wrapper.get('[data-testid="save-glossary-terms"]').trigger('click');

    expect(wrapper.emitted('save')).toEqual([
      [{ term_ids: ['term-1', 'term-2'] }],
    ]);

    const form = vi.mocked(useForm).mock.results.at(-1)?.value;
    expect(form.term_ids).toEqual(['term-1', 'term-2']);
    expect(form.put).toHaveBeenCalledWith(
      expect.stringContaining('knowledgeItems.glossary.sync'),
      expect.objectContaining({ preserveScroll: true }),
    );
  });
});
