import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import ProcessStepsDisplay from '../ProcessStepsDisplay.vue';

import { waitForSelector } from '@/tests/helpers/waitForSelector';
import type { ProcessSteps } from '@/Types/contentParts';

function makeElement(steps: Partial<ProcessSteps['json_content'][number]>[] = []): ProcessSteps {
  return {
    json_content: steps.map(s => ({ title: '', text: '', ...s })),
    options: { columns: 3 },
  };
}

const editableStubs = {
  TiptapEditor: {
    props: ['modelValue', 'preset', 'toolbar', 'html', 'placeholder'],
    emits: ['update:modelValue'],
    template: `
      <div class="tiptap-editor-stub" :data-preset="preset" :data-toolbar="toolbar">
        <textarea class="stub-input" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" />
      </div>
    `,
  },
};

describe('ProcessStepsDisplay — public (non-editable)', () => {
  it('renders steps as plain, non-editable text', () => {
    const wrapper = mount(ProcessStepsDisplay, {
      props: {
        element: makeElement([
          { title: 'Užpildyk anketą', text: 'Trumpas aprašymas' },
          { title: 'Ateik į pokalbį', text: 'Susipažinsime' },
        ]),
      },
    });

    expect(wrapper.text()).toContain('Užpildyk anketą');
    expect(wrapper.text()).toContain('Trumpas aprašymas');
    expect(wrapper.find('[contenteditable]').exists()).toBe(false);
    expect(wrapper.find('[data-rc-step-remove]').exists()).toBe(false);
    expect(wrapper.find('[data-rc-step-add]').exists()).toBe(false);
  });

  it('renders links and rich HTML in step title and text', () => {
    const wrapper = mount(ProcessStepsDisplay, {
      props: {
        element: makeElement([
          {
            title: 'Žingsnis su <a href="https://vusa.lt">nuoroda</a>',
            text: 'Tekstas su <strong>paryškinimu</strong> ir <a href="https://vu.lt">VU nuoroda</a>',
          },
        ]),
      },
    });

    const link = wrapper.find('a[href="https://vusa.lt"]');
    expect(link.exists()).toBe(true);
    expect(link.text()).toBe('nuoroda');

    const strong = wrapper.find('strong');
    expect(strong.exists()).toBe(true);
    expect(strong.text()).toBe('paryškinimu');

    const vuLink = wrapper.find('a[href="https://vu.lt"]');
    expect(vuLink.exists()).toBe(true);
    expect(vuLink.text()).toBe('VU nuoroda');
  });
});

describe('ProcessStepsDisplay — editable (full-screen editor)', () => {
  it('renders step title and text as Tiptap editors and emits update:element on edit', async () => {
    const wrapper = mount(ProcessStepsDisplay, {
      props: {
        element: makeElement([{ title: 'Pirmas žingsnis', text: 'Paaiškinimas' }]),
        editable: true,
      },
      global: { stubs: editableStubs },
    });

    const titleEditor = wrapper.find('[data-rc-step-title]');
    const textEditor = wrapper.find('[data-rc-step-text]');

    expect(titleEditor.exists()).toBe(true);
    expect(textEditor.exists()).toBe(true);

    expect(titleEditor.attributes('data-preset')).toBe('marks');
    expect(titleEditor.attributes('data-toolbar')).toBe('bubble');
    expect(textEditor.attributes('data-preset')).toBe('marks');
    expect(textEditor.attributes('data-toolbar')).toBe('bubble');

    expect(titleEditor.find('textarea').element.value).toBe('Pirmas žingsnis');
    expect(textEditor.find('textarea').element.value).toBe('Paaiškinimas');

    await titleEditor.find('textarea').setValue('Naujas žingsnis');
    const titleEmitted = wrapper.emitted('update:element');
    expect(titleEmitted).toBeTruthy();
    expect((titleEmitted!.at(-1)![0] as ProcessSteps).json_content[0]?.title).toBe('Naujas žingsnis');

    await textEditor.find('textarea').setValue('Naujas paaiškinimas');
    const textEmitted = wrapper.emitted('update:element');
    expect(textEmitted).toBeTruthy();
    expect((textEmitted!.at(-1)![0] as ProcessSteps).json_content[0]?.text).toBe('Naujas paaiškinimas');
  });

  it('renders delete button when steps > 1 and emits update:element on delete', async () => {
    const wrapper = mount(ProcessStepsDisplay, {
      props: {
        element: makeElement([
          { title: 'Žingsnis 1' },
          { title: 'Žingsnis 2' },
        ]),
        editable: true,
      },
      global: { stubs: editableStubs },
    });

    const removeButtons = wrapper.findAll('[data-rc-step-remove]');
    expect(removeButtons).toHaveLength(2);

    await removeButtons[0]?.trigger('click');
    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const updated = emitted!.at(-1)![0] as ProcessSteps;
    expect(updated.json_content).toHaveLength(1);
    expect(updated.json_content[0]?.title).toBe('Žingsnis 2');
  });

  it('does not render delete button when only 1 step exists', () => {
    const wrapper = mount(ProcessStepsDisplay, {
      props: {
        element: makeElement([{ title: 'Vienintelis žingsnis' }]),
        editable: true,
      },
      global: { stubs: editableStubs },
    });

    expect(wrapper.find('[data-rc-step-remove]').exists()).toBe(false);
  });

  it('renders add placeholder and emits update:element with a new step on click', async () => {
    const wrapper = mount(ProcessStepsDisplay, {
      props: {
        element: makeElement([{ title: 'Žingsnis 1' }]),
        editable: true,
      },
      global: { stubs: editableStubs },
    });
    await waitForSelector(wrapper, '[data-rc-step-add]');

    const addPlaceholder = wrapper.find('[data-rc-step-add]');
    expect(addPlaceholder.exists()).toBe(true);

    await addPlaceholder.trigger('click');
    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const updated = emitted!.at(-1)![0] as ProcessSteps;
    expect(updated.json_content).toHaveLength(2);
    expect(updated.json_content[1]).toEqual({ title: '', text: '' });
  });

  it('renders empty state when steps array is empty', () => {
    const wrapper = mount(ProcessStepsDisplay, {
      props: {
        element: makeElement([]),
        editable: true,
      },
      global: { stubs: editableStubs },
    });

    expect(wrapper.find('[data-rc-step-empty]').exists()).toBe(true);
  });
});
