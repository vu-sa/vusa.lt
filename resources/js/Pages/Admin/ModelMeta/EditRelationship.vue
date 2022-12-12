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
    <NModal
      v-model:show="showModal"
      class="prose prose-sm dark:prose-invert"
      style="max-width: 600px"
      :title="`Sukurti naują ryšį`"
      :bordered="false"
      size="large"
      role="card"
      aria-modal="true"
      preset="card"
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
      </NForm></NModal
    >
    <NDataTable
      class="mt-4"
      :data="relationshipables"
      :columns="relationshipableColumns"
    ></NDataTable>
  </PageContent>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NDataTable,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NModal,
  NSelect,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import RelationshipForm from "@/Components/Admin/Forms/RelationshipForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

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
  Inertia.reload({
    data: {
      modelType: value,
    },
  });
};
</script>
