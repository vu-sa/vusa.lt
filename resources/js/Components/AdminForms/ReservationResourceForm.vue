<template>
  <div class="space-y-4">
    <!-- Loading state -->
    <div v-if="spinning" class="flex flex-col items-center justify-center py-8 gap-3">
      <Spinner class="size-6" />
      <p class="text-sm text-muted-foreground">
        {{ $t('Kraunami visi resursai...') }}
      </p>
    </div>

    <!-- Form content -->
    <div v-else class="space-y-4">
      <!-- Date Range Picker -->
      <div class="space-y-2">
        <Label for="reservation-period">{{ capitalize($t('entities.reservation.period')) }} <span class="text-destructive">*</span></Label>
        <DateRangePicker
          id="reservation-period"
          v-model="dateRange"
          :number-of-months="2"
          @change="onDateChange"
        />
      </div>

      <!-- Resource Select -->
      <div class="space-y-2">
        <Label for="resource-select">{{ $t('forms.fields.title') }}</Label>
        <Select v-model="selectedResourceId" @update:model-value="onResourceChange">
          <SelectTrigger id="resource-select" class="w-full">
            <template v-if="selectedResource">
              <div class="flex items-center gap-2">
                <component :is="Icons.RESOURCE" class="size-4 text-muted-foreground" />
                <span>{{ selectedResource.name }}</span>
                <span class="text-muted-foreground text-xs">
                  {{ selectedResource.lowestCapacityAtDateTimeRange }} {{ $t('iš') }} {{ selectedResource.capacity }}
                </span>
                <Badge variant="outline" class="text-xs">
                  {{ selectedResource.tenant?.shortname }}
                </Badge>
              </div>
            </template>
            <template v-else>
              <SelectValue :placeholder="`${$t('Pasirinkite')}...`" />
            </template>
          </SelectTrigger>
          <SelectContent side="bottom" align="start">
            <SelectItem
              v-for="resource in allResourceOptions"
              :key="resource.id"
              :value="String(resource.id)"
              :disabled="resource.disabled"
            >
              <div class="flex items-center gap-2">
                <component :is="Icons.RESOURCE" class="size-4 text-muted-foreground" />
                <span>{{ resource.name }}</span>
                <span class="text-muted-foreground text-xs">
                  {{ resource.lowestCapacityAtDateTimeRange }} {{ $t('iš') }} {{ resource.capacity }}
                </span>
                <Badge variant="outline" class="text-xs">
                  {{ resource.tenant?.shortname }}
                </Badge>
              </div>
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Quantity Input -->
      <div class="space-y-2">
        <Label for="quantity-input">{{ $t('forms.fields.quantity') }}</Label>
        <NumberField
          id="quantity-input"
          v-model="reservationResourceForm.quantity"
          :min="1"
          :max="capacityMax"
          :disabled="reservationResourceForm.resource_id === null"
          class="w-full max-w-xs"
        />
        <p v-if="reservationResourceForm.resource_id && capacityMax > 0" class="text-xs text-muted-foreground">
          {{ $t('Maksimalus kiekis') }}: {{ capacityMax }}
        </p>
        <p v-if="quantityError" class="text-xs text-destructive">
          {{ quantityError }}
        </p>
      </div>

      <!-- Submit Button -->
      <Button :disabled="!canSubmit || reservationResourceForm.processing" @click="handleSubmit">
        <Spinner v-if="reservationResourceForm.processing" class="mr-2 size-4" />
        {{ $t("forms.submit") }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { type InertiaForm, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import { CalendarDateTime, getLocalTimeZone, today, type DateValue } from '@internationalized/date';
import type { DateRange } from 'reka-ui';

import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Spinner } from '@/Components/ui/spinner';
import { NumberField } from '@/Components/ui/number-field';
import { DateRangePicker } from '@/Components/ui/date-range-picker';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { capitalize } from '@/Utils/String';
import Icons from '@/Types/Icons/regular';

const props = defineProps<{
  reservationResourceForm: InertiaForm<{
    id: string | undefined;
    resource_id: string | null;
    reservation_id: string;
    quantity: number;
    start_time: number;
    end_time: number;
  }>;
  reservationResourceFormRouteName: string;
  currentlyUsedCapacity?: number;
  allResources?: App.Entities.Resource[];
  rememberKey?: 'CreateReservationResource';
}>();

// We need this to match the original selected resource for edit
// If this changes, we don't need to consider current amount of resources for capacity
const emit = defineEmits(['success']);
const spinning = ref(true);

const reservationResourceForm = props.rememberKey
  ? useForm(props.rememberKey, props.reservationResourceForm)
  : useForm(props.reservationResourceForm);

// Helper to convert timestamp to CalendarDateTime
const timestampToCalendarDateTime = (timestamp: number): CalendarDateTime => {
  const date = new Date(timestamp);
  return new CalendarDateTime(
    date.getFullYear(),
    date.getMonth() + 1,
    date.getDate(),
    date.getHours(),
    date.getMinutes(),
  );
};

// Helper to convert CalendarDateTime to timestamp
const calendarDateTimeToTimestamp = (dateValue: DateValue): number => {
  return dateValue.toDate(getLocalTimeZone()).getTime();
};

// Date range for the picker
const dateRange = ref<DateRange>({
  start: timestampToCalendarDateTime(props.reservationResourceForm.start_time),
  end: timestampToCalendarDateTime(props.reservationResourceForm.end_time),
});

// Selected resource ID as string for Select component
const selectedResourceId = ref<string | undefined>(
  props.reservationResourceForm.resource_id
    ? String(props.reservationResourceForm.resource_id)
    : undefined,
);

// Validation computed properties
const quantityError = computed(() => {
  if (reservationResourceForm.resource_id === null) return null;
  if (reservationResourceForm.quantity < 1) {
    return $t('Kiekis turi būti bent 1');
  }
  if (reservationResourceForm.quantity > capacityMax.value) {
    return `${$t('Kiekis viršija maksimalų leistiną')}: ${capacityMax.value}`;
  }
  return null;
});

const canSubmit = computed(() => {
  return (
    reservationResourceForm.resource_id !== null
    && reservationResourceForm.quantity >= 1
    && reservationResourceForm.quantity <= capacityMax.value
    && !quantityError.value
  );
});

// Watch for resource changes
const onResourceChange = (value: string | undefined) => {
  if (value) {
    reservationResourceForm.resource_id = value;
    reservationResourceForm.quantity = 1;
  }
  else {
    reservationResourceForm.resource_id = null;
  }
};

// Selected resource computed
const selectedResource = computed(() => {
  if (!reservationResourceForm.resource_id) return null;
  return props.allResources?.find(r => String(r.id) === reservationResourceForm.resource_id);
});

const isReservationResourceSameForUpdate = computed(() => {
  return (
    reservationResourceForm.resource_id === props.reservationResourceForm.resource_id
  );
});

const getAllResources = (startTime: number, endTime: number) => {
  let exceptReservations: number[] = [];
  let exceptResources: (number | null)[] = [];

  if (isReservationResourceSameForUpdate.value) {
    exceptReservations = [props.reservationResourceForm.reservation_id];
    exceptResources = [props.reservationResourceForm.resource_id];
  }

  router.reload({
    data: {
      'dateTimeRange': {
        start: startTime,
        end: endTime,
      },
      'except-reservations': exceptReservations,
      'except-resources': exceptResources,
    },
    only: ['allResources'],
    onSuccess: () => {
      spinning.value = false;
    },
  });
};

// Load resources on mount instead of using top-level await
onMounted(() => {
  getAllResources(
    props.reservationResourceForm.start_time,
    props.reservationResourceForm.end_time,
  );
});

const onDateChange = (value: DateRange) => {
  if (!value.start || !value.end) return;

  spinning.value = true;

  const startTime = calendarDateTimeToTimestamp(value.start);
  const endTime = calendarDateTimeToTimestamp(value.end);

  reservationResourceForm.start_time = startTime;
  reservationResourceForm.end_time = endTime;

  getAllResources(startTime, endTime);

  if (props.reservationResourceFormRouteName === 'reservationResources.store') {
    reservationResourceForm.resource_id = null;
    selectedResourceId.value = undefined;
    reservationResourceForm.quantity = 1;
  }
};

const allResourceOptions = computed(() => {
  return props.allResources?.map(resource => ({
    ...resource,
    disabled:
      resource.lowestCapacityAtDateTimeRange === 0 || !resource.is_reservable,
  })) ?? [];
});

const capacityMax = computed(() => {
  const capacity = props.allResources?.find(
    resource => String(resource.id) === reservationResourceForm.resource_id,
  )?.lowestCapacityAtDateTimeRange ?? 1;

  return capacity;
});

const handleSubmit = () => {
  if (props.reservationResourceFormRouteName === 'reservationResources.update') {
    reservationResourceForm.put(
      route('reservationResources.update', reservationResourceForm.id),
      {
        preserveScroll: true,
        onSuccess: () => {
          reservationResourceForm.reset();
          emit('success');
        },
      },
    );
    return;
  }
  reservationResourceForm.post(route('reservationResources.store'), {
    preserveScroll: true,
    onSuccess: () => {
      reservationResourceForm.reset();
      emit('success');
    },
  });
};
</script>
