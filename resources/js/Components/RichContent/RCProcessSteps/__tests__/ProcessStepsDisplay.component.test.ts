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
});

describe('ProcessStepsDisplay — editable (full-screen editor)', () => {
  it('renders step title and text as inline editable', async () => {
    const wrapper = mount(ProcessStepsDisplay, {
      props: {
        element: makeElement([{ title: 'Pirmas žingsnis', text: 'Paaiškinimas' }]),
        editable: true,
      },
    });
    // RCInlineText is lazy-loaded (see ProcessStepsDisplay.vue) — resolving that
    // dynamic import needs a wait before the editable markup it renders exists.
    await waitForSelector(wrapper, '[contenteditable]');

    const editables = wrapper.findAll('[contenteditable]');
    expect(editables.length).toBeGreaterThanOrEqual(2);
    expect(editables[0]?.text()).toBe('Pirmas žingsnis');
    expect(editables[1]?.text()).toBe('Paaiškinimas');
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
    });

    expect(wrapper.find('[data-rc-step-remove]').exists()).toBe(false);
  });

  it('renders add placeholder and emits update:element with a new step on click', async () => {
    const wrapper = mount(ProcessStepsDisplay, {
      props: {
        element: makeElement([{ title: 'Žingsnis 1' }]),
        editable: true,
      },
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
    });

    expect(wrapper.find('[data-rc-step-empty]').exists()).toBe(true);
  });
});
