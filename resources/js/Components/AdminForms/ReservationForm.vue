<template>
  <FormPage
    :title="form.name || $t('Nauja rezervacija')"
    :bar-title="$t('Nauja rezervacija')"
    :head-title="$t('Nauja rezervacija')"
    :lead="$t('reservations.cart.checkout_lead')"
    :entity-type="ModelEnum.RESERVATION"
    :back-href="route('reservations.index')"
    :back-label="capitalize($tChoice('entities.reservation.model', 2))"
    :save-label="$t('Pateikti')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :available-locales="[]"
    mode="create"
    @submit="submit"
  >
    <template v-if="draftStatus" #title-status>
      <StatusBadge :status="draftStatus" data-testid="reservation-draft-status" />
    </template>

    <FormFieldWrapper
      id="reservation-name"
      :label="$t('forms.fields.title')"
      required
      :hint="$t('reservations.cart.name_hint')"
      :char-count="form.name?.length || 0"
      :max-length="255"
      :error="form.errors.name"
    >
      <Input
        id="reservation-name"
        v-model="form.name"
        :placeholder="RESERVATION_PLACEHOLDERS.name[$page.props.app.locale]"
        :class="['h-11', fieldSurfaceClass]"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="reservation-description"
      :label="$t('forms.fields.description')"
      required
      :hint="$t('reservations.cart.description_hint')"
      :error="form.errors.description"
    >
      <Textarea
        id="reservation-description"
        v-model="form.description"
        rows="3"
        :placeholder="RESERVATION_PLACEHOLDERS.description[$page.props.app.locale]"
        :class="fieldSurfaceClass"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="reservation-period"
      :label="$t('reservations.cart.period_label')"
      required
      :hint="$t('reservations.cart.period_hint')"
    >
      <ReservationPeriodFields
        id-prefix="reservation"
        :model-value="period"
        :start-error="form.errors.start_time"
        :end-error="form.errors.end_time"
        @update:model-value="onPeriodChange"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="reservation-resources"
      :label="capitalize($tChoice('entities.resource.model', 2))"
      required
    >
      <div class="space-y-3">
        <div
          v-if="problemCount > 0"
          class="border-l-2 border-status-danger bg-status-danger-surface px-4 py-3 text-sm"
          role="alert"
          data-testid="reservation-cart-conflicts"
        >
          <p class="font-semibold text-status-danger">
            {{ $t('reservations.cart.conflicts_title') }}
          </p>
          <p class="mt-0.5 text-muted-foreground">
            {{ $t('reservations.cart.conflicts_description') }}
          </p>
        </div>

        <p v-if="items.length === 0" class="border border-dashed border-border p-4 text-sm text-muted-foreground">
          {{ $t('reservations.cart.empty_checkout') }}
        </p>
        <ul v-else :class="['divide-y divide-border border border-border px-3', fieldSurfaceClass]" data-testid="selected-resources">
          <ReservationCartItemRow v-for="item in items" :key="item.resource_id" :item />
        </ul>
        <p v-for="message in resourceErrors" :key="message" class="text-xs text-destructive">
          {{ message }}
        </p>

        <div class="flex flex-wrap gap-2">
          <Button
            id="reservation-resources"
            type="button"
            :variant="items.length === 0 ? 'brand' : 'outline'"
            :voice="items.length === 0 ? 'brand' : 'sentence'"
            class="u-touch"
            data-testid="reservation-browse-resources"
            @click="browseResources"
          >
            <Search class="size-4" aria-hidden="true" />
            {{ items.length === 0 ? $t('reservations.cart.browse') : $t('reservations.cart.add_more') }}
          </Button>
          <ResourceSelectDialog
            v-model:open="resourceDialogOpen"
            :date-time-range
            :excluded-ids="selectedResourceIds"
            multiple
            @confirm="onResourcesConfirm"
          >
            <template #trigger>
              <Button type="button" variant="ghost" voice="sentence" class="u-touch" :disabled="!hasValidRange">
                <Plus class="size-4" aria-hidden="true" />
                {{ $t('reservations.cart.quick_add') }}
              </Button>
            </template>
          </ResourceSelectDialog>
        </div>
      </div>
    </FormFieldWrapper>

    <template #aside>
      <FormPanel :title="$t('reservations.cart.submit_panel')" :icon="Send" title-class="text-brand">
        <div
          class="flex items-start gap-2 border border-border bg-secondary/40 p-3 text-xs leading-relaxed text-muted-foreground"
          data-testid="reservation-draft-intro"
        >
          <Save class="mt-0.5 size-4 shrink-0 text-brand" aria-hidden="true" />
          <span>
            <span class="block font-semibold text-foreground">{{ $t('reservations.cart.draft_intro_title') }}</span>
            {{ $t('reservations.cart.draft_intro_description', { days: String(cart?.ttlDays ?? 14) }) }}
          </span>
        </div>

        <div class="flex items-start gap-2.5">
          <Checkbox id="condition" v-model="conditionAcquaintance" class="mt-0.5" />
          <Label for="condition" class="cursor-pointer text-sm font-normal">
            {{ $t('Sutinku įdėmiai sekti rezervacijos informaciją, išteklius pasiimti ir grąžinti laiku.') }}
          </Label>
        </div>
        <p v-if="form.errors.condition" class="text-xs text-destructive">
          {{ form.errors.condition }}
        </p>

        <p v-if="items.length > 0 && problemCount > 0" class="text-xs text-status-danger">
          {{ $t('reservations.cart.fix_before_submit') }}
        </p>
      </FormPanel>

      <FormPanel :title="$t('reservations.cart.more_about')" :icon="Info" title-class="text-brand">
        <a class="inline-flex items-center gap-1.5 text-sm font-medium underline underline-offset-4" target="_blank" rel="noopener noreferrer" :href="reservationGuideUrl">
          <ExternalLink class="size-4" aria-hidden="true" />
          {{ $t('Rezervacijų atmintinė') }}
        </a>
        <a class="inline-flex items-center gap-1.5 text-sm font-medium underline underline-offset-4" target="_blank" rel="noopener noreferrer" :href="rulesHref">
          <ExternalLink class="size-4" aria-hidden="true" />
          {{ $t('reservations.cart.rules') }}
        </a>
      </FormPanel>
    </template>

    <template v-if="cart" #danger-zone>
      <Button
        type="button"
        variant="outline"
        voice="sentence"
        class="border-destructive/40 text-destructive hover:border-destructive hover:bg-destructive hover:text-destructive-foreground"
        data-testid="reservation-draft-delete"
        @click="deleteDraftOpen = true"
      >
        <Trash2 class="size-4" aria-hidden="true" />
        {{ $t('reservations.cart.delete_draft') }}
      </Button>

      <ConfirmDialog
        v-model:open="deleteDraftOpen"
        :title="$t('reservations.cart.delete_draft_title')"
        :description="$t('reservations.cart.clear_description')"
        :confirm-label="$t('reservations.cart.delete_draft')"
        destructive
        @confirm="deleteDraft"
      />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { CloudCheck, ExternalLink, Info, Loader2, Plus, Save, Search, Send, Trash2 } from 'lucide-vue-next';
import { capitalize, computed, onMounted, ref, watch } from 'vue';

import FormFieldWrapper from './FormFieldWrapper.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel, StatusBadge } from '@/Components/Patterns';
import ReservationCartItemRow from '@/Components/Reservations/ReservationCartItemRow.vue';
import ReservationPeriodFields, { type ReservationPeriod } from '@/Components/Reservations/ReservationPeriodFields.vue';
import type { ReservationCart } from '@/Components/Reservations/types';
import { useReservationCart } from '@/Components/Reservations/useReservationCart';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { useDocsHref } from '@/Composables/useDocsHref';
import { RESERVATION_PLACEHOLDERS } from '@/Constants/I18n/Placeholders';
import type { StatusPresentation } from '@/Constants/statuses';
import { ResourceSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { ModelEnum } from '@/Types/enums';

// The checkout of the reservation cart: the draft holds the items and is saved as the user types,
// so the form only submits what the draft already says.
const props = defineProps<{
  cart: ReservationCart | null;
  defaultPeriod: ReservationPeriod;
}>();

const { items, period: cartPeriod, isSaving, setPeriod, saveDetails, add, clear } = useReservationCart();

const conditionAcquaintance = ref(false);

// The guide is a SharePoint document that exists as two separate files, so the locale picks
// the URL rather than a translated string.
const reservationGuideUrl = computed(() => usePage().props.app.locale === 'lt'
  ? 'https://vustudentuatstovybe.sharepoint.com/:b:/s/vieningai/ERnxptqtoF5DmDiqAbpfBewBjV-z7QcgAZiZi5w5sS1ODQ?e=cP6Zsv'
  : 'https://vustudentuatstovybe.sharepoint.com/:b:/s/vieningai/ESPcgxR0HqNFj0TBAQL4hmQBLmE5RSN72cEFe9psis3gjg?e=wS2uKj');

const rulesHref = useDocsHref('/rezervacijos/rezervacijos#susitarimai');

const period = computed<ReservationPeriod>(() => cartPeriod.value ?? props.defaultPeriod);

const form = useForm({
  name: props.cart?.name ?? '',
  description: props.cart?.description ?? '',
  start_time: period.value.start,
  end_time: period.value.end,
  resources: [] as { id: string; quantity: number }[],
});

// Error keys that are not the id of the field they belong to.
const fieldIds = {
  name: 'reservation-name',
  description: 'reservation-description',
};

const textsChanged = () => (form.name || null) !== (props.cart?.name ?? null)
  || (form.description || null) !== (props.cart?.description ?? null);

const deleteDraftOpen = ref(false);

// Set once the draft is deleted, so a text save still waiting on its debounce cannot recreate it.
let discarded = false;

const deleteDraft = () => {
  discarded = true;
  clear(() => router.visit(route('reservations.index')));
};

const saveTexts = useDebounceFn(() => {
  if (!discarded && textsChanged()) {
    saveDetails({ name: form.name || null, description: form.description || null });
  }
}, 800);

// The debounce may not have fired yet, so what was typed is saved before leaving for the list.
const browseResources = () => {
  const visitList = () => router.visit(route('resources.index'));

  // Also when nothing was typed: the saved draft is what turns the list's buttons into "Pridėti".
  if (!props.cart || textsChanged()) {
    saveDetails({ name: form.name || null, description: form.description || null }, visitList);
  }
  else {
    visitList();
  }
};

const draftStatus = computed<StatusPresentation | null>(() => {
  if (isSaving.value) {
    return { label: $t('reservations.cart.saving'), role: 'neutral', icon: Loader2 };
  }

  return props.cart ? { label: $t('reservations.cart.draft_saved'), role: 'neutral', icon: CloudCheck } : null;
});

watch(() => [form.name, form.description], () => {
  void saveTexts();
});

// A cart filled while browsing may have no period yet; the default one is shown, so store it.
onMounted(() => {
  if (!cartPeriod.value && items.value.length > 0) {
    setPeriod(props.defaultPeriod.start, props.defaultPeriod.end);
  }
});

function onPeriodChange(value: ReservationPeriod) {
  form.start_time = value.start;
  form.end_time = value.end;
  setPeriod(value.start, value.end);
}

watch(period, (value) => {
  form.start_time = value.start;
  form.end_time = value.end;
});

const problemCount = computed(() => items.value.filter(item => item.problem !== null).length);

const resourceErrors = computed(() => Object.entries(form.errors)
  .filter(([key]) => key.startsWith('resources'))
  .map(([, message]) => message));

// --- Quick add without leaving the checkout ---------------------------------------------------

const resourceDialogOpen = ref(false);

const selectedResourceIds = computed(() => items.value.map(item => item.resource_id));

const dateTimeRange = computed(() => ({ start: form.start_time, end: form.end_time }));

const hasValidRange = computed(() => !!form.start_time && !!form.end_time && form.start_time < form.end_time);

const onResourcesConfirm = (hits: NormalizedSearchHit[]) => {
  const existing = new Set(selectedResourceIds.value);

  for (const hit of hits) {
    if (!existing.has(hit.recordId)) {
      add(hit.recordId);
    }
  }
};

// Checked on press rather than by disabling the button, so pressing it always says what is missing.
const findClientErrors = () => {
  const errors: Partial<Record<'name' | 'description' | 'resources' | 'condition', string>> = {};

  if (!form.name.trim()) {
    errors.name = $t('reservations.cart.name_required');
  }
  if (!form.description.trim()) {
    errors.description = $t('reservations.cart.description_required');
  }
  if (items.value.length === 0) {
    errors.resources = $t('reservations.cart.resources_required');
  }
  else if (problemCount.value > 0) {
    errors.resources = $t('reservations.cart.fix_before_submit');
  }
  if (!conditionAcquaintance.value) {
    errors.condition = $t('reservations.cart.terms_required');
  }

  return errors;
};

watch(conditionAcquaintance, (accepted) => {
  if (accepted) {
    form.clearErrors('condition');
  }
});

const submit = () => {
  const clientErrors = findClientErrors();

  form.clearErrors();

  if (Object.keys(clientErrors).length > 0) {
    form.setError(clientErrors);

    return;
  }

  form
    .transform(data => ({
      ...data,
      resources: items.value.map(item => ({ id: item.resource_id, quantity: item.quantity })),
    }))
    .post(route('reservations.store'), { preserveScroll: true });
};
</script>
