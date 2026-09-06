import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import ProcessStepsBlockToolbar from '../ProcessStepsBlockToolbar.vue';
import type { ContentPart } from '../../Types';

const stubs = {
  RCBlockToolbarShell: {
    props: ['content', 'blockKey', 'reference', 'canMoveUp', 'canMoveDown', 'canDelete'],
    emits: ['move-up', 'move-down', 'delete', 'open-form'],
    template: '<div class="shell-stub"><slot /></div>',
  },
  RCSectionToolbarOptions: {
    props: ['modelValue', 'presentationDisabled'],
    emits: ['update:modelValue'],
    template: '<div class="section-toolbar-stub" />',
  },
  RCWidthPicker: {
    props: ['modelValue', 'allowedWidths'],
    emits: ['update:modelValue'],
    template: '<div class="width-picker-stub" />',
  },
  Dialog: {
    props: ['open'],
    template: '<div v-if="open" class="dialog-stub"><slot /></div>',
  },
  DialogContent: { template: '<div><slot /></div>' },
  DialogHeader: { template: '<div><slot /></div>' },
  DialogTitle: { template: '<div><slot /></div>' },
};

function makeContent(stepsCount = 3, options: Record<string, unknown> = {}): ContentPart {
  const steps = Array.from({ length: stepsCount }, (_, i) => ({
    title: `Žingsnis ${i + 1}`,
    text: `Aprašymas ${i + 1}`,
  }));

  return {
    type: 'process-steps',
    json_content: steps,
    options: {
      columns: 3,
      ...options,
    },
  };
}

function mountToolbar(content: ContentPart) {
  return mount(ProcessStepsBlockToolbar, {
    props: {
      content,
      blockKey: 'steps-1',
      canMoveUp: true,
      canMoveDown: true,
      canDelete: true,
    },
    global: { stubs },
  });
}

describe('ProcessStepsBlockToolbar', () => {
  it('renders steps list with titles and numerals', () => {
    const wrapper = mountToolbar(makeContent(3));
    expect(wrapper.text()).toContain('Žingsnis 1');
    expect(wrapper.text()).toContain('Žingsnis 2');
    expect(wrapper.text()).toContain('Žingsnis 3');
  });

  it('clicking add step emits update:content with a new step', async () => {
    const wrapper = mountToolbar(makeContent(2));
    await wrapper.get('[data-rc-toolbar-add-step]').trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updated = emitted!.at(-1)![0] as ContentPart;
    expect(updated.json_content).toHaveLength(3);
  });

  it('reorders steps when move up or down is clicked', async () => {
    const wrapper = mountToolbar(makeContent(3));
    const moveDownButtons = wrapper.findAll('button[title="rich-content.move_down"]');
    await moveDownButtons[0]?.trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updated = emitted!.at(-1)![0] as ContentPart;
    const jsonContent = updated.json_content as Array<{ title: string; text: string }>;
    expect(jsonContent[0]?.title).toBe('Žingsnis 2');
    expect(jsonContent[1]?.title).toBe('Žingsnis 1');
  });

  it('removes a step when remove button is clicked', async () => {
    const wrapper = mountToolbar(makeContent(3));
    const removeButtons = wrapper.findAll('button[title="rich-content.remove_step"]');
    await removeButtons[0]?.trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updated = emitted!.at(-1)![0] as ContentPart;
    const jsonContent = updated.json_content as Array<{ title: string; text: string }>;
    expect(jsonContent).toHaveLength(2);
    expect(jsonContent[0]?.title).toBe('Žingsnis 2');
  });

  it('shows the width picker and section toolbar options', () => {
    const wrapper = mountToolbar(makeContent(2));
    expect(wrapper.find('.width-picker-stub').exists()).toBe(true);
    expect(wrapper.find('.section-toolbar-stub').exists()).toBe(true);
  });
});
