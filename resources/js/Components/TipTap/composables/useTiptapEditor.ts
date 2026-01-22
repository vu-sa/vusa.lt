/**
 * TipTap Editor Composable
 * 
 * Centralized editor setup with preset support, heading ID generation, and cleanup logic.
 */
import { ref, onBeforeUnmount, nextTick } from 'vue';
import { useEditor, type Editor } from '@tiptap/vue-3';
import type { EditorOptions } from '@tiptap/core';
import { latinizeId } from '@/Utils/String';
import { type EditorPreset, getExtensionsForPreset, type PresetOptions } from '../extensions/presets';

export interface UseTiptapEditorOptions {
  /** Editor preset: 'minimal' | 'compact' | 'full' */
  preset: EditorPreset;
  /** Initial content (JSON or HTML string) */
  content?: string | Record<string, unknown> | null;
  /** Emit HTML instead of JSON on update */
  html?: boolean;
  /** Additional preset options */
  presetOptions?: PresetOptions;
  /** Custom editor props (merged with defaults) */
  editorProps?: Partial<EditorOptions['editorProps']>;
  /** Callback on content update */
  onUpdate?: (content: string | Record<string, unknown> | null) => void;
  /** Enable heading ID generation for TOC */
  generateHeadingIds?: boolean;
}

/**
 * Main TipTap editor composable
 * 
 * @example
 * ```ts
 * const { editor } = useTiptapEditor({
 *   preset: 'full',
 *   content: props.modelValue,
 *   onUpdate: (content) => emit('update:modelValue', content),
 * });
 * ```
 */
export function useTiptapEditor(options: UseTiptapEditorOptions) {
  const {
    preset,
    content = '',
    html = false,
    presetOptions = {},
    editorProps = {},
    onUpdate,
    generateHeadingIds = preset === 'full',
  } = options;

  const extensions = getExtensionsForPreset(preset, presetOptions);

  const editor = useEditor({
    editorProps: {
      attributes: {
        class: 'focus:outline-none px-3 py-2 w-full',
        ...editorProps.attributes,
      },
      ...editorProps,
    },
    extensions,
    content: content ?? '',
    onUpdate: ({ editor: editorInstance }) => {
      if (generateHeadingIds) {
        updateHeadingIds(editorInstance);
      }
      
      nextTick(() => {
        if (onUpdate) {
          if (html) {
            onUpdate(editorInstance.getHTML());
          } else {
            onUpdate(editorInstance.getJSON());
          }
        }
      });
    },
  });

  // Cleanup on unmount
  onBeforeUnmount(() => {
    editor.value?.destroy();
  });

  return {
    editor,
  };
}

/**
 * Generate unique heading IDs for table of contents
 */
function updateHeadingIds(editorInstance: Editor) {
  const innerHeadings: { level: number; text: string; id: string }[] = [];
  const transaction = editorInstance.state.tr;

  editorInstance.state.doc.descendants((node, pos) => {
    if (node.type.name === 'heading') {
      let id = latinizeId(node.textContent);

      // Ensure unique IDs
      let counter = 1;
      while (innerHeadings.some((heading) => heading.id === id)) {
        id = `${latinizeId(node.textContent)}-${counter}`;
        counter++;
      }

      if (node.attrs.id !== id) {
        transaction.setNodeAttribute(pos, 'id', id);
      }

      innerHeadings.push({
        level: node.attrs.level,
        text: node.textContent,
        id,
      });
    }
  });

  transaction.setMeta('addToHistory', false);
  transaction.setMeta('preventUpdate', true);

  editorInstance.view.dispatch(transaction);
}
