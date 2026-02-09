<template>
  <AdminForm :model="form" label-placement="top">
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
    <FormElement :icon="Icons.RESOURCE">
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
        <DynamicListInput v-model="form.resources" :create-item="onCreate" allow-empty
          empty-text="Nėra pridėtų išteklių" add-first-text="Pridėti pirmą išteklių" add-text="Pridėti išteklių">
          <template #item="{ item }">
            <div class="flex w-full gap-2">
              <Select v-model="item.id" @update:model-value="item.quantity = 1">
                <SelectTrigger class="min-w-64">
                  <SelectValue :placeholder="RESERVATION_PLACEHOLDERS.resource[$page.props.app.locale]" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="resource in allResourceOptions" :key="resource.id" :value="resource.id"
                    :disabled="resource.disabled">
                    <div class="flex items-center gap-2">
                      <IFluentCube24Regular class="h-4 w-4 text-gray-400" />
                      <span>{{ resource.name }}</span>
                      <span class="text-gray-400">
                        {{ resource.lowestCapacityAtDateTimeRange }} {{ $t("iš") }} {{ resource.capacity }}
                      </span>
                      <Badge variant="secondary" class="text-xs">
                        {{ resource.tenant?.shortname }}
                      </Badge>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <NumberField v-model="item.quantity" :min="1" :max="getleftCapacity(item.id)" />
            </div>
          </template>
        </DynamicListInput>
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
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { DateTimePicker } from '@/Components/ui/date-picker';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { NumberField } from '@/Components/ui/number-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { RESERVATION_PLACEHOLDERS } from '@/Constants/I18n/Placeholders';
import { capitalize } from '@/Utils/String';
import Icons from '@/Types/Icons/regular';
import type { ReservationCreationTemplate } from '@/Pages/Admin/Reservations/CreateReservation.vue';
import type { ReservationEditType } from '@/Pages/Admin/Reservations/EditReservation.vue';
import MdSuspenseWrapper from '@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue';

defineEmits<{
  (event: 'update:value', value: number | null): void;
  (event: 'submit:form', form: unknown): void;
}>();

// TODO: cleanup the modelRoute
const props = defineProps<{
  reservation: ReservationCreationTemplate | ReservationEditType;
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

const onCreate = () => {
  return {
    id: undefined,
    quantity: 1,
  };
};

const onDateChange = () => {
  if (!startDate.value || !endDate.value) return;

  form.resources = [];
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

const allResourceOptions = computed(() => {
  const selectedResources = form.resources.map(resource => resource.id);

  return props.allResources.map(resource => ({
    ...resource,
    disabled:
      resource.lowestCapacityAtDateTimeRange === 0
      || selectedResources.includes(resource.id)
      || !resource.is_reservable,
  }));
});

const submit = () => {
  form.submit(
    props.reservation?.id ? 'patch' : 'post',
    routeToSubmit.value, {
      preserveScroll: true,
    });
};
</script>
