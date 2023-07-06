<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>Rezervacija</template>
        <template #description>
          <p>
            Rezervacijos aprašymas. Daiktų rezervacijos laikas yra nuo jų
            <strong>atsiėmimo laiko</strong> iki
            <strong>grąžinimo laiko</strong>.
          </p>
          <p class="mt-2">
            Skolinimosi laikas galioja visiems rezervuojamiems daiktams. Norint
            individualiai pakeisti skolintino daikto laiką, pridėk daiktą jau
            sukūrus rezervaciją.
          </p>
        </template>
        <NFormItem label="Pavadinimas" required>
          <MultiLocaleInput
            v-model:input="form.name"
            v-model:lang="inputLang"
            placeholder="Renginys „VU SA dienos“"
          />
        </NFormItem>
        <NFormItem label="Aprašymas" required>
          <MultiLocaleInput
            v-model:input="form.description"
            v-model:lang="inputLang"
            placeholder="Visi ištekliai bus naudojami šiam renginiui. Jeigu reikia, galima būtų išteklių A grąžinti anksčiau..."
            input-type="textarea"
          />
        </NFormItem>
        <NFormItem required label="Skolinimosi laikotarpis">
          <NDatePicker
            v-model:value="date"
            :loading="resourceLoading"
            type="datetimerange"
            :first-day-of-week="0"
            format="yyyy-MM-dd HH:mm"
            default-time="13:00:00"
            :time-picker-props="{
              format: 'HH:mm',
              minutes: 15,
            }"
            @update:value="onDateChange"
          />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Rezervuojami ištekliai</template>
        <template #description
          >Pakeitus rezervacijos laiką, pasirinkti ištekliai bus išvalyti.
          Rodomas išteklių kiekis nurodytu rezervacijos laikotarpiu.
        </template>
        <NFormItem>
          <template #label>
            <span class="mb-2 inline-flex items-center gap-1"
              ><NIcon :component="Icons.RESOURCE" />
              {{ $t("Pasirinkti ištekliai") }}
            </span>
          </template>
          <NDynamicInput v-model:value="form.resources" :on-create="onCreate">
            <template #default="{ value }">
              <div class="flex w-full gap-2">
                <NSelect
                  v-model:value="value.id"
                  filterable
                  :options="allResourceOptions"
                  placeholder="Pasirinkite išteklių..."
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
  NButton,
  NDatePicker,
  NDynamicInput,
  NForm,
  NFormItem,
  NIcon,
  NInputNumber,
  NSelect,
} from "naive-ui";
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useForm } from "laravel-precognition-vue-inertia";

import FormElement from "./FormElement.vue";
import Icons from "@/Types/Icons/regular";
import MultiLocaleInput from "../SimpleAugment/MultiLocaleInput.vue";
import type { ReservationCreationTemplate } from "@/Pages/Admin/Reservations/CreateReservation.vue";
import type { ReservationEditType } from "@/Pages/Admin/Reservations/EditReservation.vue";
// import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

defineEmits<{
  (event: "update:value", value: number | null): void;
}>();

const props = defineProps<{
  reservation: ReservationCreationTemplate | ReservationEditType;
  allResources: App.Entities.Resource[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const inputLang = ref(usePage().props.app.locale);
const resourceLoading = ref(false);

const routeToSubmit = computed(() => {
  return props.reservation?.id
    ? route(props.modelRoute, props.reservation.id)
    : route(props.modelRoute);
});

const form = useForm(
  props.reservation?.id ? "patch" : "post",
  routeToSubmit.value,
  props.reservation
);

const date = ref<[number, number]>([form.start_time, form.end_time]);

watch(date, (newVal) => {
  form.start_time = newVal[0];
  form.end_time = newVal[1];
});

const onCreate = () => {
  return {
    id: undefined,
    quantity: 1,
  };
};

const onDateChange = (value: [number, number]) => {
  form.resources = [];
  router.reload({
    data: {
      dateTimeRange: { start: value[0], end: value[1] },
    },
    preserveScroll: true,
    only: ["resources"],
    onSuccess: () => {
      resourceLoading.value = false;
    },
  });
};

const getleftCapacity = (id: string) => {
  return props.allResources.find((resource) => resource.id === id)
    ?.lowestCapacityAtDateTimeRange;
};

const allResourceOptions = computed(() => {
  let selectedResources = form.resources.map((resource) => resource.id);

  let allResources = props.allResources.map((resource) => ({
    label: `${resource.name} (likutis: ${resource.lowestCapacityAtDateTimeRange})`,
    value: resource.id,
    disabled:
      resource.lowestCapacityAtDateTimeRange === 0 ||
      selectedResources.includes(resource.id) ||
      !resource.is_reservable,
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
