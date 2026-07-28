import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mockAttributionData = ref<{ name: string; photoUrl: string | null; attributions: string[] } | null>(null);
const mockExecute = vi.fn(async () => {});

vi.mock('@/Composables/useApi', () => ({
  useApi: vi.fn(() => ({
    data: mockAttributionData,
    execute: mockExecute,
  })),
}));

import PersonQuoteEditor from '../PersonQuoteEditor.vue';
import type { PersonQuote } from '@/Types/contentParts';

/** Stands in for CollectionSelectDialog — a real search-backed dialog is out of scope here. */
const stubs = {
  CollectionSelectDialog: {
    props: ['open'],
    emits: ['confirm', 'update:open'],
    template: `
      <div>
        <slot name="trigger" />
        <button class="confirm-pick" @click="$emit('confirm', [{ id: 'users-7', recordId: '7', title: 'Vardenė Pavardenė', collection: 'users' }])">
          pick
        </button>
      </div>
    `,
  },
  TiptapEditor: { props: ['modelValue'], template: '<div class="tiptap-stub" />' },
};

function makeItem(): { json_content: PersonQuote['json_content']; options: PersonQuote['options'] } {
  return {
    json_content: { quote: {}, snapshot: { name: '' } },
    options: { align: 'center', showAvatar: true },
  };
}

describe('PersonQuoteEditor', () => {
  // `content`/`options` are defineModel() refs whose getter returns the exact prop
  // object; the editor mutates them in place (matching every other RichContent
  // editor's convention — see ContentEditorFactory's "mutate in place to preserve
  // object identity" comment), so the passed-in object reflects changes directly
  // without needing to assert on an emitted event.
  it('snapshots a picked user\'s id and name', async () => {
    const item = makeItem();
    const wrapper = mount(PersonQuoteEditor, {
      props: { modelValue: item.json_content, options: item.options },
      global: { stubs },
    });

    await wrapper.find('.confirm-pick').trigger('click');

    expect(item.json_content.snapshot.userId).toBe(7);
    expect(item.json_content.snapshot.name).toBe('Vardenė Pavardenė');
  });

  it('seeds the attribution from the first suggestion, but leaves it editable', async () => {
    const item = makeItem();
    const wrapper = mount(PersonQuoteEditor, {
      props: { modelValue: item.json_content, options: item.options },
      global: { stubs },
    });

    await wrapper.find('.confirm-pick').trigger('click');
    mockAttributionData.value = { name: 'Vardenė Pavardenė', photoUrl: '/photo.jpg', attributions: ['Koordinatorė, VU SA MIF'] };
    await wrapper.vm.$nextTick();

    expect(item.json_content.snapshot.attribution).toBe('Koordinatorė, VU SA MIF');

    // Editing the attribution input afterwards must not be clobbered by a re-fetch.
    // (RCSectionOptions also renders text inputs — target this one specifically.)
    const attributionInput = wrapper.find('input[placeholder="rich-content.enter_attribution"]');
    await attributionInput.setValue('Pirmininkė');
    expect(item.json_content.snapshot.attribution).toBe('Pirmininkė');
  });
});
