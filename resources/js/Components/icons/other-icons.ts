// Tree-shakable other icons with regular and filled variants

import type { Component } from "vue";
import type { OtherIconEnum } from "../../Types/otherEnums";

// =============================================================================
// REGULAR ICONS - Import each icon only ONCE
// =============================================================================
import Alert24Regular from "~icons/fluent/alert24-regular";
import DocumentMultiple24Regular from "~icons/fluent/document-multiple24-regular";
import Home24Regular from "~icons/fluent/home24-regular";
import Image24Regular from "~icons/fluent/image24-regular";

// =============================================================================
// FILLED ICONS - Import each icon only ONCE
// =============================================================================
import Alert24Filled from "~icons/fluent/alert24-filled";
import DocumentMultiple24Filled from "~icons/fluent/document-multiple24-filled";
import Home24Filled from "~icons/fluent/home24-filled";
import Image24Filled from "~icons/fluent/image24-filled";

// =============================================================================
// TREE-SHAKABLE EXPORTS - Clean, concise naming
// =============================================================================
export const FileIcon = DocumentMultiple24Regular;
export const HomeIcon = Home24Regular;
export const ImageIcon = Image24Regular;
export const NotificationIcon = Alert24Regular;

export const FileIconFilled = DocumentMultiple24Filled;
export const HomeIconFilled = Home24Filled;
export const ImageIconFilled = Image24Filled;
export const NotificationIconFilled = Alert24Filled;

// =============================================================================
// DYNAMIC ACCESS MAPPINGS (for backward compatibility and helper functions)
// =============================================================================

const otherIconMappingRegular: Record<keyof typeof OtherIconEnum, Component> = {
  FILE: FileIcon,
  HOME: HomeIcon,
  IMAGE: ImageIcon,
  NOTIFICATION: NotificationIcon,
};

const otherIconMappingFilled: Record<keyof typeof OtherIconEnum, Component> = {
  FILE: FileIconFilled,
  HOME: HomeIconFilled,
  IMAGE: ImageIconFilled,
  NOTIFICATION: NotificationIconFilled,
};

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Get other icon by enum key with variant support
 * @param otherKey - The other icon enum key
 * @param variant - Icon variant ('regular' or 'filled')
 * @returns Vue component for the icon
 */
export function getOtherIcon(
  otherKey: keyof typeof OtherIconEnum,
  variant: 'regular' | 'filled' = 'regular'
): Component {
  return variant === 'filled' 
    ? otherIconMappingFilled[otherKey] 
    : otherIconMappingRegular[otherKey];
}

const otherIconMapping = otherIconMappingRegular;

// Export mappings for external use
export { 
  otherIconMapping, 
  otherIconMappingRegular, 
  otherIconMappingFilled 
};
