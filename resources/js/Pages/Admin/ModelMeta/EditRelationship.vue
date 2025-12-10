<template>
  <PageContent :title="relationship.name" :back-url="route('relationships.index')">
    <UpsertModelLayout>
      <RelationshipForm :relationship
        @submit:form="(form) => form.patch(route('relationships.update', relationship.id), { preserveScroll: true })"
        @delete="() => router.delete(route('relationship.destroy', relationship.id))" />
    </UpsertModelLayout>
    <Button @click="showModal = true">
      Sukurti ryšį
    </Button>
    <CardModal v-model:show="showModal" :title="`Sukurti naują ryšį`" @close="showModal = false">
      <NForm label-placement="top">
        <div class="grid grid-cols-1 sm:grid-cols-4 lg:grid-cols-6 gap-6">
          <NFormItem label="Modelio tipas" class="col-span-6">
            <NSelect v-model:value="relationForm.model_type" remote :options="modelTypeOptions"
              placeholder="Pasirinkite modelio tipą" @update:value="handleUpdateModelType" />
          </NFormItem>
          <NFormItem label="Ryšio suteikėjas" class="col-span-6">
            <NSelect v-model:value="relationForm.model_id" filterable clearable :options="options" />
          </NFormItem>
          <NFormItem label="Ryšio gavėjas" class="col-span-6">
            <NSelect v-model:value="relationForm.related_model_id" filterable clearable :options="options" />
          </NFormItem>
          <NFormItem v-if="isTypeBasedRelationship" :label="$t('forms.fields.relationship_scope')" class="col-span-6">
            <NSelect v-model:value="relationForm.scope" :options="scopeOptions" />
          </NFormItem>
        </div>
        <Button @click="submitRelationForm">
          Sukurti
        </Button>
      </NForm>
    </CardModal>
    <NDataTable class="mt-4" size="small" :data="relationship.relationshipables" :columns="relationshipableColumns" />
  </PageContent>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import {
  NDataTable,
  NForm,
  NFormItem,
  NGrid,
  NSelect,
} from "naive-ui";
import { computed, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";
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

// Scope constants matching backend
const SCOPE_WITHIN_TENANT = 'within-tenant';
const SCOPE_CROSS_TENANT = 'cross-tenant';

const relationTemplate = {
  model_type: null as string | null,
  model_id: null,
  related_model_id: null,
  relationship_id: props.relationship.id,
  scope: SCOPE_WITHIN_TENANT,
};

const relationForm = useForm(relationTemplate);

// Check if the current model type is Type (for showing scope selector)
const isTypeBasedRelationship = computed(() => {
  return relationForm.model_type === 'App\\Models\\Type';
});

// Scope options for the dropdown
const scopeOptions = computed(() => [
  {
    label: $t('forms.options.scope_within_tenant'),
    value: SCOPE_WITHIN_TENANT,
  },
  {
    label: $t('forms.options.scope_cross_tenant'),
    value: SCOPE_CROSS_TENANT,
  },
]);

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
    title: $t('forms.fields.relationship_scope'),
    key: "scope",
    render(row) {
      if (row.relationshipable_type !== 'App\\Models\\Type') {
        return '-';
      }
      return row.scope === SCOPE_CROSS_TENANT
        ? $t('forms.options.scope_cross_tenant')
        : $t('forms.options.scope_within_tenant');
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
