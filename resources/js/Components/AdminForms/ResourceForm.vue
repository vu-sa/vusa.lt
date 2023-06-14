<template>
  <NForm :model="form" label-placement="top" :disabled="formDisabled">
    <div class="flex flex-col">
      <FormElement>
        <template #title>Rezervuojamas išteklius</template>
        <template #description> Išteklio aprašymas</template>
        <NFormItem label="Pavadinimas" required>
          <MultiLocaleInput
            v-model:input="form.name"
            v-model:lang="inputLang"
          />
        </NFormItem>
        <NFormItem label="Aprašymas" required>
          <MultiLocaleInput
            v-model:input="form.description"
            v-model:lang="inputLang"
            input-type="textarea"
          />
        </NFormItem>
        <NFormItem label="Padalinys, kuriam priklauso daiktas" :span="2">
          <NSelect
            v-model:value="form.padalinys_id"
            :options="padaliniai"
            label-field="shortname"
            value-field="id"
            placeholder="VU SA X"
            clearable
          />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Papildoma informacija</template>
        <NFormItem label="Vieta" required>
          <NInput v-model:value="form.location" />
        </NFormItem>
        <NFormItem label="Vnt. skaičius" required>
          <NInputNumber v-model:value="form.capacity" :min="1" type="number" />
        </NFormItem>
        <NFormItem label="Ar šiuo metu rezervuojamas?" required>
          <NSwitch v-model:value="form.is_reservable" />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <!-- <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      /> -->
      <!-- <UpsertModelButton :form="form" :model-route="modelRoute" /> -->
      <NButton @click="submit">Pateikti</NButton>
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  NButton,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NSelect,
  NSwitch,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm } from "laravel-precognition-vue-inertia";
import { usePage } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import MultiLocaleInput from "../SimpleAugment/MultiLocaleInput.vue";
// import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";
import type { ResourceCreationTemplate } from "@/Pages/Admin/Reservations/CreateResource.vue";

const props = defineProps<{
  resource: ResourceCreationTemplate;
  padaliniai: App.Entities.Padalinys[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("post", route(props.modelRoute), props.resource);

// padalinys_id is set to 0 if it's not found. Shouldn't happen for authenticated users.
const formDisabled = computed(() => {
  return form.padalinys_id === 0;
});

const submit = () => {
  form.submit({
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
  });
};

const inputLang = ref(usePage().props.app.locale);
</script>
