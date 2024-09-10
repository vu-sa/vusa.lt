<template>
  <PageContent :title="resource.name[$page.props.app.locale]" :back-url="route('resources.index')"
    :heading-icon="Icons.RESOURCE">
    <NCard :title="$t('Rezervacijų istorija')" class="mb-4 min-w-[450px]">
      <ResourceReservationsTable :reservations="resource.reservations" />
    </NCard>
    <UpsertModelLayout :errors="$page.props.errors" :model="resource">
      <ResourceForm :resource :categories :assignable-tenants model-route="resources.update" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { NCard } from "naive-ui";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ResourceForm from "@/Components/AdminForms/ResourceForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import ResourceReservationsTable from "@/Components/Tables/ResourceReservationsTable.vue";

export type ResourceEditType = Omit<
  App.Entities.Resource,
  "created_at" | "updated_at" | "deleted_at" | "name" | "description"
> & {
  name: Record<"lt" | "en", string>;
  description: Record<"lt" | "en", string>;
  media: Record<string, never>[];
  // media: models.Media[];
};

defineProps<{
  resource: ResourceEditType;
  categories: any
  assignableTenants: Array<App.Entities.Tenant>;
}>();
</script>
