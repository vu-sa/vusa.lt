<template>
  <NForm :model="form" label-placement="top">
    <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
      <NFormItemGi required label="Pavadinimas" :span="2">
        <NInput
          v-model:value="form.title"
          type="text"
          placeholder="Turinio tipas"
        />
      </NFormItemGi>

      <NFormItemGi label="Techninė žymė" :span="2">
        <NInput
          v-model:value="form.slug"
          :disabled="modelRoute === 'types.update'"
          type="text"
          placeholder="pvz.: turinio-tipas"
        />
      </NFormItemGi>

      <NFormItemGi label="Aprašymas" :span="6">
        <NInput
          v-model:value="form.description"
          type="textarea"
          placeholder="Ilgas aprašymas..."
        />
      </NFormItemGi>
      <NFormItemGi required label="Modelio tipas" :span="2">
        <NSelect
          v-model:value="form.model_type"
          :options="modelTypes"
          placeholder="Institucija"
          @update:value="form.parent_id = null"
        />
      </NFormItemGi>
      <NFormItemGi label="Tėvinis tipas" :span="2">
        <NSelect
          v-model:value="form.parent_id"
          label-field="title"
          value-field="id"
          :options="parentTypeOptions"
          placeholder="Studentų atstovybė"
        />
      </NFormItemGi>
    </NGrid>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      />
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="ts">
import { NForm, NFormItemGi, NGrid, NInput, NSelect } from "naive-ui";
import { computed } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  type: App.Models.MainPage;
  contentTypes: Record<string, any>[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("type", props.type);

const modelTypes = [
  {
    value: "App\\Models\\DutyInstitution",
    label: "Institucija",
  },
  {
    value: "App\\Models\\Duty",
    label: "Pareigybė",
  },
  {
    value: "App\\Models\\Doing",
    label: "Veikla",
  },
];

const parentTypeOptions = computed(() => {
  return props.contentTypes.filter(
    (type) => form.model_type === type.model_type && form.id !== type.id
  );
});
</script>
