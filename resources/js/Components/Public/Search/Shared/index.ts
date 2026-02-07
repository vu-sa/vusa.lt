// Search base components for code reuse across Document, Institution, and Meeting search pages

export { default as BaseSearchInput } from './BaseSearchInput.vue'
export { default as BaseSearchResults } from './BaseSearchResults.vue'
export { default as BaseFacetSidebar } from './BaseFacetSidebar.vue'
export { default as BaseSearchInterface } from './BaseSearchInterface.vue'
export { default as FilterAccordionItem } from './FilterAccordionItem.vue'
export { default as CheckboxFilterList } from './CheckboxFilterList.vue'

// Re-export types for convenience
export type { FilterOption, ViewModeOption } from './types'
