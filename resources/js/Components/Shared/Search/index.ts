/**
 * Shared Search Components
 *
 * Reusable search UI components for both admin and public interfaces.
 */

// Filter Components
export { default as CheckboxFilter } from './CheckboxFilter.vue'
export { default as YearFilter } from './YearFilter.vue'
export { default as FilterAccordion } from './FilterAccordion.vue'
export { default as FilterSidebar } from './FilterSidebar.vue'

// Results Components
export { default as SearchResults } from './SearchResults.vue'

// Types
export type {
  FilterOption,
  FacetValue,
  DateRangeValue,
  SortOption,
  SearchError,
  FilterAccordionConfig,
  ViewModeOption,
} from './types'
