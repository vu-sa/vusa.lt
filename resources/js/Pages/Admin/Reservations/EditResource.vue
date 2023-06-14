<template>
  <PageContent
    :title="title"
    :back-url="route('resources.index')"
    :heading-icon="Icons.RESOURCE"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="resource">
      <ResourceForm
        :resource="resource"
        :padaliniai="assignablePadaliniai"
        model-route="resources.update"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from "vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ResourceForm from "@/Components/AdminForms/ResourceForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

export type ResourceEditType = Omit<
  App.Entities.Resource,
  "created_at" | "updated_at" | "deleted_at" | "name" | "description"
> & {
  name: {
    lt: string;
    en: string;
  };
} & {
  description: {
    lt: string;
    en: string;
  };
};

const props = defineProps<{
  resource: ResourceEditType;
  assignablePadaliniai: Array<App.Entities.Padalinys>;
}>();

const title = computed(() => {
  if (props.resource.name.lt) {
    return props.resource.name.lt;
  } else if (props.resource.name.en) {
    return props.resource.name.en;
  } else {
    return "Išteklio redagavimas";
  }
});
</script>
