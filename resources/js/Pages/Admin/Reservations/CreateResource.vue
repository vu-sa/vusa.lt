<template>
  <PageContent :title="$tChoice('forms.new_model', 1, {
    model: $tChoice('entities.resource.model', 1),
  })
    " :heading-icon="Icons.RESOURCE">
    <UpsertModelLayout>
      <ResourceForm :assignable-tenants :resource :categories
        @submit:form="(form) => form.post(route('resources.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { usePage } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ResourceForm from "@/Components/AdminForms/ResourceForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

export type ResourceCreationTemplate = Omit<
  App.Entities.Resource,
  "created_at" | "updated_at" | "deleted_at" | "id" | "name" | "description"
> & {
  id: undefined;
  name: Record<"lt" | "en", string>;
  description: Record<"lt" | "en", string>;
  media: Record<string, never>[];
  // media: models.Media[] | [];
};

defineProps<{
  assignableTenants: Array<App.Entities.Tenant>;
  categories: any
}>();

const resource: ResourceCreationTemplate = {
  id: undefined,
  name: {
    lt: "",
    en: "",
  },
  description: {
    lt: "",
    en: "",
  },
  location: "",
  capacity: 1,
  resource_category_id: null,
  // If tenant_id is zero, then the form will be disabled (set in form).
  tenant_id: usePage().props.auth?.user.tenants[0]?.id ?? 0,
  is_reservable: 1,
  media: [],
};
</script>
