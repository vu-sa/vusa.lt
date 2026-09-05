import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import CardStackDisplay from '../CardStackDisplay.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../../Editor/Fullscreen/useActiveHotspot';

import { stubPopover, stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs';
import type { CardStack } from '@/Types/contentParts';

function makeElement(cards: Partial<CardStack['json_content'][number]>[] = []): CardStack {
  return {
    json_content: cards.map(card => ({ icon: '', title: '', description: '', ...card })),
    options: { autoplay: false },
  };
}

const popoverStubs = {
  Popover: stubPopover,
  PopoverAnchor: stubPopoverAnchor,
  PopoverContent: stubPopoverContent,
  RCIconSelect: { props: ['modelValue', 'allowNone'], emits: ['update:modelValue'], template: '<div class="rc-icon-select" />' },
};

function mountEditableWithPopover(element: CardStack, blockKey = 'stack-1') {
  const hotspots = useActiveHotspot();
  const wrapper = mount(CardStackDisplay, {
    props: { element, editable: true, blockKey },
    global: { stubs: popoverStubs, provide: { [ACTIVE_HOTSPOT_KEY]: hotspots } },
  });
  return { wrapper, hotspots };
}

describe('CardStackDisplay — public (non-editable)', () => {
  it('renders the front card as plain, non-editable text', () => {
    const wrapper = mount(CardStackDisplay, { props: { element: makeElement([{ title: 'Narystė', description: 'Prisijunk' }]) } });
    expect(wrapper.text()).toContain('Narystė');
    expect(wrapper.text()).toContain('Prisijunk');
    expect(wrapper.find('[contenteditable]').exists()).toBe(false);
  });

  it('renders no add/remove controls', () => {
    const wrapper = mount(CardStackDisplay, { props: { element: makeElement([{ title: 'A' }, { title: 'B' }]) } });
    expect(wrapper.find('[data-rc-card-stack-remove-item]').exists()).toBe(false);
  });
});

describe('CardStackDisplay — editable (full-screen editor)', () => {
  function mountEditable(element: CardStack) {
    return mount(CardStackDisplay, { props: { element, editable: true, blockKey: 'stack-1' } });
  }

  it('renders every card title/description inline-editable', () => {
    const wrapper = mountEditable(makeElement([{ title: 'Narystė', description: 'Prisijunk' }]));
    const editable = wrapper.findAll('[contenteditable]');
    expect(editable).toHaveLength(2);
    expect(editable[0]?.text()).toBe('Narystė');
    expect(editable[1]?.text()).toBe('Prisijunk');
  });

  it('emits update:element with the patched title, preserving description', async () => {
    const wrapper = mountEditable(makeElement([{ title: 'Old', description: 'Kept' }]));
    const title = wrapper.findAll('[contenteditable]')[0]!;
    title.element.textContent = 'New title';
    await title.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200)); // RCInlineText's debounce

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as CardStack;
    expect(patched.json_content[0]?.title).toBe('New title');
    expect(patched.json_content[0]?.description).toBe('Kept');
  });

  it('does not rotate the stack when clicking a card while editable', async () => {
    const wrapper = mountEditable(makeElement([{ title: 'A' }, { title: 'B' }]));
    const cards = wrapper.findAll('.perspective-1000 > div');
    await cards[0]!.trigger('click');

    // Still shows both editable titles for their original cards — no rotation occurred.
    expect(wrapper.findAll('[contenteditable]')[0]?.text()).toBe('A');
  });

  it('shows a remove button only on the current front card, when there is more than one', () => {
    const wrapper = mountEditable(makeElement([{ title: 'A' }, { title: 'B' }]));
    expect(wrapper.findAll('[data-rc-card-stack-remove-item]')).toHaveLength(1);
  });

  it('hides the remove button entirely with a single card', () => {
    const wrapper = mountEditable(makeElement([{ title: 'A' }]));
    expect(wrapper.find('[data-rc-card-stack-remove-item]').exists()).toBe(false);
  });

  it('removes a card and emits update:element without it', async () => {
    const wrapper = mountEditable(makeElement([{ title: 'A' }, { title: 'B' }]));
    await wrapper.get('[data-rc-card-stack-remove-item]').trigger('click');

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as CardStack;
    expect(patched.json_content.map(card => card.title)).toEqual(['B']);
  });

  it('adds a new empty card via the add placeholder', async () => {
    const wrapper = mountEditable(makeElement([{ title: 'A' }]));
    await wrapper.get('[data-rc-interactive]:not([contenteditable])').trigger('click');

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as CardStack;
    expect(patched.json_content).toHaveLength(2);
    expect(patched.json_content[1]?.title).toBe('');
  });
});

describe('CardStackDisplay — icon popover (full-screen editor)', () => {
  it('clicking a card claims its popover hotspot', async () => {
    const { wrapper, hotspots } = mountEditableWithPopover(makeElement([{ title: 'A' }]));

    const card = wrapper.get('.perspective-1000 > div');
    await card.trigger('click');

    expect(hotspots.isPopoverOpen('stack-1:card')).toBe(true);
    await wrapper.vm.$nextTick();
    expect(wrapper.find('.rc-icon-select').exists()).toBe(true);
  });

  it('clicking a background card brings it to the front and opens the popover for it', async () => {
    const { wrapper, hotspots } = mountEditableWithPopover(makeElement([{ title: 'A' }, { title: 'B' }]));

    const cards = wrapper.findAll('.perspective-1000 > div');
    await cards[1]!.trigger('click');

    expect(hotspots.isPopoverOpen('stack-1:card')).toBe(true);
    // "B" is now the front card — its remove button is the one rendered.
    const removeButtons = wrapper.findAll('[data-rc-card-stack-remove-item]');
    expect(removeButtons).toHaveLength(1);
  });

  it('switches the current card via the popover prev/next controls and updates the icon field', async () => {
    const { wrapper, hotspots } = mountEditableWithPopover(makeElement([{ title: 'A', icon: 'icon-a' }, { title: 'B', icon: 'icon-b' }]));
    hotspots.openPopover('stack-1:card');
    await wrapper.vm.$nextTick();

    expect(wrapper.findComponent(popoverStubs.RCIconSelect).props('modelValue')).toBe('icon-a');

    await wrapper.get('[aria-label="rich-content.next_card"]').trigger('click');
    // setCurrentCard locks for 700ms — the popover's rendered icon reflects whichever
    // card is current the moment it re-renders next.
    await wrapper.vm.$nextTick();
    expect(wrapper.findComponent(popoverStubs.RCIconSelect).props('modelValue')).toBe('icon-b');
  });

  it('emits update:element with the patched icon for the current card', async () => {
    const { wrapper, hotspots } = mountEditableWithPopover(makeElement([{ title: 'A', icon: '' }]));
    hotspots.openPopover('stack-1:card');
    await wrapper.vm.$nextTick();

    await wrapper.findComponent(popoverStubs.RCIconSelect).vm.$emit('update:modelValue', 'megaphone');

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as CardStack;
    expect(patched.json_content[0]?.icon).toBe('megaphone');
  });
});
