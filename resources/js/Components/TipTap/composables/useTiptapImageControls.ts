/**
 * TipTap Image Controls Composable
 * 
 * Handles image alignment, resizing, and accessibility editing.
 */
import { ref, computed } from 'vue';
import type { Editor } from '@tiptap/vue-3';

export interface ImageData {
  src: string;
  alt: string;
  title: string;
}

export type ImageAlignment = 'left' | 'center' | 'right';
export type ImageSizePreset = 'small' | 'medium' | 'large' | 'full';

const IMAGE_SIZE_PRESETS: Record<ImageSizePreset, string> = {
  small: '300px',
  medium: '500px',
  large: '800px',
  full: '100%',
};

/**
 * Composable for image controls in TipTap editor
 * 
 * @example
 * ```ts
 * const { 
 *   showImageDialog, 
 *   currentImageData,
 *   openAccessibilityDialog,
 *   submitAccessibilityChanges,
 *   setAlignment,
 *   setSize,
 *   getCurrentAlignment,
 * } = useTiptapImageControls(editor);
 * ```
 */
export function useTiptapImageControls(editor: Ref<Editor | undefined>) {
  const showImageDialog = ref(false);
  const currentImageData = ref<ImageData>({ src: '', alt: '', title: '' });

  /**
   * Get current image alignment
   */
  function getCurrentAlignment(): ImageAlignment {
    if (!editor.value) return 'center';
    const attrs = editor.value.getAttributes('image');
    return (attrs.align as ImageAlignment) || 'center';
  }

  /**
   * Check if alignment is active
   */
  function isAlignmentActive(align: ImageAlignment): boolean {
    return getCurrentAlignment() === align;
  }

  /**
   * Set image alignment
   */
  function setAlignment(align: ImageAlignment) {
    editor.value?.chain().focus().updateAttributes('image', { align }).run();
  }

  /**
   * Set image size by preset
   */
  function setSizePreset(preset: ImageSizePreset) {
    const width = IMAGE_SIZE_PRESETS[preset];
    editor.value?.chain().focus().updateAttributes('image', { width }).run();
  }

  /**
   * Set image size by custom width
   */
  function setSize(width: string) {
    editor.value?.chain().focus().updateAttributes('image', { width }).run();
  }

  /**
   * Open accessibility dialog with current image data
   */
  function openAccessibilityDialog() {
    if (!editor.value) return;
    
    const attrs = editor.value.getAttributes('image');
    currentImageData.value = {
      src: attrs.src || '',
      alt: attrs.alt || '',
      title: attrs.title || '',
    };
    showImageDialog.value = true;
  }

  /**
   * Submit accessibility changes (alt text, title)
   */
  function submitAccessibilityChanges(data: { alt: string; title: string }) {
    editor.value?.chain().focus().updateAttributes('image', { 
      alt: data.alt, 
      title: data.title 
    }).run();
    showImageDialog.value = false;
  }

  /**
   * Close accessibility dialog
   */
  function closeAccessibilityDialog() {
    showImageDialog.value = false;
  }

  return {
    // State
    showImageDialog,
    currentImageData,
    
    // Alignment
    getCurrentAlignment,
    isAlignmentActive,
    setAlignment,
    
    // Sizing
    setSizePreset,
    setSize,
    IMAGE_SIZE_PRESETS,
    
    // Accessibility dialog
    openAccessibilityDialog,
    submitAccessibilityChanges,
    closeAccessibilityDialog,
  };
}

// Re-export types
import type { Ref } from 'vue';
