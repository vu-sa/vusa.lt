<template>
  <PageContent :title :heading-icon="icon" :create-url="canUseRoutes.create ? route(`${modelName}.create`) : undefined">
    <template #create-button>
      <slot name="create-button" />
    </template>
    <template #aside-header>
      <slot name="aside-header" />
    </template>
    <SuggestionAlert v-model:show="showAlert" v-if="hasMarkdownContent" class="mb-2">
      <div class="text-sm">
        <MdSuspenseWrapper :directory="modelName" :locale="$page.props.app.locale" file="description" @content-loaded="onContentLoaded" />
      </div>
    </SuggestionAlert>
    <slot />
    <Card class="w-full min-w-[768px]">
      <CardHeader>
        <CardTitle class="mt-0">
          {{ title }}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <IndexDataTable v-bind="$attrs" :paginated-models :columns :model-name
          :show-route="canUseRoutes.show ? `${modelName}.show` : undefined"
          :edit-route="canUseRoutes.edit ? `${modelName}.edit` : undefined" :destroy-route="canUseRoutes.destroy ? `${modelName}.destroy` : undefined
            " :duplicate-route="canUseRoutes.duplicate ? `${modelName}.duplicate` : undefined" />
      </CardContent>
    </Card>
    <slot name="after-table" />
  </PageContent>
</template>

<script setup lang="tsx">
import { type DataTableColumns } from "naive-ui";
import { useStorage } from "@vueuse/core";
import type { Component } from "vue";
import { ref } from "vue";

import IndexDataTable from "@/Components/Layouts/IndexModel/IndexDataTable.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import entities from "@/entities";
import MdSuspenseWrapper from "@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";

const props = defineProps<{
  paginatedModels: PaginatedModels<Record<string, any>>;
  columns: DataTableColumns<any>;
  modelName: string;
  title: string;
  canUseRoutes: {
    create: boolean;
    show: boolean;
    edit: boolean;
    duplicate: boolean;
    destroy: boolean;
  };
  icon?: Component;
}>();

// check for entity in the entity array by model name as key
const entity = entities.find((entity) => entity.key === props.modelName);

const showAlert = entity
  ? useStorage(`show-index-alert-${entity?.key}`, true)
  : false;

// Whether to display markdown content section
const hasMarkdownContent = ref(true);

/**
 * Handle content loaded event from MdSuspenseWrapper
 */
const onContentLoaded = (loaded: boolean) => {
  hasMarkdownContent.value = loaded;
};
</script>
