/**
 * useColumnResize - Resizable column drag handler
 *
 * Generic composable for handling column resize via drag.
 * Can be reused for any resizable panel/column in the UI.
 */
import { ref, onUnmounted } from 'vue';

export interface ColumnResizeOptions {
  /** Minimum width in pixels */
  minWidth?: number;
  /** Maximum width in pixels */
  maxWidth?: number;
}

export function useColumnResize(
  /** Callback to set the new width */
  setWidth: (width: number) => void,
  /** Current width ref or getter */
  getCurrentWidth: () => number,
  options: ColumnResizeOptions = {},
) {
  const { minWidth = 120, maxWidth = 400 } = options;

  const isResizing = ref(false);
  const resizeStartX = ref(0);
  const resizeStartWidth = ref(0);

  /**
   * Start resize operation
   */
  function startResize(event: MouseEvent) {
    isResizing.value = true;
    resizeStartX.value = event.clientX;
    resizeStartWidth.value = getCurrentWidth();

    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);

    // Prevent text selection during resize
    document.body.style.userSelect = 'none';
    document.body.style.cursor = 'col-resize';
  }

  /**
   * Handle resize drag
   */
  function handleResize(event: MouseEvent) {
    if (!isResizing.value) return;

    const delta = event.clientX - resizeStartX.value;
    let newWidth = resizeStartWidth.value + delta;

    // Clamp to min/max
    newWidth = Math.max(minWidth, Math.min(maxWidth, newWidth));

    setWidth(newWidth);
  }

  /**
   * Stop resize operation
   */
  function stopResize() {
    isResizing.value = false;
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
    document.body.style.userSelect = '';
    document.body.style.cursor = '';
  }

  // Cleanup on unmount
  onUnmounted(() => {
    if (isResizing.value) {
      stopResize();
    }
  });

  return {
    isResizing,
    startResize,
  };
}
