/**
 * Navigation-related constants
 */

/** Scroll threshold in pixels before navigation styling changes */
export const SCROLL_THRESHOLD = 50;

/** Debounce delay in milliseconds for search input */
export const SEARCH_DEBOUNCE_DELAY = 500;

/** Standard icon sizes for navigation components */
export const ICON_SIZES = {
  small: 'h-3 w-3',
  default: 'h-4 w-4', 
  large: 'h-5 w-5',
} as const;

/** Animation duration constants */
export const ANIMATION_DURATION = {
  fast: 150,
  default: 200,
  slow: 300,
} as const;
