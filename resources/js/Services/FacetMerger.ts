import type { DocumentFacet, DocumentSearchFilters } from '@/Types/DocumentSearchTypes'

export class FacetMerger {
  /**
   * Merges initial facets (all available options) with current search facets (current results)
   * This ensures users can see all filter options even when current search has limited results
   */
  static mergeFacets(
    initialFacets: DocumentFacet[], 
    currentFacets: DocumentFacet[], 
    filters: DocumentSearchFilters
  ): DocumentFacet[] {
    if (initialFacets.length === 0) {
      return currentFacets
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
      const selectedValues = this.getSelectedValuesForField(initialFacet.field, filters)

      // Merge values: show ALL initial values with updated counts from current search
      const mergedValues = initialFacet.values.map(initialValue => {
        const currentCount = currentValueMap.get(initialValue.value) || 0
        const isSelected = selectedValues.includes(initialValue.value)
        
        return {
          ...initialValue,
          count: currentCount,
          // Mark as selected for styling/priority
          isSelected: isSelected
        }
      })

      // Add any new values from current search that weren't in initial facets
      if (currentFacet) {
        currentFacet.values.forEach(currentValue => {
          const existsInInitial = initialFacet.values.some(iv => iv.value === currentValue.value)
          if (!existsInInitial) {
            mergedValues.push({
              ...currentValue,
              isSelected: selectedValues.includes(currentValue.value)
            })
          }
        })
      }

      // Sort: selected items first, then by count (but keep zero counts), then alphabetically
      mergedValues.sort((a, b) => {
        const aSelected = selectedValues.includes(a.value)
        const bSelected = selectedValues.includes(b.value)
        
        if (aSelected && !bSelected) return -1
        if (!aSelected && bSelected) return 1
        if (a.count !== b.count) return b.count - a.count
        return a.value.localeCompare(b.value)
      })

      return {
        ...initialFacet,
        values: mergedValues // Show ALL values, don't filter any out
      }
    })
  }

  /**
   * Get selected values for a specific facet field from filters
   */
  private static getSelectedValuesForField(field: string, filters: DocumentSearchFilters): string[] {
    switch (field) {
      case 'tenant_shortname':
        return filters.tenants
      case 'content_type':
        return filters.contentTypes
      case 'language':
        return filters.languages
      default:
        return []
    }
  }

  /**
   * Process tenant hierarchy for display
   * Handles complex organizational structures in facets
   */
  static processTenantHierarchy(facets: DocumentFacet[]): DocumentFacet[] {
    return facets.map(facet => {
      if (facet.field !== 'tenant_shortname') {
        return facet
      }

      // Process tenant values for hierarchical display
      const processedValues = facet.values.map(value => ({
        ...value,
        label: this.formatTenantLabel(value.value),
        // Add hierarchy level for styling
        level: this.getTenantLevel(value.value)
      }))

      return {
        ...facet,
        values: processedValues
      }
    })
  }

  /**
   * Format tenant labels for better display
   */
  private static formatTenantLabel(tenantValue: string): string {
    // Handle common tenant abbreviations and formatting
    const labelMap: Record<string, string> = {
      'VU SA': 'Vilniaus universiteto Studentų atstovybė',
      'IF': 'Informatikos fakultetas',
      'MIF': 'Matematikos ir informatikos fakultetas',
      'GMC': 'Gyvybės mokslų centras'
    }

    return labelMap[tenantValue] || tenantValue
  }

  /**
   * Determine hierarchy level for tenant (for styling purposes)
   */
  private static getTenantLevel(tenantValue: string): number {
    // Main organization
    if (tenantValue === 'VU SA') return 0
    
    // Faculties and major units
    if (tenantValue.includes('fakultetas') || ['MIF', 'IF', 'GMC'].includes(tenantValue)) {
      return 1
    }
    
    // Sub-units
    return 2
  }

  /**
   * Filter facets based on search context
   * Removes empty facets or applies business rules
   */
  static filterFacetsForContext(facets: DocumentFacet[], hasActiveSearch: boolean): DocumentFacet[] {
    return facets.filter(facet => {
      // Always show facets with values
      if (facet.values.some(v => v.count > 0)) {
        return true
      }

      // During active search, hide empty facets unless they have selected values
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
  static calculateFacetStats(facets: DocumentFacet[]): {
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
      selectedValues
    }
  }

  /**
   * Sort facets by priority for display
   */
  static sortFacetsByPriority(facets: DocumentFacet[]): DocumentFacet[] {
    const priorityOrder = {
      'tenant_shortname': 1,
      'content_type': 2,
      'language': 3,
      'document_date': 4
    }

    return [...facets].sort((a, b) => {
      const aPriority = priorityOrder[a.field as keyof typeof priorityOrder] || 999
      const bPriority = priorityOrder[b.field as keyof typeof priorityOrder] || 999
      return aPriority - bPriority
    })
  }
}