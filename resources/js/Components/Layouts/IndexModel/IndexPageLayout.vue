<template>
  <PageContent
    :title="title"
    :create-url="canUseRoutes.create ? route(`${modelName}.create`) : undefined"
  >
    <template #aside-header>
      <slot name="aside-header"></slot>
    </template>
    <NCard class="subtle-gray-gradient">
      <IndexDataTable
        :paginated-models="paginatedModels"
        :columns="columns"
        :model-name="modelName"
        :show-route="canUseRoutes.show ? `${modelName}.show` : undefined"
        :edit-route="canUseRoutes.edit ? `${modelName}.edit` : undefined"
        :destroy-route="
          canUseRoutes.destroy ? `${modelName}.destroy` : undefined
        "
      />
    </NCard>
  </PageContent>
</template>

<script setup lang="tsx">
import { type DataTableColumns, NCard } from "naive-ui";

import IndexDataTable from "@/Components/Layouts/IndexModel/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Layouts/IndexModel/IndexSearchInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

defineProps<{
  paginatedModels: PaginatedModels<Record<string, any>>;
  columns: DataTableColumns<any>;
  modelName: string;
  title: string;
  canUseRoutes: {
    create: boolean;
    show: boolean;
    edit: boolean;
    destroy: boolean;
  };
}>();
</script>
