import type { EditorPreset } from './extensions/presets';

/**
 * Which controls a toolbar offers. Kept apart from the extension preset on purpose:
 * trimming the *schema* would silently drop stored tables or tags on the next save,
 * so a record description keeps the `full` schema and only shows fewer controls.
 */
export type ToolbarProfile = 'marks' | 'compact' | 'description' | 'full';

export interface ToolbarTools {
  headingLevels: (2 | 3 | 4)[];
  lists: boolean;
  blockquote: boolean;
  horizontalRule: boolean;
  image: boolean;
  youtube: boolean;
  video: boolean;
  table: boolean;
  headingStyle: boolean;
  alignment: boolean;
  tag: boolean;
  clearFormatting: boolean;
}

const PROFILES: Record<ToolbarProfile, ToolbarTools> = {
  marks: {
    headingLevels: [],
    lists: false,
    blockquote: false,
    horizontalRule: false,
    image: false,
    youtube: false,
    video: false,
    table: false,
    headingStyle: false,
    alignment: false,
    tag: false,
    clearFormatting: false,
  },
  compact: {
    headingLevels: [2],
    lists: true,
    blockquote: false,
    horizontalRule: false,
    image: true,
    youtube: true,
    video: false,
    table: false,
    headingStyle: false,
    alignment: true,
    tag: true,
    clearFormatting: true,
  },
  description: {
    headingLevels: [2, 3, 4],
    lists: true,
    blockquote: true,
    horizontalRule: false,
    image: true,
    youtube: true,
    video: false,
    table: false,
    headingStyle: false,
    alignment: false,
    tag: false,
    clearFormatting: true,
  },
  full: {
    headingLevels: [2, 3, 4],
    lists: true,
    blockquote: true,
    horizontalRule: true,
    image: true,
    youtube: true,
    video: true,
    table: true,
    headingStyle: true,
    alignment: true,
    tag: true,
    clearFormatting: true,
  },
};

export function profileForPreset(preset: EditorPreset): ToolbarProfile {
  return preset === 'minimal' ? 'marks' : preset;
}

export function toolsFor(profile: ToolbarProfile, options: { disableTables?: boolean } = {}): ToolbarTools {
  const tools = { ...PROFILES[profile] };
  if (options.disableTables) {
    tools.table = false;
  }
  return tools;
}

export function hasInsertTools(tools: ToolbarTools): boolean {
  return tools.image || tools.youtube || tools.video || tools.table || tools.horizontalRule;
}
