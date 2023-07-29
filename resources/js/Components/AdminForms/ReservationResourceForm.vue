<template>
  <NForm :model="reservationResourceForm">
    <NFormItem required :label="capitalize($t('entities.reservation.period'))">
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
    <NFormItem :label="$t('forms.fields.title')" path="resource_id">
      <NSelect
        v-model:value="reservationResourceForm.resource_id"
        :placeholder="`${$t('Pasirinkite')}...`"
        clearable
        value-field="id"
        label-field="name"
        :options="allResourceOptions"
        :render-label="handleRenderResourceLabel"
        :render-tag="handleRenderResourceTag"
        remote
        @update:value="reservationResourceForm.quantity = 1"
      >
      </NSelect>
    </NFormItem>
    <NFormItem :label="$t('forms.fields.quantity')" path="quantity">
      <NInputNumber
        v-model:value="reservationResourceForm.quantity"
        :disabled="reservationResourceForm.resource_id === null"
        min="1"
        :max="capacityMax"
        placeholder=""
      />
    </NFormItem>
    <NFormItem :show-label="false">
      <NButton
        :disabled="reservationResourceForm.resource_id === null"
        type="primary"
        @click="handleSubmit"
        >{{ $t("forms.submit") }}</NButton
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
  type SelectOption,
} from "naive-ui";
import { capitalize } from "@/Utils/String";
import { computed, ref, watch } from "vue";
import {
  renderResourceLabel,
  renderResourceTag,
} from "@/Features/Admin/Reservations/Helpers";

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
    ...resource,
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

const getleftCapacity = (id: string) => {
  return props.allResources?.find((resource) => resource.id === id)
    ?.lowestCapacityAtDateTimeRange;
};

const handleRenderResourceLabel = (option: SelectOption, selected: boolean) => {
  return renderResourceLabel(option, selected, getleftCapacity(option.id));
};

const handleRenderResourceTag = ({ option }: { option: SelectOption }) => {
  return renderResourceTag(option, props.allResources);
};

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
