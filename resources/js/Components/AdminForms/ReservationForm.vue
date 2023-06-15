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
        <NFormItem label="Pasirinkti daiktai">
          <NDynamicInput v-model:value="form.resources" :on-create="onCreate">
            <template #default="{ value }">
              <div class="flex w-full gap-2">
                <NSelect
                  v-model:value="value.id"
                  filterable
                  :options="allResourceOptions"
                />
                <NInputNumber
                  v-model:value="value.quantity"
                  :min="1"
                  :max="getleftCapacity(value.id)"
                  :default-value="1"
                ></NInputNumber>
              </div>
            </template>
          </NDynamicInput>
        </NFormItem>
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

<script setup lang="tsx">
import {
  type DataTableColumns,
  NButton,
  NDataTable,
  NDatePicker,
  NDynamicInput,
  NForm,
  NFormItem,
  NInputNumber,
  NSelect,
} from "naive-ui";
import { computed, ref, watch } from "vue";
import { useForm } from "laravel-precognition-vue-inertia";
import { usePage } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import MultiLocaleInput from "../SimpleAugment/MultiLocaleInput.vue";
import type { ReservationCreationTemplate } from "@/Pages/Admin/Reservations/CreateReservation.vue";
// import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const emit = defineEmits<{
  (event: "update:value", value: number | null): void;
}>();

const props = defineProps<{
  reservation: ReservationCreationTemplate | App.Entities.Resource;
  allResources: App.Entities.Resource[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const inputLang = ref(usePage().props.app.locale);

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

const onCreate = (value: number) => {
  return {
    id: undefined,
    quantity: 1,
  };
};

const getleftCapacity = (id: string) => {
  return props.allResources.find((resource) => resource.id === id)
    ?.leftCapacity;
};

const form = useForm(
  props.reservation?.id ? "patch" : "post",
  routeToSubmit.value,
  props.reservation
);

const allResourceOptions = computed(() => {
  let selectedResources = form.resources.map((resource) => resource.id);

  let allResources = props.allResources.map((resource) => ({
    label: `${resource.name} (likutis: ${resource.capacity})`,
    value: resource.id,
    disabled:
      resource.leftCapacity === 0 || selectedResources.includes(resource.id),
  }));

  // filter by selected resources in form

  return allResources.filter((resource) => {
    return !selectedResources.includes(resource.label);
  });
});

const submit = () => {
  form.submit({
    preserveScroll: true,
  });
};
</script>
