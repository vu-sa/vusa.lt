/**
 * Shared Search Components
 *
 * Reusable search UI components for both admin and public interfaces.
 */

// Filter Components
export { default as CheckboxFilter } from './CheckboxFilter.vue';
export { default as YearFilter } from './YearFilter.vue';
export { default as FilterAccordion } from './FilterAccordion.vue';
export { FACET_ICON_COLORS, getFacetIconColor } from './facetIconColors';

// Types
export type {
  FilterOption,
  FacetValue,
  DateRangeValue,
  SortOption,
  SearchError,
  FilterAccordionConfig,
  ViewModeOption,
} from './types';
