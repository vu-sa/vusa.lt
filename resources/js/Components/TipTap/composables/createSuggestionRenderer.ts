import type { Component } from 'vue';
import { VueRenderer } from '@tiptap/vue-3';
import type { Editor } from '@tiptap/core';
import type { PluginKey } from '@tiptap/pm/state';
import { exitSuggestion, type SuggestionOptions, type SuggestionKeyDownProps } from '@tiptap/suggestion';

export function createSuggestionRenderer<Item, Selection>(list: Component, pluginKey: PluginKey): NonNullable<SuggestionOptions<Item, Selection>['render']> {
  return () => {
    let component: VueRenderer | null = null;
    let dropdown: HTMLElement | null = null;
    let editor: Editor | null = null;
    let unmount: (() => void) | null = null;

    function cleanup() {
      unmount?.();
      unmount = null;
      editor?.off('destroy', cleanup);
      dropdown?.remove();
      component?.destroy();
      component = null;
      dropdown = null;
      editor = null;
    }

    return {
      onStart: (props) => {
        cleanup();
        editor = props.editor;
        editor.on('destroy', cleanup);
        component = new VueRenderer(list, { props, editor });
        dropdown = component.element as HTMLElement;
        dropdown.style.zIndex = '9999';
        // A modal's focus trap must also contain its suggestion buttons.
        editor.view.dom.closest('[role="dialog"]')?.appendChild(dropdown);
        unmount = props.mount(dropdown);
      },
      onUpdate: (props) => {
        component?.updateProps(props);
      },
      onKeyDown: (props) => {
        if (props.event.key === 'Escape') {
          props.event.stopPropagation();
          exitSuggestion(props.view, pluginKey);
          return true;
        }
        const listRef = component?.ref as { onKeyDown?: (props: SuggestionKeyDownProps) => boolean } | null;
        return listRef?.onKeyDown?.(props) ?? false;
      },
      onExit: cleanup,
    };
  };
}
