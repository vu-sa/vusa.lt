<template>
  <NSpin :spinning description="Kraunami visi resursai...">
    <NForm :model="reservationResourceForm">
      <NFormItem required :label="capitalize($t('entities.reservation.period'))">
        <!-- For now, don't allow to change the date when updating, because of recalculation of possible amount difficulties -->
        <NDatePicker v-model:value="date" type="datetimerange" :first-day-of-week="0" format="yyyy-MM-dd HH:mm"
          default-time="13:00:00" :time-picker-props="{
            format: 'HH:mm',
            minutes: 15,
          }" @update:value="onDateChange" />
      </NFormItem>
      <NFormItem :label="$t('forms.fields.title')" path="resource_id">
        <NSelect v-model:value="reservationResourceForm.resource_id" :placeholder="`${$t('Pasirinkite')}...`" clearable
          value-field="id" label-field="name" :options="allResourceOptions" :render-label="handleRenderResourceLabel"
          :render-tag="handleRenderResourceTag" remote @update:value="reservationResourceForm.quantity = 1" />
      </NFormItem>
      <NFormItem :label="$t('forms.fields.quantity')" path="quantity">
        <NInputNumber v-model:value="reservationResourceForm.quantity"
          :disabled="reservationResourceForm.resource_id === null" min="1" :max="capacityMax" placeholder="" />
      </NFormItem>
      <NFormItem :show-label="false">
        <NButton :disabled="reservationResourceForm.resource_id === null" type="primary" @click="handleSubmit">
          {{ $t("forms.submit") }}
        </NButton>
      </NFormItem>
    </NForm>
  </NSpin>
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
    id: number | undefined;
    resource_id: number | null;
    reservation_id: number;
    quantity: number;
    start_time: string;
    end_time: string;
  }>;
  reservationResourceFormRouteName: string;
  currentlyUsedCapacity?: number;
  allResources?: App.Entities.Resource[];
}>();

// We need this to match the original selected resource for edit
// If this changes, we don't need to consider current amount of resources for capacity
const emit = defineEmits(["success"]);
const spinning = ref(true)

const reservationResourceForm = useForm(props.reservationResourceForm);

const isReservationResourceSameForUpdate = computed(() => {
  return (
    reservationResourceForm.resource_id === props.reservationResourceForm.resource_id
  );
});

const date = ref<string[]>([
  props.reservationResourceForm.start_time,
  props.reservationResourceForm.end_time,
]);

watch(date, (value) => {
  reservationResourceForm.start_time = value[0];
  reservationResourceForm.end_time = value[1];
});

const getAllResources = async (value: string[]) => {

  let exceptReservations = [];
  let exceptResources = [];

  if (isReservationResourceSameForUpdate.value) {
    exceptReservations = [props.reservationResourceForm.reservation_id];
    exceptResources = [props.reservationResourceForm.resource_id];
  }

  router.reload({
    data: {
      dateTimeRange: {
        start: value[0],
        end: value[1],
      },
      'except-reservations': exceptReservations,
      'except-resources': exceptResources,
    },
    only: ["allResources"],
    onSuccess: () => {
      spinning.value = false;
    },
  });
};

await getAllResources([
  props.reservationResourceForm.start_time,
  props.reservationResourceForm.end_time,
]);

const onDateChange = (value: string[]) => {
  spinning.value = true;
  getAllResources(value);
  if (props.reservationResourceFormRouteName === "reservationResources.store") {
    reservationResourceForm.resource_id = null;
    reservationResourceForm.quantity = 1;
  }
};

const allResourceOptions = computed(() => {
  return props.allResources?.map((resource) => ({
    ...resource,
    disabled:
      resource.lowestCapacityAtDateTimeRange === 0 || !resource.is_reservable,
  }));
});

const capacityMax = computed(() => {
  const capacityMax = props.allResources?.find(
    (resource) => resource.id === reservationResourceForm.resource_id
  )?.lowestCapacityAtDateTimeRange ?? 1

  return capacityMax;
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
  if (props.reservationResourceFormRouteName === "reservationResources.update") {
    reservationResourceForm.put(
      route("reservationResources.update", reservationResourceForm.id),
      {
        preserveScroll: true,
        onSuccess: () => {
          reservationResourceForm.reset();
          emit("success");
        },
      }
    );
    return;
  }
  reservationResourceForm.post(route("reservationResources.store"), {
    preserveScroll: true,
    onSuccess: () => {
      reservationResourceForm.reset();
      emit("success");
    },
  });
};
</script>
