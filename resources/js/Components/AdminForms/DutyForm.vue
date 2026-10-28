<template>
  <FormPage
    :title="dutyTitle"
    :bar-title
    :head-title="barTitle"
    :lead="isEditing ? (duty?.institution?.short_name ?? duty?.institution?.name) : $t('Sukurk naują pareigybę institucijoje')"
    :entity-type="ModelEnum.DUTY"
    :required-note="true"
    :activity-subject="duty?.id ? { type: 'duty', id: duty.id } : undefined"
    :created-at="duty?.created_at"
    :updated-at="duty?.updated_at"
    :back-href="backHref ?? (isEditing && duty?.id ? route('duties.show', duty.id) : route('duties.index'))"
    :back-label="$t('Pareigybės')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isEditing ? 'edit' : 'create'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    :missing-locale-counts
    @update:locale="activeLocale = $event"
    @submit="emit('submit:form', form)"
  >
    <template v-if="isEditing" #title-status>
      <StatusBadge :status="operatingStatuses[form.is_active ? 'active' : 'inactive']" />
    </template>

    <template #title>
      <InflectedDutyName :name="dutyTitle" :locale="activeLocale" />
    </template>

    <!-- Cross tenant duty alert -->
    <div
      v-if="!canEditDuty"
      class="border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] p-4 text-xs text-[var(--status-attention)]"
    >
      {{ $t('forms.fields.cross_tenant_duty_notice') }}
    </div>

    <template v-if="canEditDuty">
      <!-- Title input -->
      <FormFieldWrapper
        id="duty-name"
        :label="$t('Pavadinimas')"
        required
        :error="form.errors[`name.${activeLocale}`]"
        :hint="$t('forms.helpers.duty_name_inflected_hint')"
      >
        <Input
          id="duty-name"
          v-model="form.name[activeLocale]"
          :placeholder="activeLocale === 'lt' ? $t('Pirmininkas, Koordinatorius…') : 'Chair, Coordinator…'"
        />
      </FormFieldWrapper>

      <div v-if="showFormerNames" id="duty-former-names" class="-mt-2 space-y-1" data-testid="duty-former-names">
        <FieldHistoryDisclosure v-model:open="formerNamesOpen" :label="$t('Ankstesni pavadinimai')" :count="form.former_names.length">
          <li class="px-3 py-2 text-xs text-muted-foreground">
            {{ $t('Seni laikotarpiai rodomi su tuo metu galiojusiu pavadinimu.') }}
          </li>
          <li
            v-for="(former, index) in form.former_names"
            :key="index"
            class="grid gap-2 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_10rem_auto] sm:items-end"
            data-testid="former-name-row"
          >
            <div class="space-y-1">
              <Label :for="`former-name-lt-${index}`" class="flex items-center gap-2 text-xs text-muted-foreground">
                LT
                <span v-if="former.auto" class="border border-border bg-secondary px-1.5 text-xs font-medium text-foreground">{{ $t('Naujas') }}</span>
              </Label>
              <Input :id="`former-name-lt-${index}`" v-model="former.name.lt" :disabled="former.auto" />
            </div>
            <div class="space-y-1">
              <Label :for="`former-name-en-${index}`" class="text-xs text-muted-foreground">EN</Label>
              <Input :id="`former-name-en-${index}`" v-model="former.name.en" :disabled="former.auto" />
            </div>
            <div class="space-y-1">
              <Label class="text-xs text-muted-foreground">{{ $t('Naudotas iki') }}</Label>
              <DatePicker
                :model-value="former.used_until"
                :disabled="former.auto"
                @update:model-value="(value?: Date) => former.used_until = value ? formatDate(value) : ''"
              />
            </div>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              class="u-touch text-destructive"
              :aria-label="$t('Pašalinti')"
              data-testid="former-name-remove"
              @click="removeFormerName(index)"
            >
              <Trash2 class="size-3.5" />
            </Button>
          </li>
          <li class="p-2">
            <Button type="button" variant="ghost" size="sm" voice="sentence" class="u-touch" @click="addFormerName">
              <Plus class="size-3.5" />
              {{ $t('Pridėti ankstesnį pavadinimą') }}
            </Button>
          </li>
        </FieldHistoryDisclosure>
        <p v-if="formerNamesError" class="text-xs text-destructive" role="alert">
          {{ formerNamesError }}
        </p>
      </div>

      <!-- Duplicate Duty Warning -->
      <DuplicateDutyWarning
        :matches="duplicateMatches"
        :current-duty-id="duty?.id ?? null"
        class="mt-2"
      />

      <!-- Email -->
      <FormFieldWrapper
        id="duty-email"
        :label="$t('Pareigybės el. paštas')"
        :error="form.errors.email"
      >
        <Input
          id="duty-email"
          v-model="form.email"
          type="email"
          placeholder="vusa@vusa.lt"
        />
      </FormFieldWrapper>

      <!-- Institution -->
      <FormFieldWrapper
        id="institution_id"
        :label="$t('Institucija')"
        required
        :error="form.errors.institution_id"
      >
        <InstitutionSelectDialog
          v-model:open="institutionDialogOpen"
          :institutions="assignableInstitutions"
          :initial-hits="institutionInitialHits"
          @confirm="onInstitutionConfirm"
        >
          <template #trigger>
            <Button
              id="institution_id"
              type="button"
              variant="outline"
              class="u-touch w-full justify-between font-normal"
            >
              <span class="truncate" :class="{ 'text-muted-foreground': !selectedInstitution }">
                {{ selectedInstitution?.name ?? $t('Pasirinkti instituciją…') }}
              </span>
              <span class="flex shrink-0 items-center gap-2">
                <InactiveInstitutionBadge :active="selectedInstitution?.is_active" />
                <span
                  v-if="selectedInstitution?.tenant?.shortname"
                  class="border border-border bg-secondary px-1.5 py-0.5 text-xs font-medium text-muted-foreground"
                >
                  {{ selectedInstitution.tenant.shortname }}
                </span>
                <ChevronsUpDown class="size-4 opacity-50" />
              </span>
            </Button>
          </template>
        </InstitutionSelectDialog>
      </FormFieldWrapper>

      <!-- Description -->
      <FormFieldWrapper
        id="duty-description"
        :label="$t('Aprašymas')"
        :hint="$t('Aprašymas rodomas viešame puslapyje prie pareigybės.')"
        :error="form.errors[`description.${activeLocale}`]"
      >
        <TiptapEditor :key="activeLocale" v-model="form.description[activeLocale]" tools="description" html />
      </FormFieldWrapper>

    </template>

    <FormSection
      v-if="showAssignableTenants"
      :title="$t('forms.fields.assignable_tenants')"
      :description="$t('Leisti kitiems padaliniams skirti atstovus į šią pareigybę.')"
      data-testid="assignable-tenants-section"
    >
      <div class="space-y-3">
        <MultiSelect
          v-if="canEditDuty"
          id="assignable-tenants"
          v-model="selectedAssignableTenants"
          :options="assignableTenants"
          label-field="shortname"
          value-field="id"
          :placeholder="$t('forms.placeholders.select_tenants')"
        />
        <div v-if="visibleAssignableTenantRows.length > 0" class="divide-y divide-border border border-border">
          <div
            v-for="row in visibleAssignableTenantRows"
            :key="row.tenant_id"
            class="flex items-center justify-between p-3"
          >
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium text-foreground">{{ tenantShortname(row) }}</span>
              <span
                class="border border-border bg-secondary px-1.5 py-0.5 text-xs font-semibold text-muted-foreground"
                :data-testid="`tenant-occupancy-${row.tenant_id}`"
              >
                {{ tenantOccupancy(row) }} / {{ row.quota ?? '∞' }}
              </span>
            </div>
            <div v-if="canEditDuty" class="flex items-center gap-2">
              <div class="flex items-center gap-1.5">
                <Label :for="`quota-${row.tenant_id}`" class="text-xs text-muted-foreground">{{ $t('Kvota') }}:</Label>
                <Input
                  :id="`quota-${row.tenant_id}`"
                  v-model.number="row.quota"
                  type="number"
                  min="1"
                  class="h-7 w-16 text-center text-xs"
                />
              </div>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                class="u-touch text-destructive"
                @click="removeTenantRow(row.tenant_id)"
              >
                <Trash2 class="size-3.5" />
              </Button>
            </div>
          </div>
        </div>
        <Button
          v-if="canEditDuty"
          type="button"
          variant="ghost"
          size="sm"
          voice="sentence"
          class="u-touch text-muted-foreground"
          data-testid="assignable-tenants-clear"
          @click="clearAssignableTenants"
        >
          {{ $t('Nebeleisti kitiems padaliniams skirti atstovų') }}
        </Button>
      </div>
    </FormSection>

    <SpotlightPopover
      v-if="fillActions.length"
      :title="$t('Retesni laukai – formos apačioje')"
      :description="$t('Ankstesnius pavadinimus ir kitų padalinių atstovus pridėsi šiais mygtukais, kai jų prireiks.')"
      :is-dismissed="fillSpotlight.isDismissed.value"
      @dismiss="fillSpotlight.dismiss()"
    >
      <FormFillActions :actions="fillActions" />
    </SpotlightPopover>

    <!-- Aside Slot -->
    <template #aside>
      <FormPanel :title="$t('Nustatymai')" :icon="Building2">
        <ActivityStatusField
          v-if="isEditing && canEditDuty"
          id="duty-active"
          v-model="form.is_active"
          :error="form.errors.is_active"
          :hint="$t('Nebeveikianti pareigybė lieka su istorija, bet nebesiūloma priskiriant narius.')"
          data-testid="toggle-duty-active"
        />

        <!-- Contacts grouping -->
        <FormFieldWrapper
          id="contacts_grouping"
          :label="$t('Kontaktų rikiavimas')"
          :error="form.errors.contacts_grouping"
        >
          <FormSegmentedControl
            v-model="form.contacts_grouping"
            :options="contactsGroupingOptions"
            :aria-label="$t('Kontaktų rikiavimas')"
            test-id-prefix="contacts-grouping"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          v-if="dutyTypes && dutyTypes.length > 0"
          id="duty-types"
          :label="$t('forms.fields.duty_type')"
          :error="form.errors.types"
        >
          <TypeTreeMultiSelect
            id="duty-types"
            v-model="selectedTypeIds"
            :types="dutyTypes"
            :placeholder="$t('forms.placeholders.select_category')"
          />
        </FormFieldWrapper>
        <div v-if="readonlyTypes.length" class="space-y-2 text-sm" data-testid="duty-readonly-types">
          <p class="text-xs font-semibold text-muted-foreground uppercase">
            {{ $t('forms.fields.duty_type') }}
          </p>
          <p v-for="type in readonlyTypes" :key="type.id">
            {{ type.title }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ $t('types.assignment_readonly') }}
          </p>
        </div>
      </FormPanel>

      <FormPanel
        v-if="canEditDuty || $page.props.auth?.user?.isSuperAdmin || (assignableDuties && assignableDuties.length > 0)"
        :title="$t('Papildomi nustatymai')"
        :icon="SlidersHorizontal"
      >
        <!-- Ex-officio Target Duties -->
        <FormFieldWrapper
          v-if="assignableDuties && assignableDuties.length > 0"
          id="ex_officio_duties"
          :label="$t('forms.fields.ex_officio_duties')"
        >
          <CollectionSelectDialog
            v-model:open="exOfficioDialogOpen"
            collection="duties"
            multiple
            allow-empty
            :base-filter-by="exOfficioBaseFilterBy"
            :disabled-ids="exOfficioDisabledIds"
            :initial-hits="exOfficioInitialHits"
            :title="$t('forms.fields.ex_officio_duties')"
            :confirm-label="$t('Pasirinkti')"
            :search-placeholder="$t('Ieškoti pareigų pagal pavadinimą…')"
            @confirm="onExOfficioConfirm"
          >
            <template #trigger>
              <Button
                id="ex_officio_duties"
                type="button"
                variant="outline"
                class="u-touch w-full justify-between font-normal"
              >
                <span class="truncate" :class="{ 'text-muted-foreground': selectedExOfficioDuties.length === 0 }">
                  {{ selectedExOfficioDuties.length > 0
                    ? selectedExOfficioDuties.map(d => d.name).join(', ')
                    : $t('forms.fields.ex_officio_duties') }}
                </span>
                <span class="flex shrink-0 items-center gap-2">
                  <span
                    v-if="selectedExOfficioDuties.length > 0"
                    class="border border-border bg-secondary px-1.5 py-0.5 text-xs font-semibold text-muted-foreground"
                  >
                    {{ selectedExOfficioDuties.length }}
                  </span>
                  <ChevronsUpDown class="size-4 opacity-50" />
                </span>
              </Button>
            </template>
          </CollectionSelectDialog>
        </FormFieldWrapper>

        <div v-if="canEditDuty" ref="seatCountControls">
          <FormFieldWrapper
            v-if="seatCountExpanded || form.places_to_occupy != null || form.errors.places_to_occupy"
            id="places_to_occupy"
            :label="$t('Vietų skaičius')"
            :hint="$t('Informacinis skaičius, pvz., Parlamento vietos. Narių priskyrimo neriboja.')"
            :error="form.errors.places_to_occupy"
          >
            <NumberField
              id="places_to_occupy"
              v-model="form.places_to_occupy"
              :min="1"
              @update:model-value="seatCountSpotlight.dismiss()"
            />
            <Button
              type="button"
              variant="link"
              voice="sentence"
              class="justify-start px-0 text-xs text-muted-foreground"
              data-testid="duty-seats-clear"
              @click="clearSeatCount"
            >
              {{ $t('Išvalyti') }}
            </Button>
          </FormFieldWrapper>
          <SpotlightPopover
            v-else
            :title="$t('forms.fields.duty_seats_spotlight_title')"
            :description="$t('forms.fields.duty_seats_spotlight_body')"
            :is-dismissed="seatCountSpotlight.isDismissed.value"
            @dismiss="seatCountSpotlight.dismiss()"
          >
            <Button
              type="button"
              variant="outline"
              voice="sentence"
              data-testid="duty-seats-add"
              @click="addSeatCount"
            >
              <Plus class="size-4" aria-hidden="true" />
              {{ $t('forms.fields.add_duty_seats') }}
            </Button>
          </SpotlightPopover>
        </div>
      </FormPanel>
    </template>

    <!-- Danger Zone Slot -->
    <template v-if="isEditing && canEditDuty" #danger-zone>
      <Button
        type="button"
        variant="outline"
        class="border-destructive/40 text-destructive hover:border-destructive hover:bg-destructive hover:text-destructive-foreground pointer-coarse:min-h-11"
        @click="deleteConfirmOpen = true"
      >
        <Trash2 class="size-4" />
        {{ $t('Ištrinti pareigybę') }}
      </Button>

      <ConfirmDialog
        v-model:open="deleteConfirmOpen"
        :title="$t('Ištrinti pareigybę?')"
        :description="$t('Pareigybė bus visiškai pašalinta iš sistemos.')"
        :confirm-label="$t('Ištrinti')"
        destructive
        @confirm="emit('delete')"
      />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, ChevronsUpDown, History, Plus, SlidersHorizontal, Trash2, Users } from '@lucide/vue';

import ActivityStatusField from './ActivityStatusField.vue';

import { operatingStatuses } from '@/Constants/statuses';
import StatusBadge from '@/Components/Patterns/StatusBadge.vue';
import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FieldHistoryDisclosure, FormFillActions, FormPanel, FormSection, type FormFillAction } from '@/Components/Patterns';
import { DatePicker } from '@/Components/ui/date-picker';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { NumberField } from '@/Components/ui/number-field';
import FormSegmentedControl, { type FormSegmentOption } from '@/Components/Patterns/FormSegmentedControl.vue';
import { MultiSelect } from '@/Components/ui/multi-select';
import { TypeTreeMultiSelect } from '@/Components/AdminForms/TypeTree';
import InstitutionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/InstitutionSelectDialog.vue';
import CollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import DuplicateDutyWarning from '@/Components/AdminForms/DuplicateDutyWarning.vue';
import { useDuplicateDutyCheck } from '@/Composables/useDuplicateDutyCheck';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { ModelEnum } from '@/Types/enums';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { formatDate, todayIso } from '@/Utils/dateTime';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

interface AssignableTenantRow {
  tenant_id: number;
  quota: number | null;
}

interface FormerNameRow {
  name: { lt: string; en: string };
  used_until: string;
  /** Added because the name was just changed; follows the rename rather than being edited. */
  auto?: boolean;
}

interface DutyPropType {
  id?: string;
  name?: { lt?: string; en?: string } | string;
  description?: { lt?: string; en?: string } | string;
  email?: string | null;
  institution_id?: string | null;
  places_to_occupy?: number | null;
  contacts_grouping?: string;
  is_active?: boolean;
  former_names?: Array<{ name: { lt?: string; en?: string }; used_until: string }>;
  types?: Array<{ id: number; title?: string }>;
  ex_officio_target_duties?: Array<{ id: string; name?: string }>;
  assignable_tenants?: Array<{ id: number; shortname?: string; pivot?: { quota?: number | null } }>;
  created_at?: string | null;
  updated_at?: string | null;
  institution?: { id?: string; name?: string; short_name?: string } | null;
}

const props = withDefaults(defineProps<{
  duty?: DutyPropType;
  dutyTypes?: App.Entities.DutyType[];
  assignableInstitutions?: App.Entities.Institution[];
  assignableTenants?: { id: number; shortname: string; type?: string }[];
  assignableDuties?: Array<{ id: string; name: string; institution?: App.Entities.Institution | Record<string, unknown> }>;
  assignableTenantUsers?: Record<number, string[]>;
  exOfficioMembers?: Array<{
    dutiable_id: string;
    user_id: string;
    name: string;
    profile_photo_path?: string | null;
    tenant_id: number | null;
    source_duty_name?: string | null;
  }>;
  actingAssignableTenantIds?: number[];
  canEditDuty?: boolean;
  rememberKey?: string;
  backHref?: string;
}>(), {
  duty: () => ({
    name: { lt: '', en: '' },
    description: { lt: '', en: '' },
    email: null,
    institution_id: null,
    places_to_occupy: null,
    contacts_grouping: 'none',
    types: [],
    ex_officio_target_duties: [],
    assignable_tenants: [],
  }),
  dutyTypes: () => [],
  assignableInstitutions: () => [],
  assignableTenants: () => [],
  assignableDuties: () => [],
  assignableTenantUsers: () => ({}),
  exOfficioMembers: () => [],
  actingAssignableTenantIds: () => [],
  // eslint-disable-next-line vue/no-boolean-default
  canEditDuty: true,
  rememberKey: undefined,
  backHref: undefined,
});

const emit = defineEmits<{
  (e: 'submit:form', form: ReturnType<typeof useForm>): void;
  (e: 'delete'): void;
}>();

const isEditing = computed(() => !!props.duty?.id);

const activeLocale = ref<'lt' | 'en'>('lt');
const deleteConfirmOpen = ref(false);

// Error keys that are not the id of the field they belong to.
const fieldIds = {
  'name.lt': 'duty-name',
  'name.en': 'duty-name',
  'description.lt': 'duty-description',
  'description.en': 'duty-description',
  'email': 'duty-email',
  'institution_id': 'institution_id',
  'places_to_occupy': 'places_to_occupy',
  'contacts_grouping': 'contacts_grouping',
  'types': 'duty-types',
  'former_names': 'duty-former-names',
};

// Form Initialization
const form = useForm({
  name: {
    lt: (typeof props.duty?.name === 'object' ? props.duty?.name?.lt : props.duty?.name) ?? '',
    en: (typeof props.duty?.name === 'object' ? props.duty?.name?.en : '') ?? '',
  },
  email: props.duty?.email ?? '',
  institution_id: props.duty?.institution_id ?? null,
  places_to_occupy: props.duty?.places_to_occupy ?? null,
  contacts_grouping: props.duty?.contacts_grouping ?? 'none',
  is_active: props.duty?.is_active ?? true,
  former_names: (props.duty?.former_names ?? []).map(former => ({
    name: { lt: former.name?.lt ?? '', en: former.name?.en ?? '' },
    used_until: String(former.used_until).slice(0, 10),
  })) as FormerNameRow[],
  description: {
    lt: (typeof props.duty?.description === 'object' ? props.duty?.description?.lt : props.duty?.description) ?? '',
    en: (typeof props.duty?.description === 'object' ? props.duty?.description?.en : '') ?? '',
  },
  types: (props.duty?.types?.map((t: { id: number }) => t.id) ?? []) as number[],
  ex_officio_target_duty_ids: (props.duty?.ex_officio_target_duties?.map((d: { id: string }) => d.id) ?? []) as string[],
  assignable_tenants: (props.duty?.assignable_tenants?.map((t: { id: number; pivot?: { quota?: number | null } }) => ({
    tenant_id: t.id,
    quota: t.pivot?.quota ?? null,
  })) ?? []) as AssignableTenantRow[],
});

const seatCountExpanded = ref(form.places_to_occupy != null);
const seatCountControls = ref<HTMLElement | null>(null);
const seatCountSpotlight = useFeatureSpotlight('duty-seat-count-v1');

async function addSeatCount() {
  seatCountExpanded.value = true;
  form.places_to_occupy = 1;
  void seatCountSpotlight.dismiss();
  await nextTick();
  seatCountControls.value?.querySelector<HTMLInputElement>('input')?.focus();
}

async function clearSeatCount() {
  form.places_to_occupy = null;
  form.clearErrors('places_to_occupy');
  seatCountExpanded.value = false;
  void seatCountSpotlight.dismiss();
  await nextTick();
  seatCountControls.value?.querySelector<HTMLButtonElement>('[data-testid="duty-seats-add"]')?.focus();
}

const barTitle = computed(() => isEditing.value
  ? getTranslatedValue(props.duty.name, undefined, $t('Pareigybė'))
  : $t('Nauja pareigybė'));

const dutyTitle = computed(() => form.name[activeLocale.value].trim()
  || (isEditing.value ? $t('Pareigybė') : $t('Nauja pareigybė')));

// Missing translation counts
const missingLocaleCounts = computed(() => ({
  lt: !form.name.lt ? 1 : 0,
  en: !form.name.en ? 1 : 0,
}));

// Renaming keeps the old name for the terms that ran under it; removing that row opts out.
const originalName = {
  lt: (typeof props.duty?.name === 'object' ? props.duty?.name?.lt : props.duty?.name) ?? '',
  en: (typeof props.duty?.name === 'object' ? props.duty?.name?.en : '') ?? '',
};
const keepFormerName = ref(true);
const renamedFrom = computed(() => (isEditing.value && originalName.lt.trim() !== '' && form.name.lt.trim() !== originalName.lt.trim()
  ? originalName.lt
  : null));

const yesterdayIso = () => {
  const date = new Date(`${todayIso()}T12:00:00Z`);
  date.setUTCDate(date.getUTCDate() - 1);

  return formatDate(date);
};

const formerNamesOpen = ref(false);
const formerNamesRevealed = ref(false);
const showFormerNames = computed(() => isEditing.value && props.canEditDuty && (form.former_names.length > 0 || formerNamesRevealed.value));
const formerNamesError = computed(() => Object.entries(form.errors as Record<string, string>)
  .find(([key]) => key.startsWith('former_names'))?.[1]);

watch(renamedFrom, () => {
  keepFormerName.value = true;
});

watch([renamedFrom, keepFormerName], ([from, keep]) => {
  const manual = form.former_names.filter(former => !former.auto);

  form.former_names = from && keep
    ? [{ name: { ...originalName }, used_until: yesterdayIso(), auto: true }, ...manual]
    : manual;
  if (from && keep) {
    formerNamesOpen.value = true;
  }
});

const addFormerName = async () => {
  formerNamesRevealed.value = true;
  formerNamesOpen.value = true;
  form.former_names.push({ name: { lt: '', en: '' }, used_until: yesterdayIso() });
  await nextTick();
  document.getElementById(`former-name-lt-${form.former_names.length - 1}`)?.focus();
};

const removeFormerName = (index: number) => {
  if (form.former_names[index]?.auto) {
    keepFormerName.value = false;
    return;
  }
  form.former_names.splice(index, 1);
};

// Duplicate duty check composable
const { matches: duplicateMatches } = useDuplicateDutyCheck(
  () => form.name.lt,
  () => form.institution_id,
  () => props.duty?.id ?? null,
);

// Institution selection
const institutionDialogOpen = ref(false);
const selectedInstitution = computed(() => {
  if (!form.institution_id) return null;
  return props.assignableInstitutions?.find(i => String(i.id) === String(form.institution_id)) ?? null;
});

const institutionInitialHits = computed<NormalizedSearchHit[]>(() => {
  if (!selectedInstitution.value) return [];
  const inst = selectedInstitution.value;
  return [normalizeHit('institutions', {
    id: String(inst.id),
    name_lt: inst.name,
    tenant_shortname: inst.tenant?.shortname,
    is_active: inst.is_active === undefined ? true : Boolean(inst.is_active),
  })];
});

// Hits carry a collection-prefixed `id`; the record's own id is `recordId`.
const onInstitutionConfirm = (hits: NormalizedSearchHit[]) => {
  if (hits.length > 0) {
    form.institution_id = hits[0].recordId;
  }
};

// Holders only carry a unit when other units delegate them; a saved choice stays visible until changed.
const contactsGroupingOptions = computed<FormSegmentOption<string>[]>(() => [
  { value: 'none', label: $t('Numatytasis') },
  { value: 'study_program', label: $t('Pagal studijų programą') },
  ...(form.assignable_tenants.length > 0 || form.contacts_grouping === 'tenant'
    ? [{ value: 'tenant', label: $t('Pagal padalinį') }]
    : []),
]);

const readonlyTypes = computed(() => (props.duty?.types ?? []).filter(type =>
  !(props.dutyTypes ?? []).some(option => Number(option.id) === Number(type.id)),
));

const selectedTypeIds = computed({
  get: () => (form.types ?? []).map(Number),
  set: (ids: Array<number | string>) => {
    form.types = [...new Set([
      ...readonlyTypes.value.map(type => Number(type.id)),
      ...ids.map(Number),
    ])];
  },
});

// Ex-officio Target Duties
const exOfficioDialogOpen = ref(false);
const exOfficioBaseFilterBy = computed(() => {
  if (!props.duty?.id) return '';
  return `id:!=${props.duty.id}`;
});
const exOfficioDisabledIds = computed(() => new Set(props.duty?.id ? [`duties-${props.duty.id}`] : []));

const selectedExOfficioDuties = computed(() => {
  const ids = new Set(form.ex_officio_target_duty_ids.map(String));
  return (props.assignableDuties ?? []).filter(d => ids.has(String(d.id)));
});

const exOfficioInitialHits = computed<NormalizedSearchHit[]>(() =>
  selectedExOfficioDuties.value.map(d => normalizeHit('duties', {
    id: String(d.id),
    name_lt: d.name,
    institution_name_lt: d.institution?.name,
    institution_is_active: d.institution?.is_active === undefined ? undefined : Boolean(d.institution.is_active),
  })),
);

const onExOfficioConfirm = (hits: NormalizedSearchHit[]) => {
  form.ex_officio_target_duty_ids = hits.map(h => h.recordId);
};

// Assignable Tenants (Delegated Seats): rare, so hidden until offered; stays open until save once revealed.
const assignableTenantsRevealed = ref(false);
const showAssignableTenants = computed(() => (props.assignableTenants?.length ?? 0) > 0 && (props.canEditDuty
  ? form.assignable_tenants.length > 0 || assignableTenantsRevealed.value
  : visibleAssignableTenantRows.value.length > 0));

const revealAssignableTenants = async () => {
  assignableTenantsRevealed.value = true;
  await nextTick();
  document.getElementById('assignable-tenants')?.focus();
};

const clearAssignableTenants = () => {
  form.assignable_tenants = [];
  assignableTenantsRevealed.value = false;
  if (form.contacts_grouping === 'tenant') {
    form.contacts_grouping = 'none';
  }
};

const selectedAssignableTenants = computed({
  get: () => {
    const ids = new Set(form.assignable_tenants.map(r => r.tenant_id));
    return (props.assignableTenants ?? []).filter(t => ids.has(t.id));
  },
  set: (tenants: Array<{ id: number; shortname: string }>) => {
    const existingMap = new Map(form.assignable_tenants.map(r => [r.tenant_id, r.quota]));
    form.assignable_tenants = tenants.map(t => ({
      tenant_id: t.id,
      quota: existingMap.get(t.id) ?? null,
    }));
  },
});

const visibleAssignableTenantRows = computed(() => {
  if (!props.canEditDuty) {
    const actingIds = new Set(props.actingAssignableTenantIds ?? []);
    return form.assignable_tenants.filter(r => actingIds.has(r.tenant_id));
  }
  return form.assignable_tenants;
});

const tenantShortname = (row: AssignableTenantRow) => {
  return props.assignableTenants?.find(t => t.id === row.tenant_id)?.shortname ?? String(row.tenant_id);
};

const tenantOccupancy = (row: AssignableTenantRow) => {
  const pickedCount = (props.assignableTenantUsers?.[row.tenant_id] ?? []).length;
  const exOfficioCount = (props.exOfficioMembers ?? []).filter(m => m.tenant_id === row.tenant_id).length;
  return pickedCount + exOfficioCount;
};

const fillSpotlight = useFeatureSpotlight('duty-form-fill-actions-v1');

const fillActions = computed<FormFillAction[]>(() => {
  if (!props.canEditDuty) {
    return [];
  }

  const actions: FormFillAction[] = [];
  if (isEditing.value && !showFormerNames.value) {
    actions.push({ key: 'former-names', label: $t('Pridėti ankstesnį pavadinimą'), icon: History, run: () => {
      void fillSpotlight.dismiss();
      void addFormerName();
    } });
  }
  if ((props.assignableTenants?.length ?? 0) > 0 && !showAssignableTenants.value) {
    actions.push({ key: 'assignable-tenants', label: $t('Leisti kitiems padaliniams skirti atstovus'), icon: Users, run: () => {
      void fillSpotlight.dismiss();
      void revealAssignableTenants();
    } });
  }

  return actions;
});

const removeTenantRow = (tenantId: number) => {
  form.assignable_tenants = form.assignable_tenants.filter(r => r.tenant_id !== tenantId);
};
</script>
