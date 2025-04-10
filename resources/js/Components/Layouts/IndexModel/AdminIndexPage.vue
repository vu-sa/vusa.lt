<template>
  <IndexPageLayout :title="title" :model-name="modelName" :can-use-routes="canUseRoutes"
    :columns="tableState.columns" :paginated-models="paginatedModels" :icon="icon">
    <template v-if="$slots['create-button']" #create-button>
      <slot name="create-button" />
    </template>
    <template v-if="$slots['aside-header']" #aside-header>
      <slot name="aside-header" />
    </template>
    <slot />
    <template v-if="$slots['after-table']" #after-table>
      <slot name="after-table" />
    </template>
  </IndexPageLayout>
</template>

<script setup lang="ts">
import { type Component } from "vue";
import { capitalize } from "@/Utils/String";
import { transChoice as $tChoice } from "laravel-vue-i18n";
import { useAdminTable } from "@/Composables/useTableState";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import type { AdminIndexPageProps, AdminTableState } from "@/Types/admin-tables";

const props = defineProps<AdminIndexPageProps>();

// Default values for canUseRoutes
const canUseRoutes = props.canUseRoutes ?? {
  create: true,
  show: true,
  edit: true,
  duplicate: false,
  destroy: true
};

// Generate title if not provided
const title = props.title ?? 
  capitalize(props.entityNamePlural ?? 
    $tChoice(`entities.${props.entityName ?? props.modelName}.model`, 2));

// Create adminTable state
const tableState = useAdminTable({
  modelName: props.modelName,
  initialSorters: props.initialSorters ?? {},
  initialFilters: props.initialFilters ?? {},
  tableColumns: props.columnBuilder
}) as AdminTableState;

defineExpose({
  tableState
});
</script>