<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>Rezervacija</template>
        <template #description> Rezervacijos aprašymas</template>
        <NFormItem label="Pavadinimas" required>
          <MultiLocaleInput
            v-model:input="form.name"
            v-model:lang="inputLang"
          />
        </NFormItem>
        <NFormItem required label="Skolinimosi laikotarpis">
          <NDatePicker v-model:value="date" type="daterange" />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Rezervuojami daiktai</template>
        <template #description> Daiktus galima rasti čia.</template>
      </FormElement>
      <FormElement>
        <template #title>Papildoma informacija</template>
        <NFormItem label="Aprašymas" required>
          <MultiLocaleInput
            v-model:input="form.description"
            v-model:lang="inputLang"
            input-type="textarea"
          />
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
  NDatePicker,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NSelect,
  NSwitch,
} from "naive-ui";
import { computed, ref, watch } from "vue";
import { useForm } from "laravel-precognition-vue-inertia";
import { usePage } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import MultiLocaleInput from "../SimpleAugment/MultiLocaleInput.vue";
import type { ReservationCreationTemplate } from "@/Pages/Admin/Reservations/CreateReservation.vue";
// import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  reservation: ReservationCreationTemplate | App.Entities.Resource;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const routeToSubmit = computed(() => {
  return props.reservation?.id
    ? route(props.modelRoute, props.reservation.id)
    : route(props.modelRoute);
});

const date = ref<[number, number]>([
  new Date().getTime(),
  new Date().getTime() + 3600 * 1000 * 24,
]);

watch(date, (newVal) => {
  form.start_date = newVal[0];
  form.end_date = newVal[1];
});

const form = useForm(
  props.reservation?.id ? "patch" : "post",
  routeToSubmit.value,
  props.reservation
);

const submit = () => {
  form.submit({
    preserveScroll: true,
  });
};

const inputLang = ref(usePage().props.app.locale);
</script>
