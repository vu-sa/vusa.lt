# TanStack Data Tables - AI Guidance

Quick reference for AI assistants working with TanStack tables in vusa.lt.

**For comprehensive documentation**: See [README.md](README.md) in this directory.

## Quick Decision Tree

```
Full admin page with header/actions/breadcrumbs? → IndexTablePage.vue
Server-side table without page wrapper? → ServerDataTable.vue
Simple client-side table? → SimpleDataTable.vue
```

## Table System Architecture

**Two systems exist**:
1. **Legacy (Naive UI)**: `IndexModel/IndexDataTable.vue` - DON'T modify
2. **TanStack (Current)**: Use for all new features

**Component hierarchy** (4 layers):
```
IndexTablePage.vue          # Full page with header/breadcrumbs
└── ServerDataTable.vue     # Server-side data + admin features
    └── DataTableProvider.vue   # State coordination
        └── DataTable.vue       # Core TanStack table
```

## Component Selection Guide

### IndexTablePage.vue
**Use when**: Need full admin page layout
- Includes: Header, breadcrumbs, filters, actions, create button
- Handles: Page navigation, permission-based UI

### ServerDataTable.vue
**Use when**: Need table without page wrapper
- Includes: Empty state, create button, show deleted toggle
- Handles: Server-side pagination, sorting, filtering
- **Enhanced**: Comprehensive admin functionality built-in

### SimpleDataTable.vue
**Use when**: Client-side data only (< 100 items)
- No server communication
- Good for: Modals, embedded tables, reports

## Project-Specific Patterns

### Model Names
Always provide `model-name` prop for:
- Translation lookups (`models.*.name`)
- Permission checks
- Route generation

### Permission Integration
Tables automatically respect permissions:
- Show/hide create button based on `can-create` prop
- Filter data based on tenant permissions
- Row actions respect model policies

### Backend Integration
Controllers use `HasTanstackTables` trait:
```php
$query = $this->applyTanstackFilters(
    $query,
    $request,
    $this->tableService,
    ['name', 'email'], // searchable columns
    ['tenantRelation' => 'tenant', 'permission' => 'model.read.padalinys']
);
```

## Delete Confirmation System

### Built-in (Tables)
Table actions automatically handle delete confirmation:
```vue
createStandardActionsColumn('institutions', {
  canDelete: true,
  confirmDelete: true, // default
  deleteConfirmTitle: 'Delete?', // optional
  deleteConfirmMessage: 'Custom message' // optional
})
```

### Manual (Forms/Custom)
Use `useDeleteConfirmation` composable:
```vue
import { useDeleteConfirmation } from '@/Composables/useDeleteConfirmation';

const deleteConfirmation = useDeleteConfirmation({
  title: 'Delete?',
  message: 'This will permanently delete the item.'
});

// Then call:
deleteConfirmation.deleteWithInertia(route('model.destroy', id));
```

## Common Issues & Gotchas

### Issue: Table not updating after data change
**Solution**: Add `:key` to force re-render
```vue
<ServerDataTable :key="refreshKey" ... />
```

### Issue: Filters not working
**Solution**: Request class must implement `getFilters()` and `getSorting()`
```php
public function getFilters(): array {
    return json_decode($this->input('filters', '{}'), true) ?: [];
}
```

### Issue: Events not firing
**Solution**: Use kebab-case for event names
```vue
@filter-changed="handler"  <!-- ✅ Correct -->
@filterChanged="handler"   <!-- ❌ Wrong -->
```

### Issue: Checkbox binding errors
**Solution**: Use `modelValue`, not `checked`
```vue
<Checkbox v-model="isChecked" />  <!-- ✅ Correct -->
<Checkbox v-model:checked="isChecked" />  <!-- ❌ Wrong -->
```

## TypeScript Integration

### Column Definitions
```typescript
const columns: ColumnDef<ModelType, any>[] = [
  {
    accessorKey: "name",
    header: () => $t("forms.fields.name"),
    cell: ({ row }) => (
      <Link href={route("model.edit", row.original.id)}>
        {row.getValue("name")}
      </Link>
    ),
    enableSorting: true
  }
];
```

### Configuration Presets
```typescript
import { createTableConfig, TablePresets } from '@/Types/TableConfigTypes';

const config = createTableConfig('admin', {
  modelName: 'institutions',
  data: institutions.data,
  columns,
  totalCount: institutions.total
});
```

## Performance Guidelines

1. **Use server-side tables** for > 100 items
2. **Set appropriate page sizes**: 10-15 for complex rows, 25-50 for simple
3. **Implement eager loading** in backend queries
4. **Debounce search** (handled automatically by ServerDataTable)

## Quick Reference

**Migration**: Don't migrate existing Naive UI tables unless required
**Backend**: No changes needed - existing services work perfectly
**Debug**: Set `APP_DEBUG=true` to see table state in console
**Help**: Check existing implementations in `resources/js/Pages/Admin/`

---

**See [README.md](README.md) for**:
- Complete component examples
- Advanced features (row selection, custom filters)
- Migration guide
- Troubleshooting details
- Full API reference
