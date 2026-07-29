import { describe, it, expect } from 'vitest';
import { defineComponent } from 'vue';
import { mount } from '@vue/test-utils';

import TiptapLinkButton from '../TiptapLinkButton.vue';
import { commonStubs } from '@/tests/stubs';

/**
 * Minimal stand-in for a Tiptap editor covering only the surface this dialog
 * touches: `getAttributes('link')`, the current selection, `doc.textBetween`,
 * and the `chain().focus().extendMarkRange('link').run()` command used to
 * capture a link's text when the cursor is collapsed inside it.
 *
 * `extendMarkRange('link').run()` mutates the shared selection to the link's
 * full range — modelling how the real editor exposes the link text once the
 * mark range is extended.
 */
function createMockEditor(opts: {
  href?: string;
  from?: number;
  to?: number;
  linkFrom?: number;
  linkTo?: number;
  linkText?: string;
} = {}) {
  const {
    href = '',
    from = 5,
    to = 5,
    linkFrom = 3,
    linkTo = 15,
    linkText = 'mano nuoroda',
  } = opts;

  const selection = { from, to };

  return {
    state: {
      selection,
      doc: {
        textBetween: (f: number, t: number) => (f === t ? '' : linkText),
      },
    },
    getAttributes: () => (href ? { href } : {}),
    chain: () => ({
      focus: () => ({
        extendMarkRange: () => ({
          run: () => {
            selection.from = linkFrom;
            selection.to = linkTo;
          },
        }),
      }),
    }),
  };
}

// Inline selector stub: surfaces a button that emits `confirm` with a normalized
// document hit so we can drive the documents tab without a Typesense backend.
const InlineCollectionSelectStub = defineComponent({
  name: 'InlineCollectionSelect',
  props: ['collection', 'confirmLabel', 'emptyMessage', 'searchPlaceholder'],
  emits: ['confirm'],
  data: () => ({
    hit: { href: '/dokumentas/abc', title: 'Dokumentas ABC' },
  }),
  template: `
    <div data-testid="inline-doc-select">
      <button type="button" data-testid="doc-confirm" @click="$emit('confirm', [hit])">
        confirm
      </button>
    </div>
  `,
});

const FileSelectorStub = defineComponent({
  name: 'FileSelector',
  template: '<div data-testid="file-selector" />',
});

// Tabs are stubbed to render every panel inline so the URL-tab inputs and the
// documents-tab selector are both present regardless of the active tab (reka-ui
// Tabs only mount the active panel and behave unpredictably under jsdom).
const tabsStubs = {
  Tabs: { template: '<div><slot /></div>' },
  TabsList: { template: '<div><slot /></div>' },
  TabsTrigger: { template: '<button><slot /></button>' },
  TabsContent: { template: '<div><slot /></div>' },
};

function mountLinkButton(editor: any) {
  return mount(TiptapLinkButton, {
    props: { editor },
    slots: { default: '<button type="button" data-testid="trigger">open</button>' },
    global: {
      stubs: {
        ...commonStubs,
        ...tabsStubs,
        InlineCollectionSelect: InlineCollectionSelectStub,
        FileSelector: FileSelectorStub,
      },
    },
  });
}

async function openDialog(wrapper: ReturnType<typeof mountLinkButton>) {
  await wrapper.find('[data-testid="trigger"]').trigger('click');
}

describe('TiptapLinkButton', () => {
  it('populates the URL and link text when opened on a collapsed cursor inside a link', async () => {
    const editor = createMockEditor({ href: 'https://vusa.lt', linkText: 'mano nuoroda' });
    const wrapper = mountLinkButton(editor);

    await openDialog(wrapper);

    expect((wrapper.find('#url-input').element as HTMLInputElement).value).toBe('https://vusa.lt');
    // Regression: previously `textBetween(from, from)` returned '' for a collapsed
    // selection, so the link text never appeared. Extending the mark range first
    // surfaces the full link text.
    expect((wrapper.find('#link-text-input').element as HTMLInputElement).value).toBe('mano nuoroda');
  });

  it('clears the link text when opened with no selection and no active link', async () => {
    const editor = createMockEditor({ href: '', from: 5, to: 5 });
    const wrapper = mountLinkButton(editor);

    await openDialog(wrapper);

    expect((wrapper.find('#url-input').element as HTMLInputElement).value).toBe('');
    expect((wrapper.find('#link-text-input').element as HTMLInputElement).value).toBe('');
  });

  it('emits document:submit with the document href and falls back to its title as text', async () => {
    const editor = createMockEditor();
    const wrapper = mountLinkButton(editor);

    await openDialog(wrapper);

    // No custom link text typed — the document title should be used.
    await wrapper.find('[data-testid="doc-confirm"]').trigger('click');

    const docEvents = wrapper.emitted('document:submit');
    expect(docEvents).toHaveLength(1);
    expect(docEvents![0]).toEqual(['/dokumentas/abc', 'Dokumentas ABC']);
  });

  it('prefers a custom link text over the document title', async () => {
    const editor = createMockEditor();
    const wrapper = mountLinkButton(editor);

    await openDialog(wrapper);

    await wrapper.find('#archive-link-text').setValue('mano tekstas');
    await wrapper.find('[data-testid="doc-confirm"]').trigger('click');

    const docEvents = wrapper.emitted('document:submit');
    expect(docEvents![0]).toEqual(['/dokumentas/abc', 'mano tekstas']);
  });

  it('does not emit document:submit when the selected hit has no href', async () => {
    const editor = createMockEditor();
    const wrapper = mountLinkButton(editor);

    await openDialog(wrapper);

    // Replace the stub's hit with one missing `href`.
    (wrapper.findComponent(InlineCollectionSelectStub).vm as any).hit = { title: 'no url' };
    await wrapper.find('[data-testid="doc-confirm"]').trigger('click');

    expect(wrapper.emitted('document:submit')).toBeUndefined();
  });

  // NOTE on coverage gaps (per resources/js/CLAUDE.md):
  //  - The "link button turns active/black when the cursor is on a link" change
  //    lives in TiptapEditor.vue (bubble + toolbar `:variant` bindings) and
  //    depends on a real Tiptap editor + BubbleMenu, which jsdom cannot drive.
  //    Verified manually instead of asserted here.
  //  - The InlineCollectionSelect Typesense wiring is stubbed; its own behavior
  //    (facets, sort, selection state) is covered by the AdminSearch suite.
});
