/**
 * Shared Search Services
 *
 * Barrel export for all shared search services.
 */

export { FacetMerger, type SelectionMap, type FilterKeyMapper } from './FacetMerger'
export { FilterUtils } from './FilterUtils'
export { ErrorUtils, QueryUtils, RecentSearchManager } from './SearchErrorUtils'
export {
  SearchClientFactory,
  SearchParamsBuilder,
  FilterBuilder,
  type TypesenseClient,
  type TypesenseSearchResponse,
  type ClientFactoryOptions
} from './SearchClientFactory'
