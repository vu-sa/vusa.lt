<template>
  <PageContent
    :title="title"
    :create-url="canUseRoutes.create ? route(`${modelName}.create`) : undefined"
  >
    <template #aside-header>
      <slot name="aside-header" />
    </template>
    <SuggestionAlert
      v-if="entity"
      :show-alert="showAlert"
      @alert-closed="showAlert = false"
    >
      <div class="prose-sm text-xs">
        <component :is="entity?.description"></component>
      </div>
    </SuggestionAlert>
    <slot />
    <NCard class="subtle-gray-gradient w-full min-w-[768px]">
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

import { useStorage } from "@vueuse/core";
import IndexDataTable from "@/Components/Layouts/IndexModel/IndexDataTable.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";
import entities from "@/Types/EntityDescriptions/entities";

const props = defineProps<{
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

// check for entity in the entity array by model name as key
const entity = entities.find((entity) => entity.key === props.modelName);

const showAlert = entity
  ? useStorage(`show-index-alert-${entity?.key}`, true)
  : false;
</script>
