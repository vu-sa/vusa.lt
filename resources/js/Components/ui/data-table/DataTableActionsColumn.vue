<template>
  <div class="flex justify-center">
    <DataTableActions
      :model="row"
      :model-name="modelName"
      :view-route="viewRoute ? route(viewRoute, row.id) : undefined"
      :edit-route="editRoute ? route(editRoute, row.id) : undefined"
      :duplicate-route="duplicateRoute ? route(duplicateRoute, row.id) : undefined"
      :delete-route="deleteRoute ? route(deleteRoute, row.id) : undefined"
      :restore-route="restoreRoute ? route(restoreRoute, row.id) : undefined"
      :can-view="canView"
      :can-edit="canEdit"
      :can-duplicate="canDuplicate"
      :can-delete="canDelete"
      :can-restore="canRestore"
      @action="handleAction"
      @custom-action="handleCustomAction"
    >
      <template #custom-actions="{ model, handleAction }">
        <slot name="custom-actions" :model="model" :handle-action="handleAction"></slot>
      </template>
    </DataTableActions>
  </div>
</template>

<script setup lang="ts" generic="TModel extends { id: string | number, deleted_at?: string | null }">
import DataTableActions from './DataTableActions.vue';

const props = defineProps<{
  row: TModel;
  modelName: string;
  
  // Route names (without parameters)
  viewRoute?: string;
  editRoute?: string;
  duplicateRoute?: string;
  deleteRoute?: string;
  restoreRoute?: string;
  
  // Permissions
  canView?: boolean;
  canEdit?: boolean;
  canDuplicate?: boolean;
  canDelete?: boolean;
  canRestore?: boolean;
}>();

const emit = defineEmits<{
  (e: 'action', action: string, model: TModel): void;
  (e: 'custom-action', action: string, model: TModel): void;
}>();

// Forward events from DataTableActions
const handleAction = (action: string, model: TModel) => {
  emit('action', action, model);
};

const handleCustomAction = (action: string, model: TModel) => {
  emit('custom-action', action, model);
};
</script>