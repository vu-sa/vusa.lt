<template>
  <AdminForm :model="form" label-placement="top" :is-create-form="!reservation.id">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <template v-if="$page.props.app.locale === 'lt'">
          <a target="_blank" class="mb-4 flex items-center gap-1"
            href="https://vustudentuatstovybe.sharepoint.com/:b:/s/vieningai/ERnxptqtoF5DmDiqAbpfBewBjV-z7QcgAZiZi5w5sS1ODQ?e=cP6Zsv">
            <IFluentLink24Filled />
            <strong class="underline">Rezervacijų atmintinė</strong>
          </a>
        </template>
        <template v-else>
          <a target="_blank" class="mb-4 flex items-center gap-1"
            href="https://vustudentuatstovybe.sharepoint.com/:b:/s/vieningai/ESPcgxR0HqNFj0TBAQL4hmQBLmE5RSN72cEFe9psis3gjg?e=wS2uKj">
            <IFluentLink24Filled />
            <strong class="underline">Reservation guide</strong>
          </a>
        </template>
        <MdSuspenseWrapper directory="reservations" :locale="$page.props.app.locale" file="description" />
      </template>
      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required>
        <Input v-model="form.name" :placeholder="RESERVATION_PLACEHOLDERS.name[$page.props.app.locale]" />
      </FormFieldWrapper>
      <FormFieldWrapper id="description" :label="$t('forms.fields.description')" required>
        <Textarea v-model="form.description"
          :placeholder="RESERVATION_PLACEHOLDERS.description[$page.props.app.locale]" />
      </FormFieldWrapper>
    </FormElement>
    <FormElement :icon="ResourceIcon">
      <template #title>
        {{
          capitalize($tChoice("entities.resource.model", 2))
        }}
      </template>
      <template #description>
        <MdSuspenseWrapper directory="reservations" :locale="$page.props.app.locale" file="resources" />
        <a class="w-fit" target="_blank" :href="route('resources.index')">
          <div class="inline-flex items-center gap-2">
            <IFluentCube24Regular class="text-gray-400" />
            <strong class="underline">{{
              $t("entities.meta.model_list", {
                model: capitalize($tChoice("entities.resource.model", 11)),
              })
            }}</strong>
          </div>
        </a>
      </template>
      <FormFieldWrapper id="period" :label="capitalize($t('entities.reservation.period'))" required>
        <div class="flex flex-wrap gap-4">
          <DateTimePicker v-model="startDate" :placeholder="$t('Pradžia')" :minute-step="15" @change="onDateChange" />
          <DateTimePicker v-model="endDate" :placeholder="$t('Pabaiga')" :minute-step="15" @change="onDateChange" />
        </div>
      </FormFieldWrapper>
      <FormFieldWrapper id="resources" :label="$t('Pasirinkti ištekliai')">
        <div class="space-y-3">
          <div>
            <ResourceSelectDialog
              v-model:open="resourceDialogOpen"
              :date-time-range
              :excluded-ids="selectedResourceIds"
              multiple
              @confirm="onResourcesConfirm"
            >
              <template #trigger>
                <Button type="button" variant="outline" :disabled="!hasValidRange">
                  <IFluentCube24Regular class="mr-2 h-4 w-4" />
                  {{ $t('Naršyti išteklius') }}
                </Button>
              </template>
            </ResourceSelectDialog>
            <p v-if="!hasValidRange" class="mt-1 text-xs text-muted-foreground">
              {{ $t('Pirmiausia pasirinkite rezervacijos laikotarpį.') }}
            </p>
          </div>

          <p v-if="form.resources.length === 0" class="text-sm text-muted-foreground">
            {{ $t('Nėra pridėtų išteklių') }}
          </p>

          <div v-else class="space-y-2">
            <div
              v-for="(item, index) in form.resources"
              :key="item.id"
              class="flex items-center justify-between gap-3 rounded-lg border p-3"
            >
              <div class="flex min-w-0 items-center gap-2">
                <IFluentCube24Regular class="h-4 w-4 shrink-0 text-gray-400" />
                <span class="truncate">{{ resourceName(item.id) }}</span>
                <Badge v-if="resourceTenant(item.id)" variant="secondary" class="shrink-0 text-xs">
                  {{ resourceTenant(item.id) }}
                </Badge>
              </div>
              <div class="flex shrink-0 items-center gap-2">
                <span class="text-xs text-gray-400">
                  {{ getleftCapacity(item.id) }} {{ $t("iš") }} {{ resourceCapacity(item.id) }}
                </span>
                <NumberField v-model="item.quantity" :min="1" :max="getleftCapacity(item.id)" />
                <Button type="button" variant="ghost" size="icon" @click="removeResource(index)">
                  <IFluentDelete24Regular />
                </Button>
              </div>
            </div>
          </div>
        </div>
      </FormFieldWrapper>
    </FormElement>
    <FormElement>
      <template #title>
        {{ $t("forms.context.additional_info") }}
      </template>
      <div class="flex items-center gap-2">
        <Checkbox id="condition" v-model="conditionAcquaintance" />
        <Label for="condition">
          <template v-if="$page.props.app.locale === 'lt'">
            Sutinku įdėmiai sekti rezervacijos informaciją, išteklius pasiimti
            ir grąžinti laiku.
          </template>
          <template v-else>
            I agree to carefully follow the reservation information, take and
            return the resources on time.
          </template>
        </Label>
      </div>
    </FormElement>
    <template #buttons>
      <Button :disabled="!conditionAcquaintance" @click="submit">
        Pateikti
      </Button>
    </template>
  </AdminForm>
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import IFluentCube24Regular from '~icons/fluent/cube24-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { DateTimePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { NumberField } from '@/Components/ui/number-field';
import { Textarea } from '@/Components/ui/textarea';
import { ResourceSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { RESERVATION_PLACEHOLDERS } from '@/Constants/I18n/Placeholders';
import { capitalize } from '@/Utils/String';
import type { ReservationCreationTemplate } from '@/Pages/Admin/Reservations/CreateReservation.vue';
import MdSuspenseWrapper from '@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue';
import { ResourceIcon } from '@/Components/icons';

defineEmits<{
  (event: 'update:value', value: number | null): void;
  (event: 'submit:form', form: unknown): void;
}>();

// Only creation remains: a reservation is never updated as a whole, so the sole
// caller is CreateReservation.vue passing `reservations.store`.
const props = defineProps<{
  reservation: ReservationCreationTemplate;
  allResources: App.Entities.Resource[];
  modelRoute: string;
  rememberKey?: 'CreateReservation';
}>();

const conditionAcquaintance = ref(false);

const routeToSubmit = computed(() => {
  return props.reservation?.id
    ? route(props.modelRoute, props.reservation.id)
    : route(props.modelRoute);
});

const form = props.rememberKey
  ? useForm(props.rememberKey, props.reservation)
  : useForm(props.reservation);

// Convert timestamps to Date objects for DateTimePicker
const startDate = ref<Date | null>(form.start_time ? new Date(form.start_time) : null);
const endDate = ref<Date | null>(form.end_time ? new Date(form.end_time) : null);

watch([startDate, endDate], ([newStart, newEnd]) => {
  form.start_time = newStart ? newStart.getTime() : null;
  form.end_time = newEnd ? newEnd.getTime() : null;
});

const resourceDialogOpen = ref(false);

const selectedResourceIds = computed(() => form.resources.map(resource => resource.id));

const dateTimeRange = computed(() => ({
  start: form.start_time ?? 0,
  end: form.end_time ?? 0,
}));

const hasValidRange = computed(() => !!form.start_time && !!form.end_time && form.start_time < form.end_time);

const findResource = (id: string) => props.allResources.find(resource => resource.id === id);

const resourceName = (id: string) => findResource(id)?.name ?? id;
const resourceTenant = (id: string) => findResource(id)?.tenant?.shortname;
const resourceCapacity = (id: string) => findResource(id)?.capacity;

const onResourcesConfirm = (hits: NormalizedSearchHit[]) => {
  const existing = new Set(selectedResourceIds.value);
  for (const hit of hits) {
    if (!existing.has(hit.recordId)) {
      form.resources.push({ id: hit.recordId, quantity: 1 });
      existing.add(hit.recordId);
    }
  }
};

const removeResource = (index: number) => {
  form.resources.splice(index, 1);
};

const onDateChange = () => {
  if (!startDate.value || !endDate.value) return;

  form.resources = [];
  // Reset dirty state so the "unsaved changes" guard doesn't fire on the reload
  form.defaults();
  router.reload({
    data: {
      dateTimeRange: { start: startDate.value.getTime(), end: endDate.value.getTime() },
    },
    preserveScroll: true,
    only: ['resources'],
  });
};

const getleftCapacity = (id: string) => {
  return props.allResources.find(resource => resource.id === id)
    ?.lowestCapacityAtDateTimeRange;
};

const submit = () => {
  // Clear dirty state before navigating so the "unsaved changes" guard doesn't fire
  form.defaults();
  form.submit(
    props.reservation?.id ? 'patch' : 'post',
    routeToSubmit.value, {
      preserveScroll: true,
    });
};
</script>
