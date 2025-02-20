<template>
  <PageContent :title="relationship.name" :back-url="route('relationships.index')">
    <UpsertModelLayout>
      <RelationshipForm :relationship
        @submit:form="(form) => form.patch(route('relationships.update', relationship.id), { preserveScroll: true })"
        @delete="() => router.delete(route('relationship.destroy', relationship.id))" />
    </UpsertModelLayout>
    <NButton @click="showModal = true">
      Sukurti ryšį
    </NButton>
    <CardModal v-model:show="showModal" :title="`Sukurti naują ryšį`" @close="showModal = false">
      <NForm label-placement="top">
        <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
          <NFormItemGi :span="6" label="Modelio tipas">
            <NSelect v-model:value="relationForm.model_type" remote :options="modelTypeOptions"
              placeholder="Pasirinkite modelio tipą" @update:value="handleUpdateModelType" />
          </NFormItemGi>
          <NFormItemGi :span="6" label="Ryšio suteikėjas">
            <NSelect v-model:value="relationForm.model_id" filterable clearable :options="options" />
          </NFormItemGi>
          <NFormItemGi :span="6" label="Ryšio gavėjas">
            <NSelect v-model:value="relationForm.related_model_id" filterable clearable :options="options" />
          </NFormItemGi>
          <!-- TODO: paaiškinti, kas yra ryšio suteikėjas ir ryšio gavėjas. -->
        </NGrid>
        <NButton type="primary" @click="submitRelationForm">
          Sukurti
        </NButton>
      </NForm>
    </CardModal>
    <NDataTable class="mt-4" size="small" :data="relationship.relationshipables" :columns="relationshipableColumns" />
  </PageContent>
</template>

<script setup lang="tsx">
import {
  NButton,
  NDataTable,
  NForm,
  NFormItemGi,
  NGrid,
  NSelect,
} from "naive-ui";
import { computed, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";

import { modelTypes } from "@/Types/formOptions";
import CardModal from "@/Components/Modals/CardModal.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import RelationshipForm from "@/Components/AdminForms/RelationshipForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  relationship: App.Entities.Relationship;
  relatedModels?: Record<string, any>[];
}>();

const showModal = ref(false);

const relationTemplate = {
  model_type: null,
  model_id: null,
  related_model_id: null,
  relationship_id: props.relationship.id,
};

const relationForm = useForm(relationTemplate);

const options = computed(() => {
  if (!props.relatedModels) {
    return [];
  }

  return props.relatedModels.map((model) => {
    return {
      label: model.name ?? model.title,
      value: model.id,
    };
  });
});

const submitRelationForm = () => {
  relationForm.post(
    route("relationships.storeModelRelationship", {
      relationship: props.relationship.id,
    }),
    {
      onSuccess: () => {
        showModal.value = false;
        // reset form
        relationForm.reset();
      },
    }
  );
};

const relationshipableColumns = [
  {
    title: "Modelio tipas",
    key: "relationshipable_type",
  },
  {
    title: "Ryšio suteikėjas",
    key: "relationshipable",
    render(row) {
      return row.relationshipable.name ?? row.relationshipable.title;
    },
  },
  {
    title: "Ryšio gavėjas",
    key: "related_model",
    render(row) {
      return row.related_model.name ?? row.related_model.title;
    },
  },
  {
    key: "more",
    render(row) {
      return (
        <MoreOptionsButton
          small
          delete
          onDeleteClick={() => handleDeleteRelationship(row.id)}
        />
      );
    },
  },
];

const modelTypeOptions = modelTypes.relationshipable.map((relationshipable) => {
  return {
    label: relationshipable,
    value: "App\\Models\\" + relationshipable,
  };
});

const handleUpdateModelType = (value: string) => {
  relationForm.reset("model_id", "related_model_id");
  router.reload({
    data: {
      modelType: value,
    },
    only: ["relatedModels"],
  });
};

const handleDeleteRelationship = (id: number) => {
  router.delete(route("relationships.deleteModelRelationship", id));
};
</script>
