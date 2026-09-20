<template>
  <FormPage
    :title="$t('Nauja rezervacija')"
    :head-title="$t('Nauja rezervacija')"
    :lead="$t('Pasirink laiką, tada ištekliai, kurie tuo metu laisvi.')"
    :entity-type="ModelEnum.RESERVATION"
    :back-href="route('reservations.index')"
    :back-label="capitalize($tChoice('entities.reservation.model', 2))"
    :save-label="$t('Pateikti')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :disabled="!conditionAcquaintance"
    :errors="form.errors"
    :field-ids
    mode="create"
    @submit="submit"
  >
    <FormSection :title="$t('Kas ir kada?')" :description="$t('Pavadinimas, kad kiti suprastų, kam skirta, ir laikotarpis.')">
      <div class="space-y-1.5">
        <Label for="reservation-name" class="text-sm font-medium">{{ $t('forms.fields.title') }} *</Label>
        <Input
          id="reservation-name"
          v-model="form.name"
          :placeholder="RESERVATION_PLACEHOLDERS.name[$page.props.app.locale]"
        />
        <p v-if="form.errors.name" class="text-xs text-destructive">
          {{ form.errors.name }}
        </p>
      </div>

      <div class="space-y-1.5">
        <Label for="reservation-description" class="text-sm font-medium">{{ $t('forms.fields.description') }} *</Label>
        <Textarea
          id="reservation-description"
          v-model="form.description"
          rows="3"
          :placeholder="RESERVATION_PLACEHOLDERS.description[$page.props.app.locale]"
        />
        <p v-if="form.errors.description" class="text-xs text-destructive">
          {{ form.errors.description }}
        </p>
      </div>

      <!-- Date and time are two fields each, never a combined popover (rules/pages.md → Pickers). -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5" role="group" aria-labelledby="reservation-start-label">
          <Label id="reservation-start-label" class="text-sm font-medium">{{ $t('Pradžia') }} *</Label>
          <div class="flex flex-wrap items-center gap-2">
            <DatePicker v-model="startDay" class="min-w-40 flex-1" />
            <TimePicker v-model="startTime" :minute-step="15" class="w-32" />
          </div>
          <p v-if="form.errors.start_time" class="text-xs text-destructive">
            {{ form.errors.start_time }}
          </p>
        </div>

        <div class="space-y-1.5" role="group" aria-labelledby="reservation-end-label">
          <Label id="reservation-end-label" class="text-sm font-medium">{{ $t('Pabaiga') }} *</Label>
          <div class="flex flex-wrap items-center gap-2">
            <DatePicker v-model="endDay" class="min-w-40 flex-1" />
            <TimePicker v-model="endTime" :minute-step="15" class="w-32" />
          </div>
          <p v-if="form.errors.end_time" class="text-xs text-destructive">
            {{ form.errors.end_time }}
          </p>
        </div>
      </div>

      <details class="group border-t border-border pt-3">
        <summary class="u-touch flex cursor-pointer select-none items-center justify-between text-sm font-semibold text-foreground">
          {{ $t('Plačiau apie rezervacijas') }}
          <ChevronDown class="size-4 text-muted-foreground transition-transform group-open:rotate-180" aria-hidden="true" />
        </summary>
        <div class="mt-3 space-y-3 text-sm">
          <a class="inline-flex items-center gap-1.5 font-medium underline underline-offset-4" target="_blank" rel="noopener" :href="reservationGuideUrl">
            <ExternalLink class="size-4" aria-hidden="true" />
            {{ $t('Rezervacijų atmintinė') }}
          </a>
          <MdSuspenseWrapper directory="reservations" :locale="$page.props.app.locale" file="description" />
        </div>
      </details>
    </FormSection>

    <FormSection
      :title="capitalize($tChoice('entities.resource.model', 2))"
      :description="$t('Rodomi tik ištekliai, kurių pasirinktu laiku užtenka.')"
    >
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
              <Button type="button" variant="outline" class="u-touch" :disabled="!hasValidRange">
                <Search class="size-4" aria-hidden="true" />
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

        <ul v-else class="divide-y divide-border border-y border-border" data-testid="selected-resources">
          <li v-for="(item, index) in form.resources" :key="item.id" class="flex flex-wrap items-center justify-between gap-3 py-2.5">
            <div class="flex min-w-0 items-center gap-2">
              <EntityTypeMark :type="ModelEnum.RESOURCE" />
              <span class="truncate text-sm">{{ resourceName(item.id) }}</span>
              <span
                v-if="resourceTenant(item.id)"
                class="shrink-0 border border-border bg-secondary px-1.5 py-0.5 text-xs font-medium text-muted-foreground"
              >
                {{ resourceTenant(item.id) }}
              </span>
            </div>
            <div class="flex shrink-0 items-center gap-2">
              <span class="text-xs text-muted-foreground tabular-nums">
                {{ getLeftCapacity(item.id) }} {{ $t("iš") }} {{ resourceCapacity(item.id) }}
              </span>
              <NumberField v-model="item.quantity" :min="1" :max="getLeftCapacity(item.id)" />
              <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                class="pointer-coarse:size-11"
                :title="$t('Pašalinti')"
                :aria-label="$t('Pašalinti')"
                @click="removeResource(index)"
              >
                <Trash2 class="size-4" aria-hidden="true" />
              </Button>
            </div>
          </li>
        </ul>
        <p v-if="form.errors.resources" class="text-xs text-destructive">
          {{ form.errors.resources }}
        </p>
      </div>
    </FormSection>

    <FormSection :title="$t('Prieš pateikiant')">
      <div class="flex items-start gap-2.5">
        <Checkbox id="condition" v-model="conditionAcquaintance" class="mt-0.5" />
        <Label for="condition" class="cursor-pointer text-sm font-normal">
          {{ $t('Sutinku įdėmiai sekti rezervacijos informaciją, išteklius pasiimti ir grąžinti laiku.') }}
        </Label>
      </div>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ChevronDown, ExternalLink, Search, Trash2 } from 'lucide-vue-next';
import { capitalize, computed, ref, watch } from 'vue';

import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { DatePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { NumberField } from '@/Components/ui/number-field';
import { Textarea } from '@/Components/ui/textarea';
import { TimePicker, type TimeValue } from '@/Components/ui/time-picker';
import { RESERVATION_PLACEHOLDERS } from '@/Constants/I18n/Placeholders';
import { ResourceSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import MdSuspenseWrapper from '@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue';
import type { ReservationCreationTemplate } from '@/Pages/Admin/Reservations/CreateReservation.vue';
import { ModelEnum } from '@/Types/enums';

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

// The guide is a SharePoint document that exists as two separate files, so the locale picks
// the URL rather than a translated string.
const reservationGuideUrl = computed(() => usePage().props.app.locale === 'lt'
  ? 'https://vustudentuatstovybe.sharepoint.com/:b:/s/vieningai/ERnxptqtoF5DmDiqAbpfBewBjV-z7QcgAZiZi5w5sS1ODQ?e=cP6Zsv'
  : 'https://vustudentuatstovybe.sharepoint.com/:b:/s/vieningai/ESPcgxR0HqNFj0TBAQL4hmQBLmE5RSN72cEFe9psis3gjg?e=wS2uKj');

const form = props.rememberKey
  ? useForm(props.rememberKey, props.reservation)
  : useForm(props.reservation);

// Error keys that are not the id of the field they belong to.
const fieldIds = {
  name: 'reservation-name',
  description: 'reservation-description',
};

// --- Period: a date field and a time field each for the start and the end -----------------------

/** The date picker speaks UTC-noon dates so no timezone can move the day; the timestamps are local. */
const toPickerDay = (timestamp: number | null | undefined): Date | undefined => {
  if (!timestamp) {
    return undefined;
  }

  const date = new Date(timestamp);

  return new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate(), 12));
};

const toTime = (timestamp: number | null | undefined): TimeValue | undefined => {
  if (!timestamp) {
    return undefined;
  }

  const date = new Date(timestamp);

  return { hour: date.getHours(), minute: date.getMinutes() };
};

const combine = (day: Date | undefined, time: TimeValue | undefined): number | null => {
  if (!day || !time) {
    return null;
  }

  return new Date(day.getUTCFullYear(), day.getUTCMonth(), day.getUTCDate(), time.hour, time.minute).getTime();
};

const startDay = ref<Date | undefined>(toPickerDay(form.start_time));
const startTime = ref<TimeValue | undefined>(toTime(form.start_time));
const endDay = ref<Date | undefined>(toPickerDay(form.end_time));
const endTime = ref<TimeValue | undefined>(toTime(form.end_time));

const start = computed(() => combine(startDay.value, startTime.value));
const end = computed(() => combine(endDay.value, endTime.value));

watch([start, end], ([newStart, newEnd]) => {
  form.start_time = newStart as number;
  form.end_time = newEnd as number;

  // Capacity depends on the period, so a new period starts from an empty basket.
  if (newStart && newEnd && newStart < newEnd) {
    onPeriodChange(newStart, newEnd);
  }
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

function onPeriodChange(newStart: number, newEnd: number) {
  form.resources = [];
  // Reset dirty state so the "unsaved changes" guard doesn't fire on the reload
  form.defaults();
  router.reload({
    data: { dateTimeRange: { start: newStart, end: newEnd } },
    preserveScroll: true,
    only: ['resources'],
  });
}

const getLeftCapacity = (id: string) => findResource(id)?.lowestCapacityAtDateTimeRange;

const submit = () => {
  // Clear dirty state before navigating so the "unsaved changes" guard doesn't fire
  form.defaults();
  form.submit('post', route(props.modelRoute), { preserveScroll: true });
};
</script>
