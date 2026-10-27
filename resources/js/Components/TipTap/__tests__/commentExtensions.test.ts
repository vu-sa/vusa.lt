import { afterEach, describe, expect, it } from 'vitest';
import { Editor } from '@tiptap/core';
import Mention from '@tiptap/extension-mention';

import { createCommentExtensions } from '../extensions/presets';
import { isValidTiptapLink } from '../linkUrl';

const editors: Editor[] = [];

function makeEditor(content = '') {
  const editor = new Editor({
    extensions: [...createCommentExtensions(), Mention.configure({ HTMLAttributes: { class: 'comment-mention' } })],
    content,
  });
  editors.push(editor);
  return editor;
}

afterEach(() => editors.splice(0).forEach(editor => editor.destroy()));

describe('comment formatting contract', () => {
  it('round trips formatting, nested lists, numbering, line breaks, links and mentions', () => {
    const editor = makeEditor('<p><strong>Bold</strong> <em>Italic</em> <u>Underline</u> <s>Strike</s><br>Next</p>'
      + '<ul><li><p>First</p><ol start="3"><li><p>Nested</p></li></ol></li></ul>'
      + '<p><a href="/mano/meetings">Meeting</a> <span data-type="mention" data-id="u1" data-label="Ona">@Ona</span></p>');
    const html = editor.getHTML();
    const reloaded = makeEditor(html);

    expect(reloaded.getJSON()).toEqual(editor.getJSON());
    expect(html).toContain('<ol start="3">');
    expect(html).toContain('<ul><li><p>First</p>');
    expect(html).toContain('href="/mano/meetings"');
    expect(html).toContain('data-id="u1"');
    expect(html).toContain('<br>');
  });

  it('does not register publishing, media, quote, code, or checklist capabilities', () => {
    const editor = makeEditor();

    for (const node of ['heading', 'blockquote', 'codeBlock', 'horizontalRule', 'image', 'table', 'taskList', 'youtube']) {
      expect(editor.schema.nodes[node], node).toBeUndefined();
    }
    expect(editor.schema.marks.code).toBeUndefined();
  });

  it('retains plain pasted text while dropping unsupported structure', () => {
    const editor = makeEditor('<h2>Heading</h2><blockquote><p>Quoted text</p></blockquote><pre><code>Example</code></pre>');

    expect(editor.getText()).toContain('Heading');
    expect(editor.getText()).toContain('Quoted text');
    expect(editor.getText()).toContain('Example');
    expect(editor.getHTML()).not.toMatch(/<(h2|blockquote|pre|code)[ >]/);
  });

  it('rejects unsafe links when invoked through editor commands', () => {
    const editor = makeEditor('<p>Text</p>');
    editor.commands.selectAll();

    expect(editor.commands.setLink({ href: 'javascript:alert(1)' })).toBe(false);
    expect(editor.getHTML()).not.toContain('<a');
    expect(editor.commands.setLink({ href: 'https://vusa.lt' })).toBe(true);
  });
});

describe('link input validation', () => {
  it.each(['https://vusa.lt', 'http://example.com', 'mailto:info@vusa.lt', '/mano/meetings', '#section'])('accepts %s', (url) => {
    expect(isValidTiptapLink(url)).toBe(true);
  });

  it.each(['', 'javascript:alert(1)', 'data:text/html,test', 'ftp://example.com', '//example.com', 'https://exa\nmple.com', 'not a url'])('rejects %s', (url) => {
    expect(isValidTiptapLink(url)).toBe(false);
  });
});
