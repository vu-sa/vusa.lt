<template>
  <div class="flex justify-start">
    <DataTableActions
      :model="row"
      :model-name
      :view-route="viewRoute ? safeRoute(viewRoute, row.id) : undefined"
      :edit-route="editRoute ? safeRoute(editRoute, row.id) : undefined"
      :duplicate-route="duplicateRoute ? safeRoute(duplicateRoute, row.id) : undefined"
      :delete-route="deleteRoute ? safeRoute(deleteRoute, row.id) : undefined"
      :restore-route="restoreRoute ? safeRoute(restoreRoute, row.id) : undefined"
      :force-delete-route="canForceDelete && forceDeleteRoute ? safeRoute(forceDeleteRoute, row.id) : undefined"
      :can-view
      :can-edit
      :can-duplicate
      :can-delete
      :can-restore
      :can-force-delete
      :confirm-delete
      :delete-confirm-message
      :delete-confirm-title
      @action="handleAction"
      @custom-action="handleCustomAction"
    >
      <template #custom-actions="{ model, handleAction }">
        <slot name="custom-actions" :model :handle-action />
      </template>
    </DataTableActions>
  </div>
</template>

<script setup lang="ts" generic="TModel extends { id: string | number, deleted_at?: string | null, force_delete_blocked_reason?: string | null }">
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
  forceDeleteRoute?: string;

  // Permissions
  canView?: boolean;
  canEdit?: boolean;
  canDuplicate?: boolean;
  canDelete?: boolean;
  canRestore?: boolean;
  canForceDelete?: boolean;

  // Confirmation settings
  confirmDelete?: boolean;
  deleteConfirmMessage?: string;
  deleteConfirmTitle?: string;
}>();

const emit = defineEmits<{
  (e: 'action', action: string, model: TModel): void;
  (e: 'custom-action', action: string, model: TModel): void;
}>();

/**
 * Route names are derived by convention from the model name, so a page can enable
 * an action for a model that never registered the matching route. Resolve to
 * undefined instead of throwing and taking the whole table down with it.
 */
const safeRoute = (routeName: string, id: string | number): string | undefined => {
  try {
    return route(routeName, id);
  }
  catch {
    return undefined;
  }
};

// Forward events from DataTableActions
const handleAction = (action: string, model: TModel) => {
  emit('action', action, model);
};

const handleCustomAction = (action: string, model: TModel) => {
  emit('custom-action', action, model);
};
</script>
