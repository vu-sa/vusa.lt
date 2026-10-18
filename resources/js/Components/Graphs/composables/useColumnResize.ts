/**
 * useColumnResize - Resizable column drag handler
 *
 * Pointer events with capture: a cancelled `pointerdown` suppresses the compatibility
 * `mouseup`, which left the column stuck to the cursor after release.
 */
import { ref, getCurrentScope, onScopeDispose } from 'vue';

export interface ColumnResizeOptions {
  minWidth?: number;
  maxWidth?: number;
}

export function useColumnResize(
  setWidth: (width: number) => void,
  getCurrentWidth: () => number,
  options: ColumnResizeOptions = {},
) {
  const { minWidth = 120, maxWidth = 400 } = options;

  const isResizing = ref(false);
  let startX = 0;
  let startWidth = 0;
  let captureTarget: Element | null = null;
  let pointerId: number | null = null;

  function startResize(event: PointerEvent) {
    if (event.button !== 0) return;
    event.preventDefault();

    isResizing.value = true;
    startX = event.clientX;
    startWidth = getCurrentWidth();
    pointerId = event.pointerId;
    captureTarget = event.currentTarget instanceof Element ? event.currentTarget : null;
    captureTarget?.setPointerCapture?.(event.pointerId);

    document.addEventListener('pointermove', handleResize);
    document.addEventListener('pointerup', stopResize);
    document.addEventListener('pointercancel', stopResize);
    window.addEventListener('blur', stopResize);

    document.body.style.userSelect = 'none';
    document.body.style.cursor = 'col-resize';
  }

  function handleResize(event: PointerEvent) {
    if (!isResizing.value) return;
    // A release outside the window never reaches us; the next move without a pressed button ends the drag.
    if (event.buttons === 0) {
      stopResize();
      return;
    }

    setWidth(Math.max(minWidth, Math.min(maxWidth, startWidth + event.clientX - startX)));
  }

  function stopResize() {
    isResizing.value = false;
    if (captureTarget && pointerId !== null && captureTarget.hasPointerCapture?.(pointerId)) {
      captureTarget.releasePointerCapture(pointerId);
    }
    captureTarget = null;
    pointerId = null;
    document.removeEventListener('pointermove', handleResize);
    document.removeEventListener('pointerup', stopResize);
    document.removeEventListener('pointercancel', stopResize);
    window.removeEventListener('blur', stopResize);
    document.body.style.userSelect = '';
    document.body.style.cursor = '';
  }

  function cleanup() {
    if (isResizing.value) {
      stopResize();
    }
  }

  if (getCurrentScope()) {
    onScopeDispose(cleanup);
  }

  return {
    isResizing,
    startResize,
    cleanup,
  };
}
