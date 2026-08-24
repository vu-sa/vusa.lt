import type { NodeViewRenderer } from '@tiptap/core';
import type { Node as ProseMirrorNode } from '@tiptap/pm/model';

type ImageAlign = 'left' | 'center' | 'right';

/** Below this the handle overlaps the image entirely and the drag becomes unusable. */
const MIN_WIDTH_PX = 48;

const WRAPPER_ALIGN_CLASSES: Record<ImageAlign, string> = {
  left: 'float-left mr-4 mb-2',
  right: 'float-right ml-4 mb-2',
  center: 'mx-auto',
};

/**
 * Drag-to-resize node view for the `image` node.
 *
 * Persists the same `width` attribute the toolbar size presets write, so a dragged
 * size survives renderHTML and the img allowlist in HtmlSanitizerService. Plain DOM
 * rather than a Vue node view: the extension is also used to build a headless editor.
 */
export function createImageResizeNodeView(): NodeViewRenderer {
  return ({ node, editor, getPos }) => {
    const wrapper = document.createElement('div');
    const image = document.createElement('img');
    const handle = document.createElement('span');

    handle.className = 'rc-image-handle';
    handle.setAttribute('contenteditable', 'false');
    handle.setAttribute('aria-hidden', 'true');
    wrapper.append(image, handle);

    let currentNode = node;

    render(currentNode);

    function render(renderedNode: ProseMirrorNode): void {
      const align = (renderedNode.attrs.align as ImageAlign) || 'center';
      const width = toCssWidth(renderedNode.attrs.width);

      wrapper.className = `rc-image-wrapper ${WRAPPER_ALIGN_CLASSES[align] ?? WRAPPER_ALIGN_CLASSES.center}`;
      wrapper.style.width = width ?? 'fit-content';

      image.className = 'tiptap-image';
      image.src = (renderedNode.attrs.src as string) ?? '';
      image.alt = (renderedNode.attrs.alt as string) ?? '';
      image.title = (renderedNode.attrs.title as string) ?? '';
      image.draggable = false;
      // Only constrain the image once the wrapper has a width of its own — a
      // percentage inside a `fit-content` wrapper would resolve against nothing.
      image.style.width = width ? '100%' : '';
    }

    function startResize(event: PointerEvent): void {
      if (!editor.isEditable) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();

      const startX = event.clientX;
      const startWidth = image.getBoundingClientRect().width;
      const maxWidth = wrapper.parentElement?.getBoundingClientRect().width || startWidth;
      // A centred image grows from both edges, so the pointer travels half the change.
      const factor = ((currentNode.attrs.align as ImageAlign) || 'center') === 'center' ? 2 : 1;
      let width = startWidth;

      const onMove = (moveEvent: PointerEvent): void => {
        width = Math.round(clamp(startWidth + (moveEvent.clientX - startX) * factor, MIN_WIDTH_PX, maxWidth));
        wrapper.style.width = `${width}px`;
        wrapper.dataset.resizing = `${width}px`;
        image.style.width = '100%';
      };

      const onEnd = (): void => {
        handle.releasePointerCapture?.(event.pointerId);
        handle.removeEventListener('pointermove', onMove);
        handle.removeEventListener('pointerup', onEnd);
        handle.removeEventListener('pointercancel', onEnd);
        delete wrapper.dataset.resizing;

        const pos = typeof getPos === 'function' ? getPos() : undefined;
        if (pos === undefined) {
          return;
        }

        // A full-bleed drag persists as `100%` so the image stays fluid on narrow screens.
        const value = width >= maxWidth - 1 ? '100%' : `${width}px`;
        editor.view.dispatch(editor.view.state.tr.setNodeAttribute(pos, 'width', value));
      };

      handle.setPointerCapture?.(event.pointerId);
      handle.addEventListener('pointermove', onMove);
      handle.addEventListener('pointerup', onEnd);
      handle.addEventListener('pointercancel', onEnd);
    }

    handle.addEventListener('pointerdown', startResize);

    return {
      dom: wrapper,
      update(updatedNode) {
        if (updatedNode.type.name !== currentNode.type.name) {
          return false;
        }

        currentNode = updatedNode;
        render(updatedNode);

        return true;
      },
      selectNode() {
        wrapper.classList.add('rc-image-selected');
      },
      deselectNode() {
        wrapper.classList.remove('rc-image-selected');
      },
      // The node has no editable content; every mutation here is our own styling.
      ignoreMutation: () => true,
      stopEvent: event => event.target instanceof Node && handle.contains(event.target),
      destroy() {
        handle.removeEventListener('pointerdown', startResize);
      },
    };
  };
}

function clamp(value: number, min: number, max: number): number {
  return Math.min(Math.max(value, min), Math.max(min, max));
}

/** Accepts `500`, `'500'`, `'500px'` and `'100%'`; anything empty means "natural size". */
export function toCssWidth(width: unknown): string | null {
  if (width === null || width === undefined || width === '') {
    return null;
  }

  if (typeof width === 'number') {
    return `${width}px`;
  }

  const value = String(width).trim();

  return /^\d+(\.\d+)?$/.test(value) ? `${value}px` : value;
}
