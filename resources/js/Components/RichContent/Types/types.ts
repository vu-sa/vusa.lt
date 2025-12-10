/**
 * Shared type definitions for Rich Content Editor system
 * 
 * This module provides TypeScript interfaces to ensure consistency
 * across all content type editors in the RichContent system.
 * 
 * Content part types are re-exported from @/Types/contentParts.ts
 * for centralized type management.
 */
import { type Component, type AsyncComponentLoader } from 'vue';

// Re-export all content part types from the centralized location
export type {
  ContentGrid,
  ImageGrid,
  Tiptap,
  ShadcnAccordion,
  ShadcnCard,
  Hero,
  SpotifyEmbed,
  SocialEmbed,
  FlowGraph,
  NumberStatSection,
  Calendar,
  News,
} from '@/Types/contentParts';

/**
 * Base interface for content part data structure
 * Matches the backend ContentPart model
 */
export interface ContentPart<
  TContent = Record<string, any>,
  TOptions = Record<string, any>
> {
  id?: number;
  type: string;
  json_content: TContent;
  options?: TOptions;
  key?: string;
  order?: number;
}

/**
 * Props interface for all content type editors
 * 
 * All editor components should accept these props via defineModel:
 * - modelValue: The json_content of the content part
 * - options: The options object for the content part
 * 
 * @example
 * ```vue
 * <script setup lang="ts">
 * import type { ShadcnCard } from './types';
 * 
 * const content = defineModel<ShadcnCard['json_content']>();
 * const options = defineModel<ShadcnCard['options']>('options');
 * </script>
 * ```
 */
export interface ContentEditorProps<
  TContent = Record<string, any>,
  TOptions = Record<string, any>
> {
  /** The json_content field - main content data */
  modelValue: TContent;
  /** The options field - type-specific configuration */
  options?: TOptions;
}

/**
 * Content type registry entry definition
 * Extends the base ContentType with editor component mapping
 */
export interface ContentTypeDefinition<
  TContent = any,
  TOptions = Record<string, any>
> {
  /** Unique identifier matching backend ContentPartEnum */
  value: string;
  /** Display label (should use i18n key in future) */
  label: string;
  /** Icon component for UI display */
  icon: Component;
  /** Optional description for content type picker */
  description?: string;
  /** Factory function for default json_content */
  defaultContent: () => TContent;
  /** Factory function for default options */
  defaultOptions?: () => TOptions;
  /** Async component loader for the editor */
  editorComponent?: AsyncComponentLoader;
}
