<template>
  <PageContent
    :title="relationship.name"
    :back-url="route('relationships.index')"
  >
    <UpsertModelLayout :errors="$attrs.errors" :model="relationship">
      <RelationshipForm
        :relationship="relationship"
        model-route="relationships.update"
      />
    </UpsertModelLayout>
    <NButton @click="showModal = true">Sukurti ryšį</NButton>
    <CardModal
      v-model:show="showModal"
      :title="`Sukurti naują ryšį`"
      @close="showModal = false"
      ><NForm label-placement="top">
        <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
          <NFormItemGi :span="6" label="Modelio tipas">
            <NSelect
              v-model:value="relationForm.model_type"
              remote
              :options="modelTypeOptions"
              placeholder="Pasirinkite modelio tipą"
              @update:value="handleUpdateModelType"
            ></NSelect>
          </NFormItemGi>
          <NFormItemGi :span="6" label="Modelis">
            <NSelect v-model:value="relationForm.model_id" :options="options">
            </NSelect>
          </NFormItemGi>
          <NFormItemGi :span="6" label="Susijęs modelis">
            <NSelect
              v-model:value="relationForm.related_model_id"
              :options="options"
            >
            </NSelect>
          </NFormItemGi>
        </NGrid>
        <NButton type="primary" @click="submitRelationForm">Sukurti</NButton>
      </NForm></CardModal
    >
    <NDataTable
      class="mt-4"
      size="small"
      :data="relationshipables"
      :columns="relationshipableColumns"
    ></NDataTable>
  </PageContent>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NDataTable,
  NForm,
  NFormItemGi,
  NGrid,
  NSelect,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import RelationshipForm from "@/Components/AdminForms/RelationshipForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineOptions({
  layout: AdminLayout,
});

const props = defineProps<{
  relationship: Record<string, any>;
  relatedModels: Record<string, any>[];
  relationshipables: Record<string, any>[];
}>();

const showModal = ref(false);

const relationTemplate = {
  model_type: null,
  model_id: null,
  related_model_id: null,
  relationship_id: props.relationship.id,
};

const relationForm = useForm("modelsRelation", relationTemplate);

const options = computed(() => {
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
    title: "Relationshipable Type",
    key: "relationshipable_type",
  },
  {
    title: "Relationshipable ID",
    key: "relationshipable_id",
  },
  {
    title: "Relationship ID",
    key: "related_model_id",
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

const modelTypeOptions = [
  {
    label: "Duty Institution",
    value: "App\\Models\\DutyInstitution",
  },
  {
    label: "Type",
    value: "App\\Models\\Type",
  },
];

const handleUpdateModelType = (value: string) => {
  relationForm.reset("model_id", "related_model_id");
  Inertia.reload({
    data: {
      modelType: value,
    },
  });
};

const handleDeleteRelationship = (id: number) => {
  Inertia.delete(route("relationships.deleteModelRelationship", id));
};
</script>
