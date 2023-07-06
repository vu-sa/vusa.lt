<template>
  <NForm :model="reservationResourceForm">
    <NFormItem required label="Skolinimosi laikotarpis">
      <NDatePicker
        v-model:value="date"
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
    <NFormItem label="Išteklio pavadinimas" path="resource_id">
      <NSelect
        v-model:value="reservationResourceForm.resource_id"
        placeholder="Pasirinkite išteklio pavadinimą"
        clearable
        :options="allResourceOptions"
      >
      </NSelect>
    </NFormItem>
    <NFormItem label="Kiekis" path="quantity">
      <NInputNumber
        v-model:value="reservationResourceForm.quantity"
        :disabled="reservationResourceForm.resource_id === null"
        min="1"
        :max="capacityMax"
        placeholder="Įveskite išteklio kiekį"
      ></NInputNumber>
    </NFormItem>
    <NFormItem :show-label="false">
      <NButton
        :disabled="reservationResourceForm.resource_id === null"
        type="primary"
        @click="handleSubmit"
        >Pridėti</NButton
      >
    </NFormItem>
  </NForm>
</template>

<script setup lang="tsx">
import { type InertiaForm, router, useForm } from "@inertiajs/vue3";
import {
  NButton,
  NDatePicker,
  NForm,
  NFormItem,
  NInputNumber,
  NSelect,
} from "naive-ui";
import { computed, ref, watch } from "vue";

const props = defineProps<{
  reservationResourceForm: InertiaForm<{
    resource_id: number | null;
    reservation_id: number;
    quantity: number;
    start_time: string;
    end_time: string;
  }>;
  allResources?: App.Entities.Resource[];
}>();

const emit = defineEmits(["success"]);

const reservationResourceForm = useForm(props.reservationResourceForm);

const date = ref<string[]>([
  props.reservationResourceForm.start_time,
  props.reservationResourceForm.end_time,
]);

watch(date, (value) => {
  reservationResourceForm.start_time = value[0];
  reservationResourceForm.end_time = value[1];
});

const getAllResources = async (value: string[]) => {
  router.reload({
    data: {
      dateTimeRange: {
        start: value[0],
        end: value[1],
      },
    },
    only: ["allResources"],
  });
};

await getAllResources([
  props.reservationResourceForm.start_time,
  props.reservationResourceForm.end_time,
]);

const onDateChange = (value: string[]) => {
  getAllResources(value);
  reservationResourceForm.resource_id = null;
  reservationResourceForm.quantity = 1;
};

const allResourceOptions = computed(() => {
  return props.allResources?.map((resource) => ({
    label: `${resource.name} (likutis: ${resource.lowestCapacityAtDateTimeRange})`,
    value: resource.id,
    disabled:
      resource.lowestCapacityAtDateTimeRange === 0 || !resource.is_reservable,
  }));
});

const capacityMax = computed(() => {
  return (
    props.allResources?.find(
      (resource) => resource.id === reservationResourceForm.resource_id
    )?.lowestCapacityAtDateTimeRange ?? 1
  );
});

const handleSubmit = () => {
  reservationResourceForm.post(route("reservationResources.store"), {
    preserveScroll: true,
    onSuccess: () => {
      reservationResourceForm.reset();
      emit("success");
    },
  });
};
</script>
