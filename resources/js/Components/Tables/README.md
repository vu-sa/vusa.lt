# TanStack Data Tables - Complete Guide

Comprehensive documentation for TanStack table components in the vusa.lt project.

> **For AI Assistants**: See [CLAUDE.md](CLAUDE.md) for quick patterns and gotchas.

## Table of Contents

- [Overview](#overview)
- [Component Guide](#component-guide)
- [Configuration](#configuration)
- [Column Definitions](#column-definitions)
- [Server-Side Integration](#server-side-integration)
- [Migration Guide](#migration-guide)
- [Advanced Features](#advanced-features)
- [Examples](#examples)
- [Troubleshooting](#troubleshooting)

## Overview

### Two Table Systems

This project has two table systems:

1. **Legacy System** (Naive UI): `IndexModel/IndexDataTable.vue`
   - **DO NOT MODIFY** - stable, working legacy code
   - Used in older admin pages

2. **TanStack System** (Current): Components in `/Tables/` directory
   - **USE FOR ALL NEW FEATURES**
   - Modern, type-safe, performant
   - Better UX and maintainability

### Architecture

The table system uses a 4-layer architecture (simplified from 5):

```
IndexTablePage.vue          # Full page layout with header/actions
└── ServerDataTable.vue     # Server-side data management (Enhanced!)
    └── DataTableProvider.vue   # Client/server coordination
        └── DataTable.vue       # Core TanStack table
```

## Component Guide

### 1. IndexTablePage.vue

**When to use**: Full admin pages with headers, breadcrumbs, and page actions

**Features**:
- Page header with title and description
- Breadcrumb navigation
- Create button
- Custom filters
- Custom actions
- Column visibility toggle
- Show deleted toggle
- Model name translation
- Permission-based UI

**Example**:

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
    header-title="Institutions"
    header-description="Manage all institutions in the system"
    can-create
    enable-filtering
    enable-column-visibility
    allow-toggle-deleted
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

    <template #actions>
      <Button @click="exportData" variant="outline">
        Export Data
      </Button>
    </template>
  </IndexTablePage>
</template>

<script setup lang="ts">
import { IndexTablePage } from '@/Components/Tables';
import { Icons } from '@/Types/Icons/filled';

const props = defineProps<{
  institutions: PaginatedResponse<Institution>
  institutionTypes: InstitutionType[]
}>();

const columns = defineColumns(); // Your column definitions
const selectedTypeIds = ref<number[]>([]);

const updateFilter = (key: string, value: any) => {
  router.get(route('institutions.index'), {
    [key]: value
  }, { preserveState: true });
};
</script>
```

### 2. ServerDataTable.vue

**When to use**: Server-side tables without page wrapper

**Features**:
- Server-side pagination
- Server-side sorting
- Server-side filtering
- Empty state with create button
- Show deleted toggle
- Model name translations
- Admin-specific features built-in

**Example**:

```vue
<template>
  <ServerDataTable
    model-name="users"
    :data="users.data"
    :columns="columns"
    :total-count="users.total"
    :initial-page="users.current_page"
    enable-filtering
    can-create
    :create-route="route('users.create')"
    allow-toggle-deleted
  >
    <template #filters>
      <Select v-model="roleFilter">
        <SelectOption value="">All Roles</SelectOption>
        <SelectOption value="admin">Admin</SelectOption>
        <SelectOption value="user">User</SelectOption>
      </Select>
    </template>
  </ServerDataTable>
</template>

<script setup lang="ts">
import { ServerDataTable } from '@/Components/Tables';

const props = defineProps<{
  users: PaginatedResponse<User>
}>();

const roleFilter = ref('');
const columns = defineColumns();
</script>
```

### 3. SimpleDataTable.vue

**When to use**: Client-side tables with small datasets (< 100 items)

**Features**:
- Client-side sorting and filtering
- No server communication
- Lightweight and fast
- Good for modals, embedded tables, reports

**Example**:

```vue
<template>
  <SimpleDataTable
    :data="items"
    :columns="columns"
  />
</template>

<script setup lang="ts">
import { SimpleDataTable } from '@/Components/Tables';

const items = ref([
  { id: 1, name: 'Item 1', status: 'active' },
  { id: 2, name: 'Item 2', status: 'inactive' }
]);

const columns = defineColumns();
</script>
```

## Configuration

### Simplified TypeScript Types

We've simplified from 6 complex interfaces to 3 clear ones:

```typescript
// Essential props only
interface BaseTableConfig<TData> {
  data: TData[]
  columns: ColumnDef<TData, any>[]
  modelName?: string
}

// Sorting, filtering, row selection
interface AdvancedTableConfig {
  enableSorting?: boolean
  enableFiltering?: boolean
  enableRowSelection?: boolean
  enableMultiRowSelection?: boolean
}

// Page layout and UI customization
interface PageTableConfig {
  headerTitle?: string
  headerDescription?: string
  icon?: Component
  canCreate?: boolean
  createRoute?: string
}
```

### Configuration Presets

Use presets for quick setup:

```typescript
import { createTableConfig, TablePresets } from '@/Types/TableConfigTypes';

// Quick preset-based configuration
const config = createTableConfig('admin', {
  modelName: 'institutions',
  data: institutions.data,
  columns,
  totalCount: institutions.total,
  createRoute: route('institutions.create')
});

// Available presets:
TablePresets.readonly   // Minimal features, no actions
TablePresets.basic      // Search + create button
TablePresets.admin      // All features enabled
```

## Column Definitions

Use standard TanStack Table format with JSX/TSX for complex cells:

```typescript
import type { ColumnDef } from '@tanstack/vue-table';
import { Link } from '@inertiajs/vue3';

const columns: ColumnDef<User, any>[] = [
  {
    accessorKey: "name",
    header: () => $t("forms.fields.name"),
    cell: ({ row }) => (
      <Link
        href={route("users.edit", row.original.id)}
        class="hover:underline text-primary"
      >
        {row.getValue("name")}
      </Link>
    ),
    enableSorting: true,
  },
  {
    accessorKey: "email",
    header: () => $t("forms.fields.email"),
    cell: ({ row }) => (
      <span class="text-muted-foreground">
        {row.getValue("email")}
      </span>
    ),
  },
  {
    accessorKey: "created_at",
    header: () => $t("Created"),
    cell: ({ row }) => {
      const date = new Date(row.getValue("created_at"));
      return date.toLocaleDateString();
    },
    enableSorting: true,
  },
  {
    id: "actions",
    cell: ({ row }) => createStandardActionsColumn('users', {
      canEdit: true,
      canDelete: true,
      canRestore: true
    })
  }
];
```

### Standard Actions Column

Use the helper for consistent action columns:

```typescript
import { createStandardActionsColumn } from '@/Types/TableConfigTypes';

createStandardActionsColumn('modelName', {
  canEdit: true,
  canDelete: true,
  canRestore: true,
  confirmDelete: true, // default
  deleteConfirmTitle: 'Delete Item?',
  deleteConfirmMessage: 'This will permanently delete the item.'
})
```

## Server-Side Integration

### Backend Setup

The backend uses the `HasTanstackTables` trait (no changes needed!):

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Traits\HasTanstackTables;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    use HasTanstackTables;

    public function index(IndexInstitutionRequest $request)
    {
        // Start with base query
        $query = Institution::query()->with(['tenant', 'types']);

        // Apply TanStack filters (search, sort, pagination)
        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            ['name', 'alias', 'email', 'tenant.name'], // searchable columns
            [
                'tenantRelation' => 'tenant',
                'permission' => 'institutions.read.padalinys'
            ]
        );

        // Paginate
        $institutions = $query->paginate($request->input('per_page', 15));

        // Return Inertia response
        return Inertia::render('Admin/People/IndexInstitution', [
            'data' => $institutions->items(),
            'meta' => [
                'total' => $institutions->total(),
                'current_page' => $institutions->currentPage(),
                'per_page' => $institutions->perPage(),
            ]
        ]);
    }
}
```

### Request Classes

Form request classes handle sorting and filtering:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:5|max:100',
            'page' => 'nullable|integer|min:1',
            'sorting' => 'nullable|json',
            'filters' => 'nullable|json'
        ];
    }

    public function getSorting(): array
    {
        return json_decode($this->input('sorting', '[]'), true) ?: [];
    }

    public function getFilters(): array
    {
        return json_decode($this->input('filters', '{}'), true) ?: [];
    }
}
```

## Migration Guide

### From Legacy (Naive UI) Tables

**Important**: Don't migrate existing Naive UI tables unless there's a business need. They work fine!

For new features, use TanStack:

```vue
<!-- Old (Naive UI) -->
<IndexDataTable
  :columns="naiveColumns"
  :paginated-models="users"
  model-name="users"
/>

<!-- New (TanStack) -->
<ServerDataTable
  model-name="users"
  :data="users.data"
  :columns="tanstackColumns"
  :total-count="users.total"
  enable-filtering
/>
```

### From Old TanStack Setup

Existing TanStack usage continues to work. Optimize by:

1. Using new configuration presets
2. Leveraging simplified types
3. Adopting IndexTablePage.vue for full-page layouts

## Advanced Features

### Custom Filters

Add custom filters using the `#filters` slot:

```vue
<template>
  <ServerDataTable
    model-name="users"
    :data="users.data"
    :columns="columns"
    :total-count="users.total"
  >
    <template #filters>
      <!-- Date range filter -->
      <DateRangePicker
        v-model="dateRange"
        @update:model-value="updateDateFilter"
      />

      <!-- Multi-select filter -->
      <Select v-model="statusFilter" multiple>
        <SelectOption value="active">Active</SelectOption>
        <SelectOption value="inactive">Inactive</SelectOption>
      </Select>

      <!-- Custom dropdown -->
      <DataTableFilter
        v-model:value="selectedRoles"
        :options="roleOptions"
        @update:value="updateRoleFilter"
      >
        Roles
      </DataTableFilter>
    </template>
  </ServerDataTable>
</template>

<script setup lang="ts">
const dateRange = ref<[Date, Date] | null>(null);
const statusFilter = ref<string[]>([]);
const selectedRoles = ref<number[]>([]);

const updateDateFilter = (range: [Date, Date] | null) => {
  router.get(route('users.index'), {
    start_date: range?.[0].toISOString(),
    end_date: range?.[1].toISOString()
  }, { preserveState: true });
};

const updateRoleFilter = (roleIds: number[]) => {
  router.get(route('users.index'), {
    roles: roleIds
  }, { preserveState: true });
};
</script>
```

### Row Selection

Enable row selection for bulk actions:

```vue
<template>
  <ServerDataTable
    model-name="users"
    :data="users.data"
    :columns="columns"
    :total-count="users.total"
    enable-row-selection
    enable-multi-row-selection
    @update:row-selection="handleSelectionChange"
  >
    <template #actions>
      <Button
        v-if="selectedRows.length > 0"
        @click="bulkDelete"
        variant="destructive"
      >
        <TrashIcon class="h-4 w-4 mr-2" />
        Delete {{ selectedRows.length }} items
      </Button>

      <Button
        v-if="selectedRows.length > 0"
        @click="bulkExport"
        variant="outline"
      >
        <DownloadIcon class="h-4 w-4 mr-2" />
        Export Selected
      </Button>
    </template>
  </ServerDataTable>
</template>

<script setup lang="ts">
const selectedRows = ref<User[]>([]);

const handleSelectionChange = (selection: Record<string, boolean>) => {
  const selectedIds = Object.keys(selection);
  selectedRows.value = users.data.filter(u => selectedIds.includes(u.id.toString()));
};

const bulkDelete = () => {
  router.delete(route('users.bulk-destroy'), {
    data: { ids: selectedRows.value.map(r => r.id) },
    onSuccess: () => selectedRows.value = []
  });
};
</script>
```

### Custom Actions

Add custom page actions:

```vue
<template>
  <IndexTablePage
    model-name="institutions"
    :data="institutions.data"
    :columns="columns"
    :total-count="institutions.total"
  >
    <template #actions>
      <Button @click="exportData" variant="outline">
        <DownloadIcon class="h-4 w-4 mr-2" />
        Export Data
      </Button>

      <Button @click="importData" variant="outline">
        <UploadIcon class="h-4 w-4 mr-2" />
        Import Data
      </Button>

      <Button @click="generateReport" variant="secondary">
        <FileTextIcon class="h-4 w-4 mr-2" />
        Generate Report
      </Button>
    </template>
  </IndexTablePage>
</template>
```

## Examples

### Simple Read-Only Table

```vue
<template>
  <ServerDataTable
    model-name="reports"
    :data="reports.data"
    :columns="columns"
    :total-count="reports.total"
    :enable-filtering="false"
    :can-create="false"
  />
</template>

<script setup lang="ts">
import { ServerDataTable } from '@/Components/Tables';

const columns: ColumnDef<Report, any>[] = [
  {
    accessorKey: "title",
    header: () => "Report Title"
  },
  {
    accessorKey: "created_at",
    header: () => "Created",
    cell: ({ row }) => new Date(row.getValue("created_at")).toLocaleDateString()
  }
];
</script>
```

### Full-Featured Admin Table

```vue
<template>
  <IndexTablePage
    model-name="institutions"
    :data="institutions.data"
    :columns="columns"
    :total-count="institutions.total"
    :create-route="route('institutions.create')"
    header-title="Institutions"
    header-description="Manage all institutions across tenants"
    :icon="Icons.INSTITUTION"
    can-create
    enable-filtering
    enable-column-visibility
    enable-row-selection
    allow-toggle-deleted
    @filter-changed="handleFilterChange"
  >
    <template #filters>
      <DataTableFilter
        v-model:value="selectedTypes"
        :options="institutionTypes.map(t => ({ label: t.name, value: t.id }))"
        @update:value="updateFilter('types', $event)"
      >
        {{ $tChoice('forms.fields.type', 2) }}
      </DataTableFilter>

      <DataTableFilter
        v-model:value="selectedTenants"
        :options="tenants.map(t => ({ label: t.name, value: t.id }))"
        @update:value="updateFilter('tenants', $event)"
      >
        {{ $tChoice('models.tenant.name', 2) }}
      </DataTableFilter>
    </template>

    <template #actions>
      <Button @click="exportData" variant="outline">
        <DownloadIcon class="h-4 w-4 mr-2" />
        Export
      </Button>
    </template>
  </IndexTablePage>
</template>

<script setup lang="ts">
import { IndexTablePage } from '@/Components/Tables';
import { Icons } from '@/Types/Icons/filled';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
  institutions: PaginatedResponse<Institution>
  institutionTypes: InstitutionType[]
  tenants: Tenant[]
}>();

const selectedTypes = ref<number[]>([]);
const selectedTenants = ref<number[]>([]);

const columns: ColumnDef<Institution, any>[] = [
  {
    accessorKey: "name",
    header: () => $t("forms.fields.name"),
    cell: ({ row }) => (
      <Link href={route("institutions.edit", row.original.id)} class="hover:underline">
        {row.getValue("name")}
      </Link>
    ),
    enableSorting: true
  },
  {
    accessorKey: "email",
    header: () => $t("forms.fields.email"),
    enableSorting: true
  },
  {
    accessorKey: "tenant.name",
    header: () => $t("models.tenant.name"),
    enableSorting: true
  },
  {
    id: "actions",
    cell: ({ row }) => createStandardActionsColumn('institutions', {
      canEdit: true,
      canDelete: true,
      canRestore: true
    })
  }
];

const updateFilter = (key: string, value: any) => {
  router.get(route('institutions.index'), {
    [key]: value
  }, { preserveState: true, preserveScroll: true });
};

const exportData = () => {
  router.get(route('institutions.export'), {}, {
    preserveState: true
  });
};
</script>
```

## Troubleshooting

### Component not updating after data change

**Problem**: Table doesn't refresh when data changes

**Solution**: Add `:key` to force re-render

```vue
<ServerDataTable :key="refreshKey" ... />
```

Or use Inertia's `preserveState`:

```typescript
router.get(route('index'), filters, {
  preserveState: false  // Force full page reload
});
```

### Filters not working

**Problem**: Filtering doesn't affect results

**Solution**: Ensure request class implements required methods

```php
public function getFilters(): array {
    return json_decode($this->input('filters', '{}'), true) ?: [];
}

public function getSorting(): array {
    return json_decode($this->input('sorting', '[]'), true) ?: [];
}
```

### TypeScript errors with columns

**Problem**: Type errors when defining columns

**Solution**: Use proper typing

```typescript
import type { ColumnDef } from '@tanstack/vue-table';

const columns: ColumnDef<ModelType, any>[] = [
  // your columns
];
```

### Events not firing

**Problem**: Custom events not triggering

**Solution**: Use kebab-case for event names

```vue
@filter-changed="handler"  <!-- ✅ Correct -->
@filterChanged="handler"   <!-- ❌ Wrong -->
```

### Checkbox binding errors

**Problem**: Checkbox v-model not working

**Solution**: Use `modelValue`, not `checked`

```vue
<Checkbox v-model="isChecked" />  <!-- ✅ Correct -->
<Checkbox v-model:checked="isChecked" />  <!-- ❌ Wrong -->
```

### Debug Mode

Set `APP_DEBUG=true` in `.env` to see detailed table state in the browser console.

## Performance Tips

1. **Use server-side tables** for datasets > 100 items
2. **Set appropriate page sizes**:
   - 10-15 for complex rows with many columns
   - 25-50 for simple data
3. **Implement proper eager loading** in backend queries to avoid N+1 problems
4. **Use column width constraints** to prevent layout shifts
5. **Debounce search inputs** (handled automatically by ServerDataTable)
6. **Enable only needed features** - disable sorting/filtering if not required

## Getting Help

1. **Check this documentation** - covers most use cases
2. **Look at existing implementations** in `resources/js/Pages/Admin/`
3. **Check the backend** services in `app/Services/TanstackTableService.php`
4. **Review controller implementations** in `app/Http/Controllers/Admin/`
5. **Ask for help** in team chat with:
   - What you're trying to do
   - What's happening (error messages, screenshots)
   - What you've tried

## Summary

The TanStack table system provides:

✅ **Reduced complexity**: 4 component layers (down from 5)
✅ **Simplified types**: 3 core interfaces (down from 6)
✅ **Configuration presets**: readonly, basic, admin
✅ **Enhanced functionality**: Comprehensive admin features
✅ **Better performance**: Optimized rendering and state management
✅ **Type safety**: Full TypeScript support
✅ **Consistent UX**: Standardized patterns across all tables

The system is maintainable, performant, and easy to use!
