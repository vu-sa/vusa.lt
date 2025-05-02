# Data Tables

This document outlines the standardized approach to data tables in the vusa.lt project.

## Table Architecture

The data table system is built on several layers of abstraction to provide flexibility while maintaining consistency:

```
IndexTablePage
└── AdminDataTable
    └── ServerDataTable
        └── DataTableProvider
            └── DataTable (Tanstack Table)
```

### Components

1. **DataTable** - Base component wrapping Tanstack Table's Vue adapter
   - Handles core table rendering and client-side functionality
   - Provides client-side sorting, filtering, and pagination
   - Renders the table UI with proper styles

2. **DataTableProvider** - Bridge component that handles:
   - Switching between client-side and server-side modes
   - Layout for filters and actions slots
   - Custom pagination for server-side tables
   - Column visibility management

3. **ServerDataTable** - Server-side integration component:
   - Manages the data fetching lifecycle with Inertia.js
   - Maintains server pagination, sorting, and filtering state
   - Handles URL parameters and data reloading
   - Provides search input and filtering UI

4. **AdminDataTable** - Admin-specific UI component:
   - Adds standard admin features (create button, deleted items toggle)
   - Provides translation of entity names and model identifiers
   - Forwards events from the underlying ServerDataTable

5. **IndexTablePage** - Complete page layout:
   - Provides header and standard page layout
   - Handles i18n conventions for model names
   - Forwards events and maintains component hierarchy

## Component Responsibilities

### `DataTable`
- Core table rendering
- Client-side filtering, sorting, pagination
- Column visibility controls
- Empty state handling

### `DataTableProvider`
- Handles layout for filters and action buttons
- Supports both client-side and server-side modes
- Manages server-side pagination controls
- Forwards slots to the base table

### `ServerDataTable`
- **State Management**: Maintains pagination, sorting, and filtering state
- **Server Communication**: Handles Inertia.js data fetching
- **Data Loading**: Manages the loading lifecycle
- **URL Parameter Handling**: Encodes and decodes URL parameters
- **Search Field**: Provides global search functionality

### `AdminDataTable`
- Create button with permissions check
- Show deleted items toggle
- Standardized empty states
- Forwarding slots from parent components

### `IndexTablePage`
- Page header with title and description
- Standard layout for index pages
- Localization of entity names
- Event delegation to underlying components

## Data Flow

The component hierarchy follows a clear data flow:

1. **Props Down**: Each component passes necessary props down to its child components
2. **Events Up**: Events bubble up through the hierarchy with `emit`
3. **Refs Across**: Parent components can access child component methods via refs

This approach ensures that:
- Each component maintains only the state it needs
- Common operations can be triggered at any level
- The hierarchy is maintainable and clear

## Usage Examples

### Basic Usage (Client-side)

```vue
<template>
  <DataTableProvider
    :columns="columns"
    :data="myData"
    :enable-pagination="true"
    :page-size="10"
    enable-filtering
  />
</template>

<script setup lang="tsx">
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import DataTableProvider from '@/Components/ui/data-table/DataTableProvider.vue';

const myData = ref([/* your data */]);

const columns = [
  {
    accessorKey: "name",
    header: () => $t("forms.fields.name"),
    cell: ({ row }) => row.getValue("name")
  },
  // More columns...
];
</script>
```

### Server-side Table

```vue
<template>
  <ServerDataTable
    model-name="users"
    :columns="columns"
    :data="users.data"
    :total-count="users.total"
    :initial-page="users.current_page"
    :page-size="users.per_page"
    enable-filtering
    enable-column-visibility
    @data-loaded="handleDataLoaded"
  />
</template>

<script setup>
const handleDataLoaded = (data) => {
  console.log('Data refreshed:', data);
};
</script>
```

### Complete Admin Page Example

```vue
<template>
  <IndexTablePage
    model-name="institutions"
    entity-name="institution"
    :icon="Icons.INSTITUTION"
    :data="institutions.data"
    :columns="columns"
    :total-count="institutions.total"
    :initial-page="institutions.current_page"
    :page-size="institutions.per_page"
    :create-route="route('institutions.create')"
    can-create
    enable-filtering
    enable-column-visibility
    @filter-changed="handleFilterChange"
  >
    <template #filters>
      <DataTableFilter
        v-model:value="selectedTypeIds"
        :options="typeOptions"
        @update:value="updateFilter('type', $event)"
      >
        {{ $tChoice('forms.fields.type', 2) }}
      </DataTableFilter>
    </template>
  </IndexTablePage>
</template>
```

## Column Definitions

Columns follow the Tanstack Table format:

```ts
const columns = [
  {
    // Accessor key matches the property name in your data
    accessorKey: "name",
    
    // Header can be a string or a function that returns a localized string
    header: () => $t("forms.fields.name"),
    
    // Cell renderer - can return plain text or JSX/TSX
    cell: ({ row }) => (
      <a href={route("users.edit", row.original.id)} class="hover:underline">
        {row.getValue("name")}
      </a>
    ),
    
    // Optional width/size
    size: 200,
    
    // Enable sorting (default: false)
    enableSorting: true,
    
    // Enable column filtering (default: false)
    enableColumnFilter: true
  }
];
```

## Filtering

### Global Search

The global search field is automatically included in both DataTable (client-side) and ServerDataTable (server-side) components when `enable-filtering` is set to true.

### Custom Filters

Use the `DataTableFilter` component for adding filters to your tables:

```vue
<DataTableFilter
  v-model:value="selectedTypeIds"
  :options="typeOptions"
  @update:value="handleTypeFilterChange"
>
  {{ $tChoice('forms.fields.type', 2) }}
</DataTableFilter>
```

## Client vs. Server-side Tables

### When to use client-side tables

Use the `DataTableProvider` with `is-server-side="false"` (default) when:
- You have small datasets (< 100 items)
- All data is already loaded in the browser
- For local filtering/sorting only

### When to use server-side tables

Use the `ServerDataTable` component when:
- You have large datasets 
- You need pagination from the server
- You need filters to query the database

## Adding Custom Server-Side Filters

When you need to add custom filters to a server-side table:

1. Define local state in your page component:
```ts
const selectedTypeIds = ref<number[]>([]);
```

2. Add the filter component in the `filters` slot:
```vue
<template #filters>
  <DataTableFilter
    v-model:value="selectedTypeIds"
    :options="typeOptions"
    @update:value="handleTypeFilterChange"
  />
</template>
```

3. Create a handler to update the filter:
```ts
const handleTypeFilterChange = (value) => {
  // Update local state
  selectedTypeIds.value = value;
  
  // Update the server table filter
  tableRef.value.updateFilter('types.id', value);
};
```

This approach ensures filter state is properly maintained in both the parent component and the table component.

## Best Practices

1. **Component Selection**:
   - For simple tables with all data available: `DataTable` or `DataTableProvider`
   - For server-paginated tables: `ServerDataTable`
   - For admin index pages: `IndexTablePage`

2. **Localization**:
   - Always use `$t` and `$tChoice` for text
   - Use proper entity name translations

3. **TypeScript**:
   - Leverage TypeScript generics for type safety
   - Define proper interfaces for your data models

4. **Performance**:
   - For large datasets, always use server-side tables
   - Set appropriate page sizes
   - Use column width constraints to avoid layout shifts

5. **Slot Usage**:
   - Use the `filters` slot for custom filter components
   - Use the `actions` slot for table actions
   - Use the `empty` slot for custom empty states

6. **Event Handling**:
   - Listen for the `data-loaded` event to perform actions after data loads
   - Use the `sorting-changed`, `filter-changed` and `page-changed` events to respond to user interactions

7. **Consistency**:
   - Follow the established component hierarchy
   - Maintain consistent naming conventions
   - Ensure proper data flow through props and events