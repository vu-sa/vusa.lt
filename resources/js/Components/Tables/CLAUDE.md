# TanStack Data Tables

This file provides guidance on using the simplified TanStack table components in the vusa.lt project.

## 🚨 Important Note on Table Systems

**Two table systems exist in this project:**

1. **Legacy System** (Naive UI): `IndexModel/IndexDataTable.vue` - **DO NOT MODIFY**
2. **TanStack System** (Current): Components in `/Tables/` directory - **Use for new features**

## Quick Start Guide

### Which Component Should I Use?

```bash
# Decision Tree:
Full admin page with header/actions/breadcrumbs? → IndexTablePage.vue
Server-side table without page wrapper? → ServerDataTable.vue  
Simple client-side table? → SimpleDataTable.vue
```

## Simplified Architecture (4 Layers)

The table system has been **simplified from 5 to 4 layers**:

```
IndexTablePage.vue          # Full page layout with header/actions
└── ServerDataTable.vue     # Server-side data management (Enhanced!)
    └── DataTableProvider.vue   # Client/server coordination  
        └── DataTable.vue       # Core TanStack table
```

## Component Guide

### 1. IndexTablePage.vue
**Use for**: Full admin pages with headers, breadcrumbs, and page actions

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
  </IndexTablePage>
</template>
```

### 2. ServerDataTable.vue (Enhanced!)
**Use for**: Server-side tables without page wrapper

**✨ Includes comprehensive admin functionality:**
- Empty state with create button
- Show deleted toggle
- Model name translations
- Admin-specific features

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
      </Select>
    </template>
  </ServerDataTable>
</template>
```

### 3. SimpleDataTable.vue
**Use for**: Client-side tables with simple data

Perfect for small datasets that don't require server-side processing.

## Configuration Made Simple

### New Simplified Types

**Before** (6 complex interfaces):
```typescript
TableConfig, PaginationConfig, UIConfig, FilteringConfig, RowSelectionConfig, IndexTablePageProps
```

**After** (3 clear interfaces):
```typescript
BaseTableConfig<TData>      // Essential props only
AdvancedTableConfig         // Sorting, filtering, row selection  
PageTableConfig            // Page layout and UI customization
```

### Using Configuration Presets

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
const columns = [
  {
    accessorKey: "name",
    header: () => $t("forms.fields.name"),
    cell: ({ row }) => (
      <Link href={route("users.edit", row.original.id)} class="hover:underline">
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
    cell: ({ row }) => new Date(row.getValue("created_at")).toLocaleDateString(),
    enableSorting: true,
  }
];
```

## Server-Side Integration

### Backend Setup (No Changes Needed!)

The existing backend services are excellent and remain unchanged:

```php
// In your controller
use App\Http\Traits\HasTanstackTables;

class InstitutionController extends Controller 
{
    use HasTanstackTables;
    
    public function index(IndexInstitutionRequest $request) 
    {
        $query = Institution::query()->with(['tenant', 'types']);
        
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
        
        $institutions = $query->paginate($request->input('per_page', 15));
        
        return Inertia::render('Admin/People/IndexInstitution', [
            'data' => $institutions->items(),
            'meta' => [/* pagination meta */]
        ]);
    }
}
```

### Request Classes

```php
// IndexInstitutionRequest.php
class IndexInstitutionRequest extends FormRequest 
{
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

**DON'T migrate existing Naive UI tables** unless there's a specific business need. They work fine.

**For new features**, use TanStack tables:

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

Existing TanStack table usage continues to work with the current system. You can optimize by:

1. **Use new configuration presets** for simpler setup
2. **Leverage new simplified types** for better TypeScript experience
3. **Adopt IndexTablePage.vue** for full-page table layouts

## Advanced Features

### Custom Filters

```vue
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
</template>

<script setup>
const updateDateFilter = (range) => {
  tableRef.value.updateFilter('created_at', range);
};
</script>
```

### Row Selection

```vue
<ServerDataTable
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
      Delete {{ selectedRows.length }} items
    </Button>
  </template>
</ServerDataTable>

<script setup>
const selectedRows = ref([]);
const handleSelectionChange = (selection) => {
  selectedRows.value = Object.keys(selection);
};
</script>
```

## Performance Tips

1. **Use server-side tables** for datasets > 100 items
2. **Set appropriate page sizes**: 10-15 for complex rows, 25-50 for simple data
3. **Implement proper eager loading** in your backend queries
4. **Use column width constraints** to prevent layout shifts
5. **Debounce search inputs** (automatically handled by ServerDataTable)

## Troubleshooting

### Common Issues

**1. "Component not updating after data change"**
```vue
<!-- Make sure to pass :key for reactive updates -->
<ServerDataTable :key="refreshKey" ... />
```

**2. "Filters not working"**
```php
// Make sure your request class implements getSorting() and getFilters()
public function getFilters(): array {
    return json_decode($this->input('filters', '{}'), true) ?: [];
}
```

**3. "TypeScript errors with columns"**
```typescript
// Use proper typing for column definitions
const columns: ColumnDef<UserType, any>[] = [
  // your columns
];
```

**4. "Events not firing"**
```vue
<!-- Use kebab-case for event names -->
@filter-changed="handleFilterChange"  <!-- ✅ Correct -->
@filterChanged="handleFilterChange"   <!-- ❌ Wrong -->
```

### Debug Mode

Set `APP_DEBUG=true` in your `.env` to see detailed table state information in the browser console.

## Getting Help

1. **Check this documentation first** - covers 90% of use cases
2. **Look at existing implementations** in `resources/js/Pages/Admin/`
3. **Check the Laravel backend** services in `app/Services/TanstackTableService.php`
4. **Ask for help** in team chat with specific error messages

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
    icon="building"
    can-create
    enable-filtering
    enable-column-visibility
    enable-row-selection
    allow-toggle-deleted
  >
    <template #filters>
      <Select v-model="typeFilter">
        <SelectOption value="">All Types</SelectOption>
        <SelectOption v-for="type in institutionTypes" :key="type.id" :value="type.id">
          {{ type.name }}
        </SelectOption>
      </Select>
    </template>
    
    <template #actions>
      <Button @click="exportData" variant="outline">
        Export Data
      </Button>
    </template>
  </IndexTablePage>
</template>
```

---

## Important Notes

### Checkbox Component Usage

**Important**: The `Checkbox.vue` component uses `modelValue` instead of `checked` for v-model binding:

```vue
<!-- ✅ Correct -->
<Checkbox v-model="isChecked" />
<Checkbox :modelValue="isChecked" @update:modelValue="handleChange" />

<!-- ❌ Incorrect -->
<Checkbox v-model:checked="isChecked" />
<Checkbox :checked="isChecked" @update:checked="handleChange" />
```

This follows Vue 3's standard v-model naming convention.

## Delete Confirmation System

### Reusable Delete Confirmation

The project provides a reusable delete confirmation system that can be used in tables, forms, and other components:

#### 1. For Table Actions (Automatic)

Table actions automatically show delete confirmation by default:

```vue
<script setup>
const columns = [
  // ... other columns
  createStandardActionsColumn('institutions', {
    canDelete: true,
    canRestore: true,
    // Confirmation is enabled by default
    confirmDelete: true, // optional, defaults to true
    deleteConfirmTitle: 'Delete Institution?', // optional
    deleteConfirmMessage: 'This will permanently delete the institution and all related data.' // optional
  })
];
</script>
```

#### 2. For Forms and Custom Components

Use the `useDeleteConfirmation` composable:

```vue
<template>
  <div>
    <Button variant="destructive" @click="handleDelete">
      Delete Item
    </Button>
    
    <DeleteConfirmationDialog
      :is-open="deleteConfirmation.isOpen.value"
      :title="deleteConfirmation.title"
      :message="deleteConfirmation.message"
      :is-deleting="deleteConfirmation.isDeleting.value"
      @confirm="deleteConfirmation.executeDelete"
      @cancel="deleteConfirmation.cancelDelete"
      @update:open="deleteConfirmation.isOpen.value = $event"
    />
  </div>
</template>

<script setup>
import { useDeleteConfirmation } from '@/Composables/useDeleteConfirmation';
import DeleteConfirmationDialog from '@/Components/Dialogs/DeleteConfirmationDialog.vue';

const deleteConfirmation = useDeleteConfirmation({
  title: 'Delete Institution?',
  message: 'This will permanently delete the institution and all related data.',
  successMessage: 'Institution deleted successfully',
  errorMessage: 'Failed to delete institution'
});

const handleDelete = () => {
  // Option 1: Using Inertia.js helper
  deleteConfirmation.deleteWithInertia(route('institutions.destroy', institution.id), {
    onSuccess: () => {
      // Handle success
    },
    onError: (errors) => {
      // Handle errors
    }
  });
  
  // Option 2: Custom delete logic
  deleteConfirmation.confirmDelete(() => {
    // Your custom delete logic here
    console.log('Deleting item...');
  });
};
</script>
```

#### 3. Advanced Usage

```vue
<script setup>
// Disable confirmation for quick actions
const deleteConfirmation = useDeleteConfirmation({
  confirm: false, // Skip confirmation dialog
  successMessage: 'Item deleted',
});

// Custom options
const deleteConfirmation = useDeleteConfirmation({
  title: 'Custom Title',
  message: 'Custom message with specific details',
  preserveScroll: false,
  preserveState: false,
});
</script>
```

### Components

- **`DeleteConfirmationDialog.vue`** - Reusable dialog component
- **`useDeleteConfirmation.ts`** - Composable for managing delete confirmation logic
- **`DataTableActions.vue`** - Automatically handles table row deletions with confirmation

This system ensures consistent delete confirmation behavior across the entire application.

## Summary of Improvements

✅ **Reduced complexity**: 5 → 4 component layers  
✅ **Simplified types**: 6 → 3 core interfaces  
✅ **Added presets**: readonly, basic, admin configurations  
✅ **Enhanced ServerDataTable**: Comprehensive admin functionality  
✅ **Removed deprecated components**: Cleaner architecture  
✅ **Better documentation**: Clear examples and decision trees  
✅ **Performance optimizations**: Less prop drilling, fewer re-renders

The table system is now **much more approachable** while maintaining all existing functionality!