/**
 * Shared component types for search UI
 */

/**
 * Filter option for checkbox/radio lists
 */
export interface FilterOption {
  value: string | number
  label: string
  count?: number
  icon?: string
  isSelected?: boolean
}

/**
 * View mode option for result display
 */
export interface ViewModeOption {
  value: 'list' | 'compact' | 'grid'
  label: string
  icon: string
}

/**
 * Sort option for result ordering
 */
export interface SortOption {
  value: string
  label: string
  field: string
  direction: 'asc' | 'desc'
}

/**
 * Date preset option
 */
export interface DatePresetOption {
  value: string
  label: string
  days?: number
}

/**
 * Facet accordion item configuration
 */
export interface FacetAccordionConfig {
  id: string
  label: string
  icon?: string
  defaultOpen?: boolean
  collapsible?: boolean
}

/**
 * Search input props shared interface
 */
export interface SearchInputProps {
  query?: string
  isSearching?: boolean
  recentSearches?: string[]
  placeholderKey?: string
  typeToSearch?: boolean
  showClearButton?: boolean
  showSearchButton?: boolean
}

/**
 * Search input emits shared interface
 */
export interface SearchInputEmits {
  (e: 'update:query', value: string): void
  (e: 'search', query: string): void
  (e: 'selectRecent', search: string): void
  (e: 'clear'): void
  (e: 'focus'): void
  (e: 'blur'): void
  (e: 'update:typeToSearch', value: boolean): void
  (e: 'removeRecent', search: string): void
  (e: 'clearAllHistory'): void
}

/**
 * Facet sidebar props shared interface
 */
export interface FacetSidebarProps {
  activeFilterCount?: number
  mobileTitle?: string
  isLoading?: boolean
}

/**
 * Facet sidebar emits shared interface
 */
export interface FacetSidebarEmits {
  (e: 'clearFilters'): void
}

/**
 * Checkbox filter list props
 */
export interface CheckboxFilterListProps {
  options: FilterOption[]
  selectedValues: (string | number)[]
  showCounts?: boolean
  emptyText?: string
  maxHeight?: string
}
