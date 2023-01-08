<template>
  <NForm :model="form" label-placement="top">
    <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
      <NFormItemGi required label="Pavadinimas" :span="2">
        <NInput
          v-model:value="form.name"
          type="text"
          placeholder="Trumpas ryšio pavadinimas.."
        />
      </NFormItemGi>

      <NFormItemGi label="Techninė žymė" :span="2">
        <NInput
          v-model:value="form.slug"
          type="text"
          placeholder="pvz.: simple-advisory"
        />
      </NFormItemGi>

      <NFormItemGi label="Aprašymas" :span="6">
        <NInput
          v-model:value="form.description"
          type="textarea"
          placeholder="Trumpas apibūdinimas..."
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
  relationship: App.Entities.Relationship;
  // contentTypes: Record<string, any>[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("relationship", props.relationship);
</script>
