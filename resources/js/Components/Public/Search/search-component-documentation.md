# Search Component System Documentation

## Overview

The Search component system provides a comprehensive, multi-index search functionality for the VUSA.lt platform using Typesense and Vue InstantSearch. It enables users to search across News, Pages, Documents, and Calendar events with real-time filtering and result management.

## Architecture

### Core Components

#### 1. `SearchButton.vue`
**Purpose**: Entry point trigger for opening search dialog
**Features**:
- Accessible button with proper ARIA attributes
- Keyboard shortcut support
- Icon-based search trigger
- Fetches Typesense configuration on mount

#### 2. `TypesenseSearch.vue` (Main Search Dialog)
**Purpose**: Primary search interface and orchestration
**Features**:
- Full-screen dialog with responsive design
- Search input with debounced queries (300ms, 3+ character minimum)
- Unified and grouped result views
- Keyboard navigation (Ctrl/Cmd+K to open, ESC to close)
- Recent search tracking (localStorage)
- Analytics tracking integration

#### 3. `SearchFilters.vue`
**Purpose**: Advanced filtering and result organization
**Features**:
- Content type toggles (News, Pages, Documents, Calendar)
- Result ordering (Relevance, Date, Type)
- Group/unified results toggle
- Recent searches management
- Color-coded content type indicators
- Result count badges per content type

#### 4. `SearchResultSection.vue`
**Purpose**: Individual content type result display
**Features**:
- Infinite scroll loading with `AisInfiniteHits`
- Highlighted search terms
- Type-specific icons and styling
- Date formatting for temporal content
- Load more functionality
- Item selection states

#### 5. `UnifiedResults.vue`
**Purpose**: Cross-index unified search results
**Features**:
- Merged results from multiple indices
- Configurable result limits
- Performance-optimized rendering
- Sorting across content types

#### 6. Supporting Components
- `CompactListItem.vue`: Compact result item layout
- `EmptyState.vue`: No results state with clear action

### Backend Integration

#### Searchable Models
1. **News** (`app/Models/News.php`)
   - Fields: title, short, permalink, image, publish_time, lang, tenant_name
   - Only published (non-draft) news indexed
   - Uses Typesense engine

2. **Documents** (`app/Models/Document.php`)
   - Fields: title, summary, language, institution_name, document_date, anonymous_url
   - Only publicly accessible documents (with anonymous_url)
   - Institution names in both LT/EN

3. **Pages** (`app/Models/Page.php`)
   - Fields: title, permalink, lang, tenant_name, category_name
   - Only published (non-draft) pages
   - Category-based organization

4. **Calendar** (`app/Models/Calendar.php`)
   - Fields: title_lt, title_en, date, end_date, lang, tenant_name
   - Only published (non-draft) events
   - Multi-language title support

#### API Endpoints
- `GET /api/v1/typesense/config` - Returns frontend Typesense configuration
- Handled by `TypesenseManager::getFrontendConfig()`

## State Management

### `useSearchController.ts`
Central state management composable that handles:

```typescript
interface SearchState {
  query: string
  selectedTypes: ContentType[]
  resultOrder: 'relevance' | 'date' | 'type'
  groupResults: boolean
  isSearching: boolean
  hasResults: boolean
}
```

**Key Features**:
- Persistent user preferences via localStorage
- Content type enable/disable management
- Result counting across indices
- Recent search tracking
- Default content type configuration

## Current Features

### Search Functionality
- ✅ Real-time search with debouncing
- ✅ Multi-index searching (News, Pages, Documents, Calendar)
- ✅ Highlighted search terms
- ✅ Minimum 3-character query requirement
- ✅ Search result statistics

### User Interface
- ✅ Responsive dialog design
- ✅ Content type filtering with color coding
- ✅ Result ordering options
- ✅ Infinite scroll loading
- ✅ Empty state handling
- ✅ Loading states
- ✅ Keyboard navigation support

### Accessibility
- ✅ ARIA attributes for screen readers
- ✅ Keyboard shortcuts (Ctrl/Cmd+K)
- ✅ Focus management
- ✅ Semantic HTML structure
- ✅ Color contrast compliance

### Performance
- ✅ Debounced search queries
- ✅ Configurable result limits
- ✅ Efficient re-rendering patterns
- ✅ Background Typesense config loading

### Localization
- ✅ Full Lithuanian/English support
- ✅ Translatable UI elements
- ✅ Multi-language content indexing

## UX Enhancement Opportunities

### 1. Search Experience Improvements
- **Auto-complete/Suggestions**: Add `AisAutocomplete` for query suggestions
- **Voice Search**: Implement `AisVoiceSearch` for accessibility
- **Search History**: Enhanced recent searches with categories
- **Search Analytics**: Track popular queries and improve relevance

### 2. Filtering Enhancements
- **Date Range Filters**: Use `AisRangeSlider` for date-based filtering
- **Faceted Search**: Add `AisRefinementList` for tags, categories, institutions
- **Hierarchical Categories**: Implement `AisHierarchicalMenu` for nested categories
- **Current Filters Display**: Add `AisCurrentRefinements` for active filter visibility

### 3. Result Presentation
- **Preview Mode**: Rich previews with thumbnails and excerpts
- **Pagination Options**: Add `AisPagination` as alternative to infinite scroll
- **Search Result URLs**: Deep-linkable search states
- **Export/Share**: Share search results and save searches

### 4. Advanced Features
- **Fuzzy Search**: Improve typo tolerance
- **Synonyms**: Better semantic matching
- **Personalization**: User-specific result ranking
- **Search Scope**: Institution/tenant-specific searching

## Recommended AisWidgets for Enhancement

### High Priority
1. **`AisAutocomplete`** - Query suggestions and instant results
2. **`AisCurrentRefinements`** - Show active filters with clear options
3. **`AisRefinementList`** - Faceted filtering by tags, categories, institutions
4. **`AisRangeSlider`** - Date range filtering for temporal content

### Medium Priority
5. **`AisVoiceSearch`** - Accessibility and mobile UX improvement
6. **`AisSortBy`** - More granular sorting options
7. **`AisToggleRefinement`** - Binary filters (e.g., "Has images", "Recent only")
8. **`AisBreadcrumb`** - Navigation context for filtered states

### Advanced Features
9. **`AisHierarchicalMenu`** - Nested category navigation
10. **`AisQueryRuleContext`** - Dynamic result customization
11. **`AisQueryRuleCustomData`** - Personalized search experiences

## Performance Considerations

### Current Optimizations
- Debounced search input (300ms)
- Minimum query length (3 characters)
- Efficient result transformation
- Component lazy loading preparation

### Recommended Improvements
- Implement virtual scrolling for large result sets
- Add result caching for common queries
- Optimize bundle size with dynamic imports
- Add search analytics for performance monitoring

## Testing Strategy

### Current Gaps
- No dedicated search component tests
- Limited integration testing
- Missing accessibility testing

### Recommended Tests
- Unit tests for search controller logic
- Integration tests for Typesense connectivity
- E2E tests for complete search workflows
- Accessibility testing with screen readers
- Performance testing with large datasets

## Security Considerations

- ✅ Search-only API key usage
- ✅ Public content filtering at model level
- ✅ XSS prevention through proper escaping
- ✅ Rate limiting via Typesense configuration

## Future Roadmap

### Phase 1 (Current)
- ✅ Basic multi-index search
- ✅ Content type filtering
- ✅ Result highlighting

### Phase 2 (Recommended Next)
- [ ] Add `AisAutocomplete` for suggestions
- [ ] Implement `AisCurrentRefinements` for filter visibility
- [ ] Add `AisRefinementList` for tags and categories
- [ ] Enhance mobile experience

### Phase 3 (Advanced)
- [ ] Voice search integration
- [ ] Advanced analytics
- [ ] Personalization features
- [ ] Search result deep linking

## Component Dependencies

```typescript
// Core Vue InstantSearch components in use
AisInstantSearch, AisSearchBox, AisIndex, AisConfigure, 
AisInfiniteHits, AisHighlight, AisStats

// UI Framework
Shadcn Vue components (Dialog, Button, Input, Badge, etc.)

// State Management
Vue 3 Composition API, Pinia-style composables

// External Libraries
Typesense InstantSearch Adapter, date-fns, lodash-es
```

This comprehensive system provides a solid foundation for search functionality with clear paths for enhancement and excellent user experience potential.
