<template>
  <div>
    <SheetForm
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

      <div
        v-if="isFull && !isEditing"
        class="border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] p-3 text-xs text-[var(--status-attention)]"
        data-testid="assign-sheet-full-notice"
      >
        <p class="font-semibold">
          {{ $t('Visos vietos užimtos (:taken / :total)', { taken: String(occupiedPlaces), total: String(dutyContext?.places_to_occupy ?? '') }) }}
        </p>
        <p class="mt-0.5">
          {{ $t('Narį vis tiek gali priskirti; vietų skaičių pakeisi pareigybės nustatymuose.') }}
        </p>
      </div>

      <MemberSearchField
        v-if="!isEditing"
        v-model="member"
        :taken-ids
        :error="form.errors.user_id"
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
            {{ $t('Šios kadencijos laikotarpį valdo pagrindinės pareigos.') }}
          </p>
        </div>
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

      <div class="space-y-4 border-t border-border pt-4">
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

        <div v-if="studyPrograms.length > 0" class="space-y-1.5">
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
              <SelectItem v-for="program in studyPrograms" :key="program.id" :value="String(program.id)">
                {{ program.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="form.errors.study_program_id" class="text-xs text-destructive">
            {{ form.errors.study_program_id }}
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

        <details :open="descriptionOpen" class="group border-t border-border pt-4" @toggle="onDescriptionToggle">
          <summary class="u-touch flex cursor-pointer select-none items-center justify-between text-sm font-semibold text-foreground">
            <span>{{ $t('Viešas aprašymas') }}</span>
            <ChevronDown class="size-4 text-muted-foreground transition-transform group-open:rotate-180" />
          </summary>
          <div v-if="descriptionOpen" class="mt-4 space-y-3">
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
              preset="full"
              html
            />
            <p v-if="form.errors.description" class="text-xs text-destructive">
              {{ form.errors.description }}
            </p>
          </div>
        </details>
      </div>

      <template v-if="isEditing && !isExOfficio" #danger-zone>
        <div class="space-y-4">
          <div v-if="canEndTerm" class="flex items-start justify-between gap-3">
            <div>
              <p class="text-sm font-semibold text-foreground">
                {{ $t('Baigti kadenciją šiandien') }}
              </p>
              <p class="text-xs text-muted-foreground">
                {{ $t('Narys nebebus laikomas šių pareigų nariu, bet įrašas liks istorijoje.') }}
              </p>
            </div>
            <Button type="button" variant="outline" size="sm" class="u-touch shrink-0" :disabled="form.processing" @click="endTermOpen = true">
              <CalendarCheck class="size-4" />
              {{ $t('Baigti kadenciją') }}
            </Button>
          </div>

          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-sm font-semibold text-destructive">
                {{ $t('Ištrinti priskyrimą') }}
              </p>
              <p class="text-xs text-muted-foreground">
                {{ $t('Kadencijos įrašas bus pašalintas. Jei tik baigėsi kadencija, verčiau ją pabaik.') }}
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
      :title="$t('Baigti kadenciją šiandien?')"
      :description="$t('Narys nebebus laikomas šių pareigų nariu, bet įrašas liks istorijoje.')"
      :confirm-label="$t('Baigti kadenciją')"
      @confirm="endTerm"
    />
    <ConfirmDialog
      v-model:open="deleteOpen"
      :title="$t('Ištrinti priskyrimą?')"
      :description="$t('Kadencijos įrašas bus pašalintas.')"
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
import { Briefcase, CalendarCheck, ChevronDown, Sparkles, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import MemberSearchField, { type MemberHit } from './MemberSearchField.vue';
import { termStatus } from './occupancy';

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
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';
import { formatDate, todayIso } from '@/Utils/dateTime';

type Dutiable = App.Entities.Dutiable & Record<string, unknown>;
interface DescriptionText {
  lt: string;
  en: string;
}

const LOCALES = ['lt', 'en'] as const;
const NO_STUDY_PROGRAM = '__none__';

const props = withDefaults(defineProps<{
  open: boolean;
  duty?: (App.Entities.Duty & Record<string, unknown>) | null;
  /** The term being edited; omit to assign a new member. */
  dutiable?: Dutiable | null;
  /** Pre-selected member, e.g. when opened from that member's row. */
  user?: (App.Entities.User & Record<string, unknown>) | MemberHit | null;
  studyPrograms?: App.Entities.StudyProgram[];
  /** Members who already hold the duty right now; they cannot be assigned again. */
  takenIds?: string[];
  occupiedPlaces?: number;
}>(), {
  duty: null,
  dutiable: null,
  user: null,
  studyPrograms: () => [],
  takenIds: () => [],
  occupiedPlaces: 0,
});

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'success'): void;
}>();

const { report: accessWarningReport, open: accessWarningOpen, guardedSubmit, confirm: accessWarningConfirm, cancel: accessWarningCancel } = useAccessChangeGuard();

const isEditing = computed(() => !!props.dutiable);
const isExOfficio = computed(() => !!props.dutiable?.via_dutiable_id);
const dutyContext = computed(() => props.duty ?? props.dutiable?.duty ?? null);
const isFull = computed(() => {
  const places = Number(dutyContext.value?.places_to_occupy ?? 0);

  return places > 0 && props.occupiedPlaces >= places;
});
const canEndTerm = computed(() => !!props.dutiable && termStatus(props.dutiable as { start_date?: string; end_date?: string | null }) === 'current');

const sheetTitle = computed(() => (isEditing.value ? $t('Redaguoti kadenciją') : $t('Priskirti narį')));
const sheetDescription = computed(() => (isEditing.value
  ? $t('Pakeisk laikotarpį ar papildomą informaciją apie šį priskyrimą.')
  : $t('Pasirink narį ir nurodyk, nuo kada jis eina šias pareigas.')));
const saveLabel = computed(() => (isEditing.value ? $t('Išsaugoti') : $t('Priskirti')));

const asDescription = (value: unknown): DescriptionText => {
  const text = (value && typeof value === 'object' ? value : {}) as Partial<DescriptionText>;

  return { lt: text.lt ?? '', en: text.en ?? '' };
};

const initialValues = () => ({
  duty_id: String(dutyContext.value?.id ?? props.dutiable?.duty_id ?? ''),
  user_id: String(props.user?.id ?? props.dutiable?.dutiable_id ?? ''),
  start_date: props.dutiable?.start_date ? String(props.dutiable.start_date).slice(0, 10) : todayIso(),
  end_date: props.dutiable?.end_date ? String(props.dutiable.end_date).slice(0, 10) : null as string | null,
  study_program_id: (props.dutiable?.study_program_id ?? null) as string | null,
  additional_email: props.dutiable?.additional_email ?? '',
  description: asDescription(props.dutiable?.description),
  use_original_duty_name: Boolean(props.dutiable?.use_original_duty_name),
  acknowledge_access_change: false,
});

const form = useForm(initialValues());

const hasText = (text: DescriptionText) => text.lt.trim() !== '' || text.en.trim() !== '';
const hasDescription = computed(() => hasText(form.description));

const descriptionOpen = ref(false);
const descriptionLocale = ref<(typeof LOCALES)[number]>('lt');

const member = ref<MemberHit | null>(null);

const initialMember = (): MemberHit | null => {
  const known = props.user ?? props.dutiable?.user ?? props.dutiable?.dutiable ?? null;

  return known ? (known as MemberHit) : null;
};

watch(member, (hit) => {
  form.user_id = hit ? String(hit.id) : '';
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
    descriptionOpen.value = hasDescription.value;
    descriptionLocale.value = 'lt';
  },
  { immediate: true },
);

// The picker speaks UTC-noon Dates; the API and the form speak YYYY-MM-DD.
const startDate = computed({
  get: () => form.start_date,
  set: (value: Date | undefined) => {
    form.start_date = value ? formatDate(value) : '';
  },
});
const endDate = computed({
  get: () => form.end_date,
  set: (value: Date | undefined) => {
    form.end_date = value ? formatDate(value) : null;
  },
});

const studyProgramValue = computed({
  get: () => form.study_program_id ?? NO_STUDY_PROGRAM,
  set: (value: string) => {
    form.study_program_id = value === NO_STUDY_PROGRAM ? null : value;
  },
});

const onDescriptionToggle = (event: Event) => {
  descriptionOpen.value = (event.target as HTMLDetailsElement).open;
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
    const payload = form.transform(data => ({
      ...data,
      description: hasText(data.description) ? data.description : null,
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
