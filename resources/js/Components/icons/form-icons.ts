// Tree-shakable form icons with regular and filled variants

import type { Component } from "vue";
import type { FormEnum } from "../../Types/otherEnums";

// =============================================================================
// REGULAR ICONS - Import each icon only ONCE
// =============================================================================
import CalendarLtr24Regular from "~icons/fluent/calendar-ltr24-regular";
import DocumentSave24Regular from "~icons/fluent/document-save24-regular";
import TextField24Regular from "~icons/fluent/text-field24-regular";

// =============================================================================
// FILLED ICONS - Import each icon only ONCE
// =============================================================================
import CalendarLtr24Filled from "~icons/fluent/calendar-ltr24-filled";
import DocumentSave24Filled from "~icons/fluent/document-save24-filled";
import TextField24Filled from "~icons/fluent/text-field24-filled";

// =============================================================================
// TREE-SHAKABLE EXPORTS - Clean, concise naming
// =============================================================================
export const DateIcon = CalendarLtr24Regular;
export const SaveIcon = DocumentSave24Regular;
export const TitleIcon = TextField24Regular;

export const DateIconFilled = CalendarLtr24Filled;
export const SaveIconFilled = DocumentSave24Filled;
export const TitleIconFilled = TextField24Filled;

// =============================================================================
// DYNAMIC ACCESS MAPPINGS (for backward compatibility and helper functions)
// =============================================================================

const formIconMappingRegular: Record<keyof typeof FormEnum, Component> = {
  DATE: DateIcon,
  SAVE: SaveIcon,
  TITLE: TitleIcon,
};

const formIconMappingFilled: Record<keyof typeof FormEnum, Component> = {
  DATE: DateIconFilled,
  SAVE: SaveIconFilled,
  TITLE: TitleIconFilled,
};

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Get form icon by enum key with variant support
 * @param formKey - The form enum key
 * @param variant - Icon variant ('regular' or 'filled')
 * @returns Vue component for the icon
 */
export function getFormIcon(
  formKey: keyof typeof FormEnum,
  variant: 'regular' | 'filled' = 'regular'
): Component {
  return variant === 'filled' 
    ? formIconMappingFilled[formKey] 
    : formIconMappingRegular[formKey];
}

const formIconMapping = formIconMappingRegular;

// Export mappings for external use
export { 
  formIconMapping, 
  formIconMappingRegular, 
  formIconMappingFilled 
};