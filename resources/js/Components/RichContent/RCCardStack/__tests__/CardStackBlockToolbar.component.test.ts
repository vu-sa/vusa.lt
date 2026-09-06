import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import CardStackBlockToolbar from '../CardStackBlockToolbar.vue';
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
  RCIcon: {
    props: ['name'],
    template: '<div class="rc-icon-stub" />',
  },
  RCIconSelect: {
    props: ['modelValue', 'allowNone'],
    emits: ['update:modelValue'],
    template: '<div class="rc-icon-select-stub" />',
  },
  Dialog: {
    props: ['open'],
    template: '<div v-if="open" class="dialog-stub"><slot /></div>',
  },
  DialogContent: { template: '<div><slot /></div>' },
  DialogHeader: { template: '<div><slot /></div>' },
  DialogTitle: { template: '<div><slot /></div>' },
};

function makeContent(cardsCount = 1, options: Record<string, unknown> = {}): ContentPart {
  const cards = Array.from({ length: cardsCount }, (_, i) => ({
    icon: `icon-${i + 1}`,
    title: `Card ${i + 1}`,
    description: `Desc ${i + 1}`,
  }));

  return {
    type: 'card-stack',
    json_content: cards,
    options: {
      autoplay: false,
      autoplayDelay: 5000,
      hintText: '',
      ...options,
    },
  };
}

function mountToolbar(content: ContentPart) {
  return mount(CardStackBlockToolbar, {
    props: {
      content,
      blockKey: 'card-stack-1',
      canMoveUp: true,
      canMoveDown: true,
      canDelete: true,
    },
    global: { stubs },
  });
}

describe('CardStackBlockToolbar', () => {
  it('displays card count and add card button', () => {
    const wrapper = mountToolbar(makeContent(2));
    expect(wrapper.text()).toContain('rich-content.cards (2)');
    expect(wrapper.find('[data-rc-toolbar-add-card]').exists()).toBe(true);
  });

  it('clicking add card emits update:content with an extra card', async () => {
    const wrapper = mountToolbar(makeContent(1));
    await wrapper.get('[data-rc-toolbar-add-card]').trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updatedContent = emitted!.at(-1)![0] as ContentPart;
    expect(updatedContent.json_content).toHaveLength(2);
  });

  it('moves card order using move buttons', async () => {
    const wrapper = mountToolbar(makeContent(2));
    const moveDownBtn = wrapper.findAll('button[title="rich-content.move_down"]')[0]!;
    await moveDownBtn.trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updatedContent = emitted!.at(-1)![0] as ContentPart;
    expect(updatedContent.json_content.map((c: any) => c.title)).toEqual(['Card 2', 'Card 1']);
  });

  it('removes a card when cards > 1', async () => {
    const wrapper = mountToolbar(makeContent(2));
    const removeBtn = wrapper.findAll('button[title="rich-content.remove_card"]')[0]!;
    await removeBtn.trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const updatedContent = emitted!.at(-1)![0] as ContentPart;
    expect(updatedContent.json_content).toHaveLength(1);
    expect(updatedContent.json_content[0].title).toBe('Card 2');
  });

  it('renders section toolbar options', () => {
    const wrapper = mountToolbar(makeContent(1));
    expect(wrapper.find('.section-toolbar-stub').exists()).toBe(true);
  });
});
