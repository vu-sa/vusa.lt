/**
 * TipTap Editor Components - Public API
 *
 * This file exports the unified TipTap editor components.
 *
 * @example
 * ```ts
 * import { TiptapEditor, generateHTMLfromTiptap } from '@/Components/TipTap';
 * ```
 */

// Main editor component
export { default as TiptapEditor } from './TiptapEditor.vue';

// Extension presets for custom use cases
export {
  type EditorPreset,
  type PresetOptions,
  createMinimalExtensions,
  createCompactExtensions,
  createFullExtensions,
  createRenderExtensions,
  getExtensionsForPreset,
} from './extensions/presets';

// Composables
export { useTiptapEditor, type UseTiptapEditorOptions } from './composables/useTiptapEditor';
export { useTiptapFileUpload } from './composables/useTiptapFileUpload';
export { useTiptapCommands } from './composables/useTiptapCommands';
export { type ToolbarProfile, type ToolbarTools, toolsFor } from './toolbarProfiles';

// Extensions (for advanced customization)
export { AccessibleImage } from './AccessibleImage';
export { CustomHeading } from './CustomHeading';
export { Video } from './Video';

// Helper buttons (for building custom toolbars)
export { default as TiptapContextMenus } from './TiptapContextMenus.vue';
export { default as TiptapFormattingButtons } from './TiptapFormattingButtons.vue';
export { default as TiptapInsertMenu } from './TiptapInsertMenu.vue';
export { default as TiptapMoreMenu } from './TiptapMoreMenu.vue';
export { default as TiptapToolButton } from './TiptapToolButton.vue';
export { default as TiptapImageButton } from './TiptapImageButton.vue';
export { default as TiptapLinkButton } from './TiptapLinkButton.vue';
export { default as TiptapVideoButton } from './TiptapVideoButton.vue';
export { default as TiptapYoutubeButton } from './TiptapYoutubeButton.vue';

// Re-export HTML generation from RichContentTiptapHTML
export { generateHTMLfromTiptap } from '../RichContent/RichContentTiptapHTML.vue';
