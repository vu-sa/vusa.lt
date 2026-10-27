<template>
  <div>
    <SheetForm
      :created-at="dutiable?.created_at"
      :updated-at="dutiable?.updated_at"
      :open
      :title="sheetTitle"
      :description="sheetDescription"
      :save-label
      :processing="form.processing"
      :dirty="form.isDirty"
      @update:open="emit('update:open', $event)"
      @submit="submit"
    >
      <div v-if="dutyContext" class="flex items-start gap-3 border border-border bg-secondary/50 p-3">
        <div class="flex size-10 shrink-0 items-center justify-center border border-border bg-card text-muted-foreground">
          <Briefcase class="size-5" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
            {{ $t('Pareigybė') }}
          </p>
          <p class="truncate text-sm font-semibold text-foreground">
            <InflectedDutyName :name="dutyContext.name" />
          </p>
          <p v-if="dutyContext.institution?.name" class="truncate text-xs text-muted-foreground">
            {{ dutyContext.institution.name }}
          </p>
        </div>
      </div>

      <DutySearchField
        v-if="pickDuty"
        v-model="chosenDuty"
        :error="form.errors.duty_id"
      />

      <MemberSearchField
        v-if="pickMember"
        v-model="member"
        :taken-ids="takenInRange"
        :allow-create="canCreateMember"
        :error="form.errors.user_id || form.errors['new_user.name'] || form.errors['new_user.email']"
      />
      <div
        v-else-if="member"
        class="flex items-center gap-3 border border-border bg-secondary/50 p-3"
      >
        <UserAvatar :user="member" :size="40" class="shrink-0" />
        <div class="min-w-0 flex-1">
          <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
            {{ $t('Narys') }}
          </p>
          <p class="truncate text-sm font-semibold text-foreground">
            {{ member.name }}
          </p>
          <p v-if="member.email" class="truncate text-xs text-muted-foreground">
            {{ member.email }}
          </p>
        </div>
      </div>

      <div
        v-if="isExOfficio"
        class="flex items-start gap-3 border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] p-3 text-[var(--status-attention)]"
      >
        <Sparkles class="mt-0.5 size-4 shrink-0" />
        <div class="text-xs">
          <p class="font-semibold">
            {{ $t('Pareigos pagal pareigas (ex-officio)') }}
          </p>
          <p class="mt-0.5">
            {{ $t('Šio laikotarpio datas valdo pagrindinės pareigos.') }}
          </p>
        </div>
      </div>

      <div v-if="cadenceOptions.length > 0 && !isExOfficio" class="space-y-1.5" data-testid="assign-cadence">
        <Label for="assign-cadence" class="text-sm font-medium">
          {{ $t('Kadencija') }}
        </Label>
        <NativeSelect
          id="assign-cadence"
          v-model="selectedCadence"
          :options="cadenceOptions"
        />
        <p class="text-xs text-muted-foreground">
          {{ cadenceHint }}
        </p>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5" role="group" aria-labelledby="assign-start-label">
          <Label id="assign-start-label" class="text-sm font-medium">
            {{ $t('Pradžios data') }}
          </Label>
          <DatePicker v-model="startDate" :disabled="isExOfficio" />
          <p v-if="form.errors.start_date" class="text-xs text-destructive">
            {{ form.errors.start_date }}
          </p>
        </div>

        <div class="space-y-1.5" role="group" aria-labelledby="assign-end-label">
          <div class="flex items-center justify-between">
            <Label id="assign-end-label" class="text-sm font-medium">
              {{ $t('Pabaigos data') }}
            </Label>
            <span class="text-xs text-muted-foreground">{{ $t('(neprivaloma)') }}</span>
          </div>
          <DatePicker v-model="endDate" :disabled="isExOfficio" clearable />
          <p v-if="form.errors.end_date" class="text-xs text-destructive">
            {{ form.errors.end_date }}
          </p>
        </div>
      </div>

      <div v-if="showQuotaTenant" class="space-y-1.5" data-testid="assign-quota-tenant">
        <Label for="assign-quota-tenant" class="text-sm font-medium">
          {{ $t('Kieno kvotai skaičiuojama') }}
        </Label>
        <NativeSelect
          id="assign-quota-tenant"
          v-model="form.tenant_id"
          :options="quotaTenantOptions"
          :placeholder="$t('Pareigybės padalinio vieta (ne kvota)')"
          :placeholder-value="null"
          placeholder-selectable
          :disabled="isExOfficio"
          :error="!!form.errors.tenant_id"
        />
        <p class="text-xs text-muted-foreground">
          {{ $t('Deleguota vieta skaičiuojama pasirinkto padalinio kvotai.') }}
        </p>
        <p v-if="form.errors.tenant_id" class="text-xs text-destructive">
          {{ form.errors.tenant_id }}
        </p>
      </div>

      <details :open="extrasOpen" class="group border-t border-border pt-4" data-testid="assign-extras" @toggle="onExtrasToggle">
        <summary class="u-touch flex cursor-pointer select-none items-center justify-between text-sm font-semibold text-foreground">
          <span>{{ $t('Papildomi nustatymai') }}</span>
          <ChevronDown class="size-4 text-muted-foreground transition-transform group-open:rotate-180" />
        </summary>
        <div v-if="extrasOpen" class="mt-4 space-y-4">
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <Label for="additional_email" class="text-sm font-medium">
                {{ $t('Papildomas el. paštas') }}
              </Label>
              <span class="text-xs text-muted-foreground">{{ $t('(neprivaloma)') }}</span>
            </div>
            <Input
              id="additional_email"
              v-model="form.additional_email"
              type="email"
              placeholder="vardas.pavarde@vusa.lt"
            />
            <p class="text-xs text-muted-foreground">
              {{ $t('Rodomas prie šio nario kontaktų.') }}
            </p>
            <p v-if="form.errors.additional_email" class="text-xs text-destructive">
              {{ form.errors.additional_email }}
            </p>
          </div>

          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <Label class="text-sm font-medium">
                {{ $t('Papildoma nuotrauka') }}
              </Label>
              <span class="text-xs text-muted-foreground">{{ $t('(neprivaloma)') }}</span>
            </div>
            <p class="text-xs text-muted-foreground">
              {{ $t('Matoma vusa.lt') }} · {{ $t('Rodoma vietoj nario profilio nuotraukos.') }}
            </p>
            <ImageUpload
              v-model:image="form.photo_media"
              mode="media"
              cropper
              focal-point
              preview-aspect="4/3"
            />
            <p v-if="form.errors['photo_media.id']" class="text-xs text-destructive">
              {{ form.errors['photo_media.id'] }}
            </p>
          </div>

          <div v-if="showStudyProgram" class="space-y-1.5" data-testid="assign-study-program">
            <div class="flex items-center justify-between">
              <Label for="study_program_id" class="text-sm font-medium">
                {{ $t('Studijų programa') }}
              </Label>
              <span class="text-xs text-muted-foreground">{{ $t('(neprivaloma)') }}</span>
            </div>
            <Select v-model="studyProgramValue">
              <SelectTrigger id="study_program_id">
                <SelectValue :placeholder="$t('Pasirink studijų programą…')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="NO_STUDY_PROGRAM">
                  {{ $t('Nenurodyta') }}
                </SelectItem>
                <SelectItem v-for="program in availableStudyPrograms" :key="program.id" :value="String(program.id)">
                  {{ program.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.study_program_id" class="text-xs text-destructive">
              {{ form.errors.study_program_id }}
            </p>
          </div>

          <div v-if="showStudyProgram" class="space-y-1.5">
            <div class="flex items-center justify-between gap-2">
              <Label for="study_program_note" class="text-sm font-medium">
                {{ $t('Studijų programos pastaba') }}
              </Label>
              <div class="inline-flex border border-border bg-secondary p-0.5" role="group" :aria-label="$t('Kalba')">
                <button
                  v-for="loc in LOCALES"
                  :key="loc"
                  type="button"
                  :class="[
                    'u-touch px-2 py-0.5 text-xs font-semibold uppercase tracking-wider transition-colors',
                    descriptionLocale === loc ? 'bg-card text-foreground' : 'text-muted-foreground hover:text-foreground',
                  ]"
                  :aria-pressed="descriptionLocale === loc"
                  @click="descriptionLocale = loc"
                >
                  {{ loc }}
                </button>
              </div>
            </div>
            <Input
              id="study_program_note"
              v-model="form.study_program_note[descriptionLocale]"
              maxlength="100"
              :placeholder="$t('Pvz., 1 kursas')"
            />
            <p class="text-xs text-muted-foreground">
              {{ $t('Matoma vusa.lt') }} · {{ $t('Rodoma skliaustuose po pareigybės pavadinimo.') }}
            </p>
            <p v-if="form.errors['study_program_note.lt'] || form.errors['study_program_note.en']" class="text-xs text-destructive">
              {{ form.errors['study_program_note.lt'] || form.errors['study_program_note.en'] }}
            </p>
          </div>

          <div class="flex items-start gap-2.5">
            <Checkbox
              id="use_original_duty_name"
              class="mt-0.5"
              :model-value="form.use_original_duty_name"
              @update:model-value="form.use_original_duty_name = $event === true"
            />
            <div class="space-y-0.5">
              <Label for="use_original_duty_name" class="cursor-pointer text-sm font-normal">
                {{ $t('Viešame puslapyje naudoti originalų pareigybės pavadinimą') }}
              </Label>
              <p class="text-xs text-muted-foreground">
                {{ $t('Matoma vusa.lt') }}
              </p>
            </div>
          </div>

          <div class="space-y-3 border-t border-border pt-4">
            <p class="text-sm font-medium text-foreground">
              {{ $t('Papildomas aprašymas') }}
            </p>
            <div class="space-y-3">
              <p class="text-xs text-muted-foreground">
                {{ $t('Matoma vusa.lt') }} · {{ $t('Rodomas viešame kontaktų sąraše vietoj pareigybės aprašymo.') }}
              </p>
              <div class="inline-flex border border-border bg-secondary p-0.5" role="group" :aria-label="$t('Kalba')">
                <button
                  v-for="loc in LOCALES"
                  :key="loc"
                  type="button"
                  :class="[
                    'u-touch px-2.5 py-1 text-xs font-semibold uppercase tracking-wider transition-colors',
                    descriptionLocale === loc ? 'bg-card text-foreground' : 'text-muted-foreground hover:text-foreground',
                  ]"
                  :aria-pressed="descriptionLocale === loc"
                  @click="descriptionLocale = loc"
                >
                  {{ loc }}
                </button>
              </div>
              <TiptapEditor
                :key="descriptionLocale"
                v-model="form.description[descriptionLocale]"
                tools="description"
                html
              />
              <p v-if="form.errors.description" class="text-xs text-destructive">
                {{ form.errors.description }}
              </p>
            </div>
          </div>
        </div>
      </details>

      <template v-if="isEditing && !isExOfficio" #danger-zone>
        <div class="space-y-4">
          <div v-if="canEndTerm" class="flex items-start justify-between gap-3">
            <div>
              <p class="text-sm font-semibold text-foreground">
                {{ $t('Užbaigti pareigas šiandien') }}
              </p>
              <p class="text-xs text-muted-foreground">
                {{ $t('dutiables.assign.last_day_notice') }}
              </p>
            </div>
            <Button type="button" variant="outline" size="sm" class="u-touch shrink-0" :disabled="form.processing" @click="endTermOpen = true">
              <CalendarCheck class="size-4" />
              {{ $t('Užbaigti pareigas') }}
            </Button>
          </div>

          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-sm font-semibold text-destructive">
                {{ $t('Ištrinti priskyrimą') }}
              </p>
              <p class="text-xs text-muted-foreground">
                {{ $t('Pareigybės laikotarpis bus pašalintas. Jei narys tik baigė eiti pareigas, verčiau jas užbaik.') }}
              </p>
            </div>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              class="u-touch shrink-0 text-destructive hover:bg-destructive/10 hover:text-destructive dark:hover:bg-destructive/10"
              :disabled="form.processing"
              @click="deleteOpen = true"
            >
              <Trash2 class="size-4" />
              {{ $t('Ištrinti') }}
            </Button>
          </div>
        </div>
      </template>
    </SheetForm>

    <ConfirmDialog
      v-model:open="endTermOpen"
      :title="$t('Užbaigti pareigas šiandien?')"
      :description="$t('dutiables.assign.last_day_notice')"
      :confirm-label="$t('Užbaigti pareigas')"
      @confirm="endTerm"
    />
    <ConfirmDialog
      v-model:open="deleteOpen"
      :title="$t('Ištrinti priskyrimą?')"
      :description="$t('Pareigybės laikotarpis bus pašalintas.')"
      :confirm-label="$t('Ištrinti')"
      destructive
      @confirm="deleteOccupancy"
    />

    <AccessChangeWarningDialog
      :open="accessWarningOpen"
      :report="accessWarningReport"
      @update:open="accessWarningOpen = $event"
      @confirm="accessWarningConfirm"
      @cancel="accessWarningCancel"
    />
  </div>
</template>

<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Briefcase, CalendarCheck, ChevronDown, Sparkles, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

import DutySearchField, { type DutyHit } from './DutySearchField.vue';
import MemberSearchField, { type MemberHit } from './MemberSearchField.vue';
import { isCuratorDuty, termStatus } from './occupancy';

import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { DatePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { ImageUpload } from '@/Components/ui/upload';
import { imageFromCachedUrl } from '@/Types/media';
import { Label } from '@/Components/ui/label';
import { NativeSelect, type NativeSelectOption } from '@/Components/ui/native-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';
import { useApi } from '@/Composables/useApi';
import { formatDate, todayIso } from '@/Utils/dateTime';

type Dutiable = App.Entities.Dutiable & Record<string, unknown>;

interface CadenceOption {
  id: string;
  label: string;
  start_date: string;
  end_date: string;
  is_current: boolean;
}

/** A padalinys the duty offers seats to, with its current count. */
export interface QuotaTenant {
  id: number;
  shortname: string;
  quota: number | null;
  occupied: number;
}

/** A term already on the duty, to tell who holds it during the chosen dates. */
export interface ExistingTerm {
  user_id: string;
  start_date?: string | null;
  end_date?: string | null;
}
interface DescriptionText {
  lt: string;
  en: string;
}

const LOCALES = ['lt', 'en'] as const;
const NO_STUDY_PROGRAM = '__none__';
const CUSTOM_DATES = 'custom';

const props = withDefaults(defineProps<{
  open: boolean;
  /** Fixed duty. Omit (with no `dutiable`) and the sheet asks which duty — the user-record entry point. */
  duty?: (App.Entities.Duty & Record<string, unknown>) | null;
  /** The term being edited; omit to assign a new member. */
  dutiable?: Dutiable | null;
  /** Pre-selected member. Without a fixed duty it is the fixed side and the sheet asks for the duty. */
  user?: (App.Entities.User & Record<string, unknown>) | MemberHit | null;
  /** Offered programmes; narrowed to the chosen duty's tenant when the duty is picked in the sheet. */
  studyPrograms?: (App.Entities.StudyProgram & { tenant_id?: number | null })[];
  /** Members who already hold the duty right now; used when `existingTerms` is not given. */
  takenIds?: string[];
  /** Every term on the duty, so only people holding it during the chosen dates are blocked. */
  existingTerms?: ExistingTerm[] | null;
  /** Padaliniai the seat may count toward; the picker shows only with `canAssignQuota`. */
  quotaTenants?: QuotaTenant[];
  canAssignQuota?: boolean;
}>(), {
  duty: null,
  dutiable: null,
  user: null,
  studyPrograms: () => [],
  takenIds: () => [],
  existingTerms: null,
  quotaTenants: () => [],
  canAssignQuota: false,
});

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'success'): void;
}>();

const { report: accessWarningReport, open: accessWarningOpen, guardedSubmit, confirm: accessWarningConfirm, cancel: accessWarningCancel } = useAccessChangeGuard();

const isEditing = computed(() => !!props.dutiable);
const pickDuty = computed(() => !props.duty && !props.dutiable);
const pickMember = computed(() => !isEditing.value && !(pickDuty.value && props.user));
const chosenDuty = ref<DutyHit | null>(null);
const isExOfficio = computed(() => !!props.dutiable?.via_dutiable_id);
const dutyContext = computed(() => props.duty ?? props.dutiable?.duty ?? null);
const canEndTerm = computed(() => !!props.dutiable && termStatus(props.dutiable as { start_date?: string; end_date?: string | null }) === 'current');

const sheetTitle = computed(() => {
  if (isEditing.value) {
    return $t('Redaguoti pareigybės laikotarpį');
  }

  return pickDuty.value ? $t('Pridėti pareigybę') : $t('Priskirti');
});
const sheetDescription = computed(() => {
  if (isEditing.value) {
    return $t('Pakeisk laikotarpį ar papildomą informaciją apie šį priskyrimą.');
  }

  return pickDuty.value
    ? $t('Pasirink pareigybę ir kadenciją, kurią narys ją ėjo ar eina.')
    : $t('Pasirink narį ir kadenciją — dabartinę ar ankstesnę.');
});

const page = usePage();
const canCreateMember = computed(() => Boolean((page.props.auth as { can?: { create?: { user?: boolean } } } | undefined)?.can?.create?.user));

/** Duty types are known on the duty record and from search; elsewhere the old behaviour stays. */
const dutyTypeIds = computed<number[] | null>(() => {
  if (pickDuty.value) {
    return chosenDuty.value?.typeIds ?? null;
  }

  const types = (dutyContext.value as { types?: { id?: number | string | null }[] } | null)?.types;

  return Array.isArray(types)
    ? types.map(type => Number(type.id)).filter((id): id is number => Number.isInteger(id))
    : null;
});

const availableStudyPrograms = computed(() => {
  const tenantId = chosenDuty.value?.homeTenantId;

  if (!pickDuty.value || !tenantId) {
    return props.studyPrograms;
  }

  return props.studyPrograms.filter(program => program.tenant_id === tenantId);
});
// Hidden when the duty is picked in the sheet: its padaliniai are not known there.
const showQuotaTenant = computed(() => props.canAssignQuota && !pickDuty.value && props.quotaTenants.length > 0);
const quotaTenantOptions = computed<NativeSelectOption[]>(() => props.quotaTenants.map(tenant => ({
  value: tenant.id,
  label: tenant.quota === null ? tenant.shortname : `${tenant.shortname} · ${tenant.occupied}/${tenant.quota}`,
})));

const saveLabel = computed(() => (isEditing.value ? $t('Išsaugoti') : $t('Priskirti')));

// A programme only means something on a curator's term; one already set is never hidden.
const showStudyProgram = computed(() => availableStudyPrograms.value.length > 0 && (
  dutyTypeIds.value === null
  || isCuratorDuty(dutyTypeIds.value, (page.props.curatorDutyTypeIds as number[] | undefined) ?? [])
  || !!props.dutiable?.study_program_id
  || hasText(asNote(props.dutiable?.study_program_note))
));

const asDescription = (value: unknown): DescriptionText => {
  // A localized string means the payload was not the full translation map; keep it rather than blanking it on save.
  if (typeof value === 'string') {
    return { lt: value, en: '' };
  }

  const text = (value && typeof value === 'object' ? value : {}) as Partial<DescriptionText>;

  return { lt: text.lt ?? '', en: text.en ?? '' };
};

const asNote = (value: unknown): DescriptionText => asDescription(value);

const initialValues = () => ({
  duty_id: String(dutyContext.value?.id ?? props.dutiable?.duty_id ?? ''),
  user_id: String(props.user?.id ?? props.dutiable?.dutiable_id ?? ''),
  start_date: props.dutiable?.start_date ? String(props.dutiable.start_date).slice(0, 10) : todayIso(),
  end_date: props.dutiable?.end_date ? String(props.dutiable.end_date).slice(0, 10) : null as string | null,
  study_program_id: (props.dutiable?.study_program_id ?? null) as string | null,
  additional_email: props.dutiable?.additional_email ?? '',
  photo_media: imageFromCachedUrl(props.dutiable?.additional_photo, props.dutiable?.additional_photo_focal_point ?? null),
  study_program_note: asNote(props.dutiable?.study_program_note),
  description: asDescription(props.dutiable?.description),
  use_original_duty_name: Boolean(props.dutiable?.use_original_duty_name),
  tenant_id: (props.dutiable?.tenant_id ?? null) as number | null,
  new_user: null as { name: string; email: string | null } | null,
  acknowledge_access_change: false,
});

const form = useForm(initialValues());

const hasText = (text: DescriptionText) => text.lt.trim() !== '' || text.en.trim() !== '';
const hasDescription = computed(() => hasText(form.description));

const extrasOpen = ref(false);
const descriptionLocale = ref<(typeof LOCALES)[number]>('lt');

const hasExtras = () => hasDescription.value
  || form.additional_email.trim() !== ''
  || !!form.photo_media
  || form.use_original_duty_name
  || !!form.study_program_id
  || hasText(form.study_program_note);

const member = ref<MemberHit | null>(null);

const initialMember = (): MemberHit | null => {
  const known = props.user ?? props.dutiable?.user ?? props.dutiable?.dutiable ?? null;

  return known ? (known as MemberHit) : null;
};

watch(member, (hit) => {
  form.user_id = hit && !hit.isNew ? String(hit.id) : '';
  form.new_user = hit?.isNew ? { name: hit.name, email: hit.email ?? null } : null;
});

watch(chosenDuty, (hit) => {
  form.duty_id = hit ? hit.id : '';
  // A programme belongs to one tenant, so a previous choice may no longer be on offer.
  form.study_program_id = null;
});

const cadenceDutyId = computed(() => (pickDuty.value ? chosenDuty.value?.id : dutyContext.value?.id) ?? null);
const cadencesUrl = ref('');
const { data: cadencesData, execute: fetchCadences } = useApi<CadenceOption[]>(cadencesUrl, {
  immediate: false,
  showErrorToast: false,
});
const cadences = computed(() => (cadencesUrl.value ? cadencesData.value ?? [] : []));
const selectedCadence = ref<string>(CUSTOM_DATES);
// A new term defaults to the current cadence once the list arrives.
const awaitingDefaultCadence = ref(false);

function loadCadences() {
  const dutyId = cadenceDutyId.value;

  if (!props.open || !dutyId || isExOfficio.value) {
    cadencesUrl.value = '';
    return;
  }

  cadencesUrl.value = route('api.v1.admin.duties.cadences', dutyId);
  fetchCadences();
}

watch(cadenceDutyId, () => {
  if (props.open) {
    awaitingDefaultCadence.value = !isEditing.value;
    loadCadences();
  }
});

/** Joining mid-term starts today; a past or future term is recorded in full. */
const presetFor = (cadence: CadenceOption) => {
  const today = todayIso();

  return {
    start: cadence.is_current && cadence.start_date < today ? today : cadence.start_date,
    end: cadence.end_date,
  };
};

const matchingCadence = () => cadences.value.find((cadence) => {
  const preset = presetFor(cadence);
  const exact = form.start_date === cadence.start_date && form.end_date === cadence.end_date;

  return exact || (form.start_date === preset.start && form.end_date === preset.end);
});

watch(cadences, (list) => {
  if (list.length === 0) {
    return;
  }

  if (awaitingDefaultCadence.value) {
    awaitingDefaultCadence.value = false;
    const current = list.find(cadence => cadence.is_current);

    if (current) {
      applyCadence(current);
      return;
    }
  }

  selectedCadence.value = matchingCadence()?.id ?? CUSTOM_DATES;
});

function applyCadence(cadence: CadenceOption) {
  const preset = presetFor(cadence);
  selectedCadence.value = cadence.id;
  form.start_date = preset.start;
  form.end_date = preset.end;
}

watch(selectedCadence, (id) => {
  const cadence = cadences.value.find(option => option.id === id);

  if (cadence && (form.start_date !== presetFor(cadence).start || form.end_date !== presetFor(cadence).end)) {
    applyCadence(cadence);
  }
});

const cadenceOptions = computed<NativeSelectOption[]>(() => cadences.value.length === 0
  ? []
  : [
      ...cadences.value.map(cadence => ({
        value: cadence.id,
        label: cadence.is_current ? `${cadence.label} (${$t('dabartinė')})` : cadence.label,
      })),
      { value: CUSTOM_DATES, label: $t('Kitos datos') },
    ]);

const cadenceHint = computed(() => {
  const cadence = cadences.value.find(option => option.id === selectedCadence.value);

  if (!cadence) {
    return $t('Nurodyk datas pats.');
  }

  return cadence.is_current
    ? $t('Nuo šiandien iki kadencijos pabaigos.')
    : $t('Visa kadencija: :start – :end.', { start: cadence.start_date, end: cadence.end_date });
});

/** Someone already holding the duty during these dates cannot get a second, overlapping term. */
const takenInRange = computed(() => {
  if (!props.existingTerms) {
    return props.takenIds;
  }

  const start = form.start_date || todayIso();
  const end = form.end_date;

  return props.existingTerms
    .filter(term => (!end || String(term.start_date ?? '').slice(0, 10) <= end)
      && (!term.end_date || String(term.end_date).slice(0, 10) >= start))
    .map(term => term.user_id);
});

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return;
    }

    form.defaults(initialValues());
    form.reset();
    form.clearErrors();
    member.value = initialMember();
    chosenDuty.value = null;
    extrasOpen.value = hasExtras();
    descriptionLocale.value = 'lt';
    selectedCadence.value = CUSTOM_DATES;
    awaitingDefaultCadence.value = !isEditing.value;
    loadCadences();
  },
  { immediate: true },
);

// The picker speaks UTC-noon Dates; the API and the form speak YYYY-MM-DD.
const syncCadenceToDates = () => {
  selectedCadence.value = matchingCadence()?.id ?? CUSTOM_DATES;
};
const startDate = computed({
  get: () => form.start_date,
  set: (value: Date | undefined) => {
    form.start_date = value ? formatDate(value) : '';
    syncCadenceToDates();
  },
});
const endDate = computed({
  get: () => form.end_date,
  set: (value: Date | undefined) => {
    form.end_date = value ? formatDate(value) : null;
    syncCadenceToDates();
  },
});

const studyProgramValue = computed({
  get: () => form.study_program_id ?? NO_STUDY_PROGRAM,
  set: (value: string) => {
    form.study_program_id = value === NO_STUDY_PROGRAM ? null : value;
  },
});

const onExtrasToggle = (event: Event) => {
  extrasOpen.value = (event.target as HTMLDetailsElement).open;
};

const endTermOpen = ref(false);
const deleteOpen = ref(false);

/** A self-lockout warning comes back as a flash on a *successful* visit; nothing was saved. */
const wasBlockedByAccessWarning = () =>
  !!(usePage().props.flash as { access_change_warning?: unknown } | undefined)?.access_change_warning;

const finish = () => {
  if (wasBlockedByAccessWarning()) {
    return;
  }

  emit('update:open', false);
  emit('success');
};

const submit = () => {
  guardedSubmit((acknowledge) => {
    form.acknowledge_access_change = acknowledge;

    const options = { preserveScroll: true, onSuccess: finish };
    const payload = form.transform(({ new_user: newUser, tenant_id: tenantId, ...data }) => ({
      ...data,
      ...(newUser ? { new_user: newUser } : {}),
      ...(showQuotaTenant.value && !isExOfficio.value ? { tenant_id: tenantId } : {}),
      description: hasText(data.description) ? data.description : null,
      study_program_note: hasText(data.study_program_note) ? data.study_program_note : null,
    }));

    if (isEditing.value) {
      payload.patch(route('dutiables.update', props.dutiable!.id), options);
    }
    else {
      payload.post(route('dutiables.store'), options);
    }
  });
};

const endTerm = () => {
  form.end_date = todayIso();
  submit();
};

const deleteOccupancy = () => {
  if (!props.dutiable) {
    return;
  }

  guardedSubmit((acknowledge) => {
    router.delete(route('dutiables.destroy', props.dutiable!.id), {
      data: { acknowledge_access_change: acknowledge, stay: true },
      preserveScroll: true,
      onSuccess: finish,
    });
  });
};
</script>
