import { afterEach, describe, expect, it } from 'vitest';
import { Editor } from '@tiptap/core';
import { StarterKit } from '@tiptap/starter-kit';

import { CustomHeading } from '../CustomHeading';
import { TextAlign } from '../TextAlign';
import { RCTag } from '../RCTag';
import { createCompactExtensions, createFullExtensions } from '../extensions/presets';
import { toCssWidth } from '../imageResizeNodeView';

/**
 * Headless extension tests, not component tests — TiptapEditor.vue itself is stubbed
 * everywhere else in this codebase per resources/js/CLAUDE.md's stubbing policy
 * (heavy DOM manipulation / contentEditable doesn't behave predictably in jsdom).
 * These construct a real `@tiptap/core` Editor with only the extensions under test
 * and exercise it via commands/`setContent`, never simulated typing — the same
 * approach the tiptap project itself uses to test extensions headlessly.
 *
 * Every editor created via `makeEditor()` is tracked and `.destroy()`ed in
 * `afterEach` — an un-destroyed Editor keeps a DOMObserver poll timer running, which
 * otherwise fires after this test file's jsdom document has been torn down and
 * throws an uncaught `document is not defined` between test files.
 */
const editors: Editor[] = [];

function makeEditor() {
  const editor = new Editor({
    extensions: [
      StarterKit.configure({ heading: false }),
      CustomHeading.configure({ levels: [2, 3, 4] }),
      TextAlign,
      RCTag,
    ],
  });
  editors.push(editor);
  return editor;
}

afterEach(() => {
  editors.splice(0).forEach(editor => editor.destroy());
});

describe('CustomHeading', () => {
  it('renders h2/h3/h4 with a slugified id', () => {
    const editor = makeEditor();
    editor.commands.setContent('<h2>Įvadas į programavimą</h2>');
    expect(editor.getHTML()).toContain('<h2 id="ivadas-i-programavima">');

    editor.commands.setContent('<h4>Sub point</h4>');
    expect(editor.getHTML()).toContain('<h4 id="sub-point">');
  });

  it('renders size and accent as classes, never inline style', () => {
    const editor = makeEditor();
    editor.commands.setContent('<h2>Title</h2>');
    editor.chain().setNodeSelection(0).updateAttributes('heading', { size: 'md', accent: 'yellow' }).run();

    const html = editor.getHTML();
    expect(html).toContain('rc-h-md');
    expect(html).toContain('rc-h-accent-yellow');
    expect(html).not.toContain('style=');
  });

  it('round-trips size/accent through parseHTML', () => {
    const editor = makeEditor();
    editor.commands.setContent('<h2 class="rc-h-lg rc-h-accent-red">Title</h2>');
    const json = editor.getJSON();
    const heading = json.content?.[0];
    expect(heading?.attrs?.size).toBe('lg');
    expect(heading?.attrs?.accent).toBe('red');
  });

  it('renders spacing as a class, never inline style', () => {
    const editor = makeEditor();
    editor.commands.setContent('<h2>Title</h2>');
    editor.chain().setNodeSelection(0).updateAttributes('heading', { spacing: 'tight' }).run();

    const html = editor.getHTML();
    expect(html).toContain('rc-h-spacing-tight');
    expect(html).not.toContain('style=');
  });

  it('leaves default spacing unclassed', () => {
    const editor = makeEditor();
    editor.commands.setContent('<h2>Title</h2>');
    editor.chain().setNodeSelection(0).updateAttributes('heading', { spacing: 'default' }).run();

    expect(editor.getHTML()).not.toContain('rc-h-spacing');
  });

  it('round-trips spacing through parseHTML', () => {
    const editor = makeEditor();
    editor.commands.setContent('<h2 class="rc-h-spacing-none">Title</h2>');
    const heading = editor.getJSON().content?.[0];
    expect(heading?.attrs?.spacing).toBe('none');
  });

  it('falls back to the first configured level for an out-of-range level attr', () => {
    const editor = makeEditor();
    editor.commands.setContent('<h2>Title</h2>');
    // Directly forcing an attribute Tiptap wouldn't produce through its own commands —
    // exercises the `hasLevel` fallback in `renderHTML`.
    editor.chain().setNodeSelection(0).updateAttributes('heading', { level: 9 }).run();
    expect(editor.getHTML()).toMatch(/<h2 id="title">/);
  });
});

describe('TextAlign', () => {
  it('renders center/end as a class and leaves the default (start) unclassed', () => {
    const editor = makeEditor();
    editor.commands.setContent('<p>Text</p>');
    editor.chain().setNodeSelection(0).updateAttributes('paragraph', { align: 'center' }).run();
    expect(editor.getHTML()).toContain('rc-align-center');
    expect(editor.getHTML()).not.toContain('style=');

    editor.chain().setNodeSelection(0).updateAttributes('paragraph', { align: 'start' }).run();
    expect(editor.getHTML()).not.toContain('rc-align');
  });

  it('applies to headings too', () => {
    const editor = makeEditor();
    editor.commands.setContent('<h2>Title</h2>');
    editor.chain().setNodeSelection(0).updateAttributes('heading', { align: 'end' }).run();
    expect(editor.getHTML()).toContain('rc-align-end');
  });

  it('round-trips alignment through parseHTML', () => {
    const editor = makeEditor();
    editor.commands.setContent('<p class="rc-align-center">Text</p>');
    expect(editor.getJSON().content?.[0]?.attrs?.align).toBe('center');
  });
});

describe('RCTag', () => {
  it('renders the dot-pill span with variant + color classes', () => {
    const editor = makeEditor();
    editor.commands.setContent('<p>Maskotė</p>');
    editor.commands.selectAll();
    editor.commands.setRCTag({ variant: 'filled', color: 'yellow' });

    const html = editor.getHTML();
    expect(html).toContain('rc-tag');
    expect(html).toContain('rc-tag-filled');
    expect(html).toContain('rc-tag-yellow');
  });

  it('round-trips variant/color through parseHTML', () => {
    const editor = makeEditor();
    editor.commands.setContent('<p><span class="rc-tag rc-tag-plain rc-tag-green">Live</span></p>');
    const marks = editor.getJSON().content?.[0]?.content?.[0]?.marks;
    expect(marks).toEqual([expect.objectContaining({ type: 'rcTag', attrs: { variant: 'plain', color: 'green' } })]);
  });

  it('unsetRCTag removes the mark', () => {
    const editor = makeEditor();
    editor.commands.setContent('<p>Tag me</p>');
    editor.commands.selectAll();
    editor.commands.setRCTag({ variant: 'filled', color: 'red' });
    expect(editor.getHTML()).toContain('rc-tag');

    editor.commands.selectAll();
    editor.commands.unsetRCTag();
    expect(editor.getHTML()).not.toContain('rc-tag');
  });
});

describe('createCompactExtensions (content-grid cells, etc.)', () => {
  // Regression test for a real bug: content-grid's tiptap cell editor uses the
  // `compact` preset, which didn't register `RCTag`/`TextAlign`. ProseMirror throws
  // "There is no mark type rcTag in this schema" while building a doc from JSON that
  // contains an unregistered mark — which crashed the *whole* editor instance the
  // moment it tried to load seeded content that used a tag, not just the tag itself.
  it('loads a doc containing a tag mark and an aligned heading without throwing', () => {
    const editor = new Editor({ extensions: createCompactExtensions() });
    editors.push(editor);

    expect(() => editor.commands.setContent({
      type: 'doc',
      content: [
        {
          type: 'heading',
          attrs: { level: 2, size: 'md', align: 'start' },
          content: [{ type: 'text', text: 'Susipažink su Lijana!' }],
        },
        {
          type: 'paragraph',
          content: [{ type: 'text', text: 'Maskotė', marks: [{ type: 'rcTag', attrs: { variant: 'filled', color: 'yellow' } }] }],
        },
      ],
    })).not.toThrow();

    const html = editor.getHTML();
    expect(html).toContain('rc-h-md');
    expect(html).toContain('rc-tag-filled');
  });

  it('lets an author apply a tag and set alignment through the same commands the toolbar uses', () => {
    const editor = new Editor({ extensions: createCompactExtensions() });
    editors.push(editor);

    editor.commands.setContent('<p>Aktyvi narė</p>');
    editor.commands.selectAll();
    editor.commands.setRCTag({ variant: 'plain', color: 'yellow' });
    expect(editor.getHTML()).toContain('rc-tag-plain');

    editor.chain().setNodeSelection(0).updateAttributes('paragraph', { align: 'center' }).run();
    expect(editor.getHTML()).toContain('rc-align-center');
  });
});

describe('AccessibleImage (full preset)', () => {
  function makeFullEditor() {
    const editor = new Editor({ extensions: createFullExtensions() });
    editors.push(editor);
    return editor;
  }

  // Regression test for a real bug: AccessibleImage overrode `addCommands()` without
  // spreading `this.parent?.()`, which dropped the base extension's `setImage`. The
  // toolbar's image button calls exactly that, so picking an image in the `full`
  // preset threw "setImage is not a function" and nothing was ever inserted.
  it('keeps the base setImage command the toolbar inserts through', () => {
    const editor = makeFullEditor();

    expect(typeof editor.commands.setImage).toBe('function');

    editor.chain().setImage({ src: '/uploads/test.png', alt: 'Alt', title: 'Title' }).run();

    const html = editor.getHTML();
    expect(html).toContain('src="/uploads/test.png"');
    expect(html).toContain('alt="Alt"');
  });

  it('still exposes the alt-aware insert command', () => {
    const editor = makeFullEditor();
    editor.chain().setImageWithAlt({ src: '/uploads/a.png', alt: 'A', align: 'left' }).run();

    expect(editor.getHTML()).toContain('data-align="left"');
  });

  it('renders a dragged/preset width so it survives a save and reload', () => {
    const editor = makeFullEditor();
    editor.chain().setImage({ src: '/uploads/test.png' }).run();
    editor.chain().setNodeSelection(0).updateAttributes('image', { width: '480px' }).run();

    expect(editor.getHTML()).toContain('width="480px"');

    // Round-trips back through parseHTML, so reopening the editor keeps the size.
    const reloaded = makeFullEditor();
    reloaded.commands.setContent(editor.getHTML());
    expect(reloaded.getHTML()).toContain('width="480px"');
  });
});

describe('image resize node view', () => {
  // The drag itself needs real layout (getBoundingClientRect is all zeroes in jsdom),
  // so the pointer maths is deliberately not asserted here — only that the handle is
  // mounted and that the width attribute it writes takes effect.
  it('mounts a resize handle next to the image in the editing surface', () => {
    const editor = new Editor({ extensions: createFullExtensions() });
    editors.push(editor);
    editor.chain().setImage({ src: '/uploads/test.png' }).run();

    const wrapper = editor.view.dom.querySelector('.rc-image-wrapper');
    expect(wrapper).not.toBeNull();
    expect(wrapper?.querySelector('img')?.getAttribute('src')).toBe('/uploads/test.png');
    expect(wrapper?.querySelector('.rc-image-handle')).not.toBeNull();
  });

  it('reflects the stored width on the wrapper so the handle hugs the image', () => {
    const editor = new Editor({ extensions: createFullExtensions() });
    editors.push(editor);
    editor.chain().setImage({ src: '/uploads/test.png' }).run();
    editor.chain().setNodeSelection(0).updateAttributes('image', { width: 320 }).run();

    const wrapper = editor.view.dom.querySelector<HTMLElement>('.rc-image-wrapper');
    expect(wrapper?.style.width).toBe('320px');
  });
});

describe('toCssWidth', () => {
  it.each([
    [null, null],
    ['', null],
    [500, '500px'],
    ['500', '500px'],
    ['500px', '500px'],
    ['100%', '100%'],
  ])('maps %p to %p', (input, expected) => {
    expect(toCssWidth(input)).toBe(expected);
  });
});
