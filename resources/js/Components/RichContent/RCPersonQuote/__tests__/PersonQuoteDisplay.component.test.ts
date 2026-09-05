import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

const mockAttributionData = ref<{ name: string; photoUrl: string | null; attributions: string[] } | null>(null);
const mockExecute = vi.fn(async () => {});

vi.mock('@/Composables/useApi', () => ({
  useApi: vi.fn(() => ({
    data: mockAttributionData,
    execute: mockExecute,
  })),
}));

import PersonQuoteDisplay from '../PersonQuoteDisplay.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../../Editor/Fullscreen/useActiveHotspot';

import { stubPopover, stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs';

/** Stands in for CollectionSelectDialog — a real search-backed dialog is out of scope here. */
const collectionSelectDialogStub = {
  props: ['open'],
  emits: ['confirm', 'update:open'],
  template: `
    <div>
      <slot name="trigger" />
      <button class="confirm-pick" @click="$emit('confirm', [{ id: 'users-7', recordId: '7', title: 'Naujas Asmuo', collection: 'users' }])">
        pick
      </button>
      <button class="clear-pick" @click="$emit('confirm', [])">
        clear
      </button>
    </div>
  `,
};

function makeElement(overrides: Record<string, unknown> = {}) {
  return {
    type: 'person-quote',
    json_content: {
      quote: { type: 'doc', content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Narystė man daug davė.' }] }] },
      snapshot: { name: 'Vardenė Pavardenė', photoUrl: null, attribution: 'Koordinatorė, VU SA MIF' },
    },
    options: { align: 'center', showAvatar: true },
    ...overrides,
  };
}

const editableStubs = {
  TiptapEditor: {
    props: ['modelValue', 'preset', 'proseStyle', 'placeholder'],
    emits: ['update:modelValue'],
    template: '<div class="tiptap-editor-stub" :data-preset="preset"><input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)"></div>',
  },
  CollectionSelectDialog: collectionSelectDialogStub,
  Popover: stubPopover,
  PopoverAnchor: stubPopoverAnchor,
  PopoverContent: stubPopoverContent,
};

function mountEditable(element: ReturnType<typeof makeElement>, blockKey = 'quote-1') {
  const hotspots = useActiveHotspot();
  const wrapper = mount(PersonQuoteDisplay, {
    props: { element, editable: true, blockKey },
    global: { stubs: editableStubs, provide: { [ACTIVE_HOTSPOT_KEY]: hotspots } },
  });
  return { wrapper, hotspots };
}

describe('PersonQuoteDisplay', () => {
  it('renders the quote text and attribution', () => {
    const wrapper = mount(PersonQuoteDisplay, { props: { element: makeElement() } });
    expect(wrapper.text()).toContain('Narystė man daug davė.');
    expect(wrapper.text()).toContain('Vardenė Pavardenė');
    expect(wrapper.text()).toContain('Koordinatorė, VU SA MIF');
  });

  it('falls back to initials when there is no photo', () => {
    const wrapper = mount(PersonQuoteDisplay, { props: { element: makeElement() } });
    expect(wrapper.text()).toContain('VP');
  });

  it('hides the avatar row when showAvatar is false', () => {
    const wrapper = mount(PersonQuoteDisplay, {
      props: { element: makeElement({ options: { align: 'center', showAvatar: false } }) },
    });
    expect(wrapper.text()).not.toContain('Vardenė Pavardenė');
  });

  it('applies the anchor id from anchorId for ToC scroll targets', () => {
    const wrapper = mount(PersonQuoteDisplay, { props: { element: makeElement(), anchorId: 9 } });
    expect(wrapper.find('#rc-9').exists()).toBe(true);
  });
});

describe('PersonQuoteDisplay — editable (full-screen editor)', () => {
  it('clicking the static quote claims the hotspot and swaps in a live TiptapEditor', async () => {
    const { wrapper, hotspots } = mountEditable(makeElement());

    expect(wrapper.find('.tiptap-editor-stub').exists()).toBe(false);

    await wrapper.find('blockquote button').trigger('click');

    expect(hotspots.isTextFieldLive('quote-1:quote')).toBe(true);
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.tiptap-editor-stub').exists()).toBe(true);
    expect(wrapper.find('.tiptap-editor-stub').attributes('data-preset')).toBe('minimal');
  });

  it('editing the live quote emits update:element with the patched quote', async () => {
    const { wrapper, hotspots } = mountEditable(makeElement());
    hotspots.openTextField('quote-1:quote');
    await wrapper.vm.$nextTick();

    await wrapper.find('.tiptap-editor-stub input').setValue('Naujas tekstas');
    const emitted = wrapper.emitted('update:element');
    expect((emitted!.at(-1)![0] as { json_content: { quote: unknown } }).json_content.quote).toBe('Naujas tekstas');
  });

  it('shows a placeholder when an editable quote is empty', () => {
    const { wrapper } = mountEditable(makeElement({
      json_content: { quote: { type: 'doc', content: [] }, snapshot: { name: 'Vardenė Pavardenė' } },
    }));

    expect(wrapper.find('blockquote').text()).toContain('rich-content.person_quote_quote');
  });

  it('a non-editable quote never renders the live editor', () => {
    const wrapper = mount(PersonQuoteDisplay, {
      props: { element: makeElement() },
      global: { stubs: editableStubs },
    });
    expect(wrapper.find('.tiptap-editor-stub').exists()).toBe(false);
    expect(wrapper.find('blockquote button').exists()).toBe(false);
  });
});

describe('PersonQuoteDisplay — person popover (full-screen editor)', () => {
  it('clicking the avatar claims the person hotspot and opens the picker popover', async () => {
    const { wrapper, hotspots } = mountEditable(makeElement());

    expect(wrapper.find('.confirm-pick').exists()).toBe(false);

    await wrapper.find('figcaption button').trigger('click');

    expect(hotspots.isPopoverOpen('quote-1:person')).toBe(true);
    await wrapper.vm.$nextTick();
    expect(wrapper.find('.confirm-pick').exists()).toBe(true);
  });

  it('shows an "add person" placeholder when editable and no person is picked yet', () => {
    const { wrapper } = mountEditable(makeElement({
      json_content: { quote: { type: 'doc', content: [] }, snapshot: { name: '' } },
    }));

    expect(wrapper.get('figcaption').text()).toContain('rich-content.select_person');
  });

  it('picking a person emits update:element with the snapshotted id and name', async () => {
    const { wrapper, hotspots } = mountEditable(makeElement());
    hotspots.openPopover('quote-1:person');
    await wrapper.vm.$nextTick();

    await wrapper.find('.confirm-pick').trigger('click');

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as { json_content: { snapshot: { userId?: number; name?: string } } };
    expect(patched.json_content.snapshot.userId).toBe(7);
    expect(patched.json_content.snapshot.name).toBe('Naujas Asmuo');
  });

  it('clearing the picker emits an empty snapshot', async () => {
    const { wrapper, hotspots } = mountEditable(makeElement());
    hotspots.openPopover('quote-1:person');
    await wrapper.vm.$nextTick();

    await wrapper.find('.clear-pick').trigger('click');

    const emitted = wrapper.emitted('update:element');
    const patched = emitted!.at(-1)![0] as { json_content: { snapshot: { name?: string } } };
    expect(patched.json_content.snapshot.name).toBe('');
  });

  it('seeds the attribution from the first suggestion after picking a person, without clobbering an existing one', async () => {
    const { wrapper, hotspots } = mountEditable(makeElement({
      json_content: { quote: { type: 'doc', content: [] }, snapshot: { name: '' } },
    }));
    hotspots.openPopover('quote-1:person');
    await wrapper.vm.$nextTick();

    await wrapper.find('.confirm-pick').trigger('click');
    mockAttributionData.value = { name: 'Naujas Asmuo', photoUrl: '/photo.jpg', attributions: ['Koordinatorius'] };
    await wrapper.vm.$nextTick();

    const emitted = wrapper.emitted('update:element');
    const patched = emitted!.at(-1)![0] as { json_content: { snapshot: { attribution?: string; photoUrl?: string } } };
    expect(patched.json_content.snapshot.attribution).toBe('Koordinatorius');
    expect(patched.json_content.snapshot.photoUrl).toBe('/photo.jpg');
  });

  it('emits an inline attribution edit once a person is set', async () => {
    const { wrapper } = mountEditable(makeElement());

    const attribution = wrapper.findAll('[contenteditable]')[0]!;
    attribution.element.textContent = 'Pirmininkė';
    await attribution.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200)); // RCInlineText's debounce

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as { json_content: { snapshot: { attribution?: string } } };
    expect(patched.json_content.snapshot.attribution).toBe('Pirmininkė');
  });
});
