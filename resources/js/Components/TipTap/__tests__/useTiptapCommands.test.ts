import { afterEach, describe, expect, it } from 'vitest';
import { Editor } from '@tiptap/core';

import { DOCUMENT_LINK_CLASS, useTiptapCommands } from '../composables/useTiptapCommands';
import { createFullExtensions } from '../extensions/presets';

const editors: Editor[] = [];

function makeEditor(content: string): Editor {
  const editor = new Editor({ extensions: createFullExtensions({ disableTables: true }), content });
  editors.push(editor);
  return editor;
}

afterEach(() => {
  editors.splice(0).forEach(editor => editor.destroy());
});

describe('useTiptapCommands', () => {
  it('links the selected text without duplicating it', () => {
    const editor = makeEditor('<p>Skaityk daugiau</p>');
    editor.commands.setTextSelection({ from: 1, to: 8 });

    useTiptapCommands(editor).insertLink('https://vusa.lt', 'ignored');

    expect(editor.getText()).toBe('Skaityk daugiau');
    expect(editor.getHTML()).toContain('href="https://vusa.lt"');
    expect(editor.getHTML()).toContain('>Skaityk</a>');
  });

  // Regression: links used to be inserted as an HTML string, so a quote in the label broke the markup.
  it('inserts a new link as text, keeping quotes in the label intact', () => {
    const editor = makeEditor('<p></p>');
    editor.commands.setTextSelection(1);

    useTiptapCommands(editor).insertLink('https://vusa.lt/?a="b"', 'Tai "citata"');

    expect(editor.getText()).toBe('Tai "citata"');
    expect(editor.getJSON().content?.[0]?.content?.[0]?.marks?.[0]?.attrs?.href).toBe('https://vusa.lt/?a="b"');
  });

  it('marks archive document links with the document class', () => {
    const editor = makeEditor('<p></p>');
    editor.commands.setTextSelection(1);

    useTiptapCommands(editor).insertLink('/dokumentas/abc', 'Protokolas', { document: true });

    expect(editor.getHTML()).toContain(DOCUMENT_LINK_CLASS);
  });

  it('keeps the heading level when the same level is picked again', () => {
    const editor = makeEditor('<p>Antraštė</p>');
    editor.commands.setTextSelection(2);
    const commands = useTiptapCommands(editor);

    commands.setHeadingLevel('3');
    commands.setHeadingLevel('3');

    expect(editor.isActive('heading', { level: 3 })).toBe(true);
    expect(commands.currentHeadingLevel.value).toBe('3');
  });

  it('removes the whole link from a collapsed cursor inside it', () => {
    const editor = makeEditor('<p><a href="https://vusa.lt">nuoroda</a> tekstas</p>');
    editor.commands.setTextSelection(3);

    useTiptapCommands(editor).unsetLink();

    expect(editor.getHTML()).not.toContain('<a');
  });
});
