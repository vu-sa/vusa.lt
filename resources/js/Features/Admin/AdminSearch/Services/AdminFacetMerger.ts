/**
 * Admin Facet Merger
 *
 * Merges initial facets (all available options) with current search facets
 * (results from current query). This ensures users can see all filter options
 * even when current search has limited results.
 */

import type {
  AdminFacet,
  AdminFacetValue,
  AdminSearchFilters,
  CollectionFacetConfig,
} from '../Types/AdminSearchTypes'

/**
 * Merges initial facets with current search facets.
 * Shows ALL initial values with updated counts from current search.
 */
export function mergeFacets(
  initialFacets: AdminFacet[],
  currentFacets: AdminFacet[],
  filters: AdminSearchFilters,
  facetConfig: CollectionFacetConfig
): AdminFacet[] {
  // If no initial facets, use current facets
  if (initialFacets.length === 0) {
    return currentFacets.map(facet => addSelectionState(facet, filters))
  }

  return initialFacets.map(initialFacet => {
    // Find corresponding current facet from search results
    const currentFacet = currentFacets.find(f => f.field === initialFacet.field)

    // Create a map of current values for quick lookup
    const currentValueMap = new Map<string, number>()
    if (currentFacet) {
      currentFacet.values.forEach(value => {
        currentValueMap.set(value.value, value.count)
      })
    }

    // Get selected values for this facet field
    const selectedValues = getSelectedValuesForField(initialFacet.field, filters)

    // Merge values: show ALL initial values with updated counts from current search
    const mergedValues = initialFacet.values.map(initialValue => {
      const currentCount = currentValueMap.get(initialValue.value) || 0
      const isSelected = selectedValues.includes(initialValue.value)

      return {
        ...initialValue,
        count: currentCount,
        isSelected,
      }
    })

    // Add any new values from current search that weren't in initial facets
    if (currentFacet) {
      currentFacet.values.forEach(currentValue => {
        const existsInInitial = initialFacet.values.some(
          iv => iv.value === currentValue.value
        )
        if (!existsInInitial) {
          mergedValues.push({
            ...currentValue,
            isSelected: selectedValues.includes(currentValue.value),
          })
        }
      })
    }

    // Sort: selected items first, then by count, then alphabetically
    sortFacetValues(mergedValues, selectedValues)

    return {
      ...initialFacet,
      values: mergedValues,
    }
  })
}

/**
 * Get selected values for a specific facet field from filters
 */
function getSelectedValuesForField(
  field: string,
  filters: AdminSearchFilters
): string[] {
  const value = filters[field]

  if (Array.isArray(value)) {
    return value.map(String)
  }
  if (value !== undefined && value !== null) {
    return [String(value)]
  }
  return []
}

/**
 * Sort facet values: selected first, then by count (descending), then alphabetically
 */
function sortFacetValues(
  values: AdminFacetValue[],
  selectedValues: string[]
): void {
  values.sort((a, b) => {
    const aSelected = selectedValues.includes(a.value)
    const bSelected = selectedValues.includes(b.value)

    // Selected items first
    if (aSelected && !bSelected) return -1
    if (!aSelected && bSelected) return 1

    // Then by count (descending)
    if (a.count !== b.count) return b.count - a.count

    // Finally alphabetically
    return a.value.localeCompare(b.value)
  })
}

/**
 * Add selection state to facet values based on current filters
 */
function addSelectionState(
  facet: AdminFacet,
  filters: AdminSearchFilters
): AdminFacet {
  const selectedValues = getSelectedValuesForField(facet.field, filters)

  return {
    ...facet,
    values: facet.values.map(value => ({
      ...value,
      isSelected: selectedValues.includes(value.value),
    })),
  }
}

/**
 * Filter facets to only include non-empty ones (or selected ones)
 * Useful for displaying facets in a cleaner UI
 */
export function filterEmptyFacets(
  facets: AdminFacet[],
  hasActiveSearch: boolean
): AdminFacet[] {
  return facets.filter(facet => {
    // Always show facets with values that have counts
    if (facet.values.some(v => v.count > 0)) {
      return true
    }

    // During active search, show facets with selected values
    if (hasActiveSearch) {
      return facet.values.some(v => v.isSelected)
    }

    // In browse mode, show all facets for discoverability
    return true
  })
}

/**
 * Calculate facet statistics for UI display
 */
export function calculateFacetStats(facets: AdminFacet[]): {
  totalFacets: number
  activeFacets: number
  totalValues: number
  selectedValues: number
} {
  let totalFacets = facets.length
  let activeFacets = 0
  let totalValues = 0
  let selectedValues = 0

  facets.forEach(facet => {
    const hasActiveValues = facet.values.some(v => v.count > 0)
    if (hasActiveValues) activeFacets++

    totalValues += facet.values.length
    selectedValues += facet.values.filter(v => v.isSelected).length
  })

  return {
    totalFacets,
    activeFacets,
    totalValues,
    selectedValues,
  }
}

/**
 * Sort facets by priority order from config
 */
export function sortFacetsByConfig(
  facets: AdminFacet[],
  facetConfig: CollectionFacetConfig
): AdminFacet[] {
  const fieldOrder = facetConfig.fields.map(f => f.field)

  return [...facets].sort((a, b) => {
    const aIndex = fieldOrder.indexOf(a.field)
    const bIndex = fieldOrder.indexOf(b.field)

    // Unknown fields go to the end
    const aPriority = aIndex === -1 ? 999 : aIndex
    const bPriority = bIndex === -1 ? 999 : bIndex

    return aPriority - bPriority
  })
}

/**
 * Group facet values by category (useful for tenant hierarchies, etc.)
 */
export function groupFacetValues<T extends AdminFacetValue>(
  values: T[],
  groupFn: (value: T) => string
): Record<string, T[]> {
  const groups: Record<string, T[]> = {}

  for (const value of values) {
    const group = groupFn(value)
    if (!groups[group]) {
      groups[group] = []
    }
    groups[group].push(value)
  }

  return groups
}

/**
 * Format year values for year-pills display
 * Sorts descending and limits to recent years
 */
export function formatYearFacetValues(
  values: AdminFacetValue[],
  maxYears = 10
): AdminFacetValue[] {
  // Sort by year descending (assuming value is year string)
  const sorted = [...values].sort((a, b) => {
    const yearA = parseInt(a.value, 10)
    const yearB = parseInt(b.value, 10)
    return yearB - yearA
  })

  // Limit to max years
  return sorted.slice(0, maxYears)
}

/**
 * Process tenant hierarchy for facet display
 * Groups tenants into main org, padaliniai (units), and PKP
 */
export function processTenantFacetValues(
  values: AdminFacetValue[]
): {
  main: AdminFacetValue[]
  padaliniai: AdminFacetValue[]
  pkp: AdminFacetValue[]
} {
  const main: AdminFacetValue[] = []
  const padaliniai: AdminFacetValue[] = []
  const pkp: AdminFacetValue[] = []

  values.forEach(tenant => {
    const value = tenant.value

    // VU SA main organization - exact match only
    if (value === 'VU SA') {
      main.push(tenant)
    }
    // PKP (programs, clubs, projects)
    else if (value.includes('PKP')) {
      pkp.push(tenant)
    }
    // VU SA Padaliniai (faculty units)
    else if (value.startsWith('VU SA ')) {
      padaliniai.push(tenant)
    }
    // Other organizations
    else {
      main.push(tenant)
    }
  })

  // Sort each group by count descending
  const sortByCount = (a: AdminFacetValue, b: AdminFacetValue) => b.count - a.count

  return {
    main: main.sort(sortByCount),
    padaliniai: padaliniai.sort(sortByCount),
    pkp: pkp.sort(sortByCount),
  }
}

/**
 * Format completion status for display
 */
export function formatCompletionStatus(status: string): string {
  const statusMap: Record<string, string> = {
    complete: 'Užbaigtas',
    incomplete: 'Neužbaigtas',
    partial: 'Dalinai užbaigtas',
  }
  return statusMap[status] || status
}

/**
 * Format vote alignment status for display
 */
export function formatVoteAlignmentStatus(status: string): string {
  const statusMap: Record<string, string> = {
    aligned: 'Atitinka',
    misaligned: 'Neatitinka',
    incomplete: 'Nepilna informacija',
    unknown: 'Nežinoma',
  }
  return statusMap[status] || status
}
