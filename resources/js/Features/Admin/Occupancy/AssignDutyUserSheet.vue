<template>
  <div>
    <SheetForm
      :open
      :title="sheetTitle"
      :description="sheetDescription"
      :save-label
      :processing="form.processing"
      @update:open="emit('update:open', $event)"
      @submit="submit"
      @cancel="emit('update:open', false)"
    >
      <!-- Fixed Context Banner: Duty if known -->
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

      <!-- Fixed Context Banner: User if known -->
      <div v-if="userContext" class="flex items-start gap-3 border border-border bg-secondary/50 p-3">
        <UserAvatar :user="userContext" :size="40" class="shrink-0" />
        <div class="min-w-0 flex-1">
          <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
            {{ $t('Asmuo') }}
          </p>
          <p class="truncate text-sm font-semibold text-foreground">
            {{ userContext.name }}
          </p>
          <p v-if="userContext.email" class="truncate text-xs text-muted-foreground">
            {{ userContext.email }}
          </p>
        </div>
      </div>

      <!-- User Selector (when user is not pre-fixed) -->
      <div v-if="!userContext" class="space-y-2">
        <Label for="user-search" class="text-sm font-medium">
          {{ $t('Asmuo') }} *
        </Label>

        <div v-if="selectedUser" class="flex items-center justify-between border border-border bg-card p-2.5">
          <div class="flex items-center gap-2.5 min-w-0">
            <UserAvatar :user="selectedUser" :size="32" class="shrink-0" />
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-foreground">
                {{ selectedUser.name }}
              </p>
              <p class="truncate text-xs text-muted-foreground">
                {{ selectedUser.email }}
              </p>
            </div>
          </div>
          <Button
            type="button"
            variant="ghost"
            size="sm"
            class="u-touch shrink-0"
            @click="clearSelectedUser"
          >
            <X class="size-4" />
            <span class="sr-only">{{ $t('Keisti') }}</span>
          </Button>
        </div>

        <div v-else class="relative">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              id="user-search"
              v-model="userSearchQuery"
              class="pl-9 pr-9"
              :placeholder="$t('Ieškoti asmens pagal vardą ar el. paštą…')"
              autocomplete="off"
            />
            <Loader2
              v-if="isSearchingUsers"
              class="absolute right-3 top-1/2 size-4 -translate-y-1/2 animate-spin text-muted-foreground"
            />
          </div>

          <!-- User Search Results Dropdown -->
          <div
            v-if="userSearchQuery.trim().length >= 2"
            class="absolute left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto border border-border bg-popover shadow-md"
          >
            <div v-if="userSearchResults.length === 0 && !isSearchingUsers" class="p-4 text-center text-sm text-muted-foreground">
              {{ $t('Naudotojų nerasta') }}
            </div>
            <ul v-else class="divide-y divide-border">
              <li
                v-for="u in userSearchResults"
                :key="u.id"
              >
                <button
                  type="button"
                  :class="[
                    'flex w-full cursor-pointer items-center justify-between gap-3 p-3 text-left',
                    'transition-colors hover:bg-accent focus-visible:bg-accent focus-visible:outline-none',
                  ]"
                  @click="selectUser(u)"
                >
                  <div class="flex min-w-0 items-center gap-3">
                    <UserAvatar :user="u" :size="32" class="shrink-0" />
                    <div class="min-w-0">
                      <p class="truncate text-sm font-medium text-foreground">
                        {{ u.name }}
                      </p>
                      <p class="truncate text-xs text-muted-foreground">
                        {{ u.email }}
                      </p>
                    </div>
                  </div>
                  <div v-if="u.tenants && u.tenants.length" class="shrink-0">
                    <span class="border border-border bg-secondary px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground">
                      {{ u.tenants.join(', ') }}
                    </span>
                  </div>
                </button>
              </li>
            </ul>
          </div>
        </div>
        <p v-if="form.errors.user_id" class="text-xs text-destructive">
          {{ form.errors.user_id }}
        </p>
      </div>

      <!-- Ex-officio Notice -->
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
            {{ $t('Šio priskyrimo laikotarpis yra valdomas per pagrindines pareigas.') }}
          </p>
        </div>
      </div>

      <!-- Period: Start & End Dates -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <Label for="start_date" class="text-sm font-medium">
            {{ $t('Pradžios data') }} *
          </Label>
          <DatePicker
            id="start_date"
            v-model="form.start_date"
            :disabled="isExOfficio"
          />
          <p v-if="form.errors.start_date" class="text-xs text-destructive">
            {{ form.errors.start_date }}
          </p>
        </div>

        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <Label for="end_date" class="text-sm font-medium">
              {{ $t('Pabaigos data') }}
            </Label>
            <span class="text-xs text-muted-foreground">
              {{ $t('(neprivaloma)') }}
            </span>
          </div>
          <DatePicker
            id="end_date"
            v-model="form.end_date"
            :disabled="isExOfficio"
            clearable
          />
          <p v-if="form.errors.end_date" class="text-xs text-destructive">
            {{ form.errors.end_date }}
          </p>
        </div>
      </div>

      <!-- Details Section: Collapsible or Direct -->
      <div class="space-y-4 border-t border-border pt-4">
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <Label for="additional_email" class="text-sm font-medium">
              {{ $t('Pareigybės el. paštas') }}
            </Label>
            <span class="text-xs text-muted-foreground">
              {{ $t('(neprivaloma)') }}
            </span>
          </div>
          <Input
            id="additional_email"
            v-model="form.additional_email"
            type="email"
            placeholder="vardas.pavarde@vusa.lt"
          />
          <p class="text-xs text-muted-foreground">
            {{ $t('El. pašto adresas, rodomas prie šio naudotojo kontaktų.') }}
          </p>
          <p v-if="form.errors.additional_email" class="text-xs text-destructive">
            {{ form.errors.additional_email }}
          </p>
        </div>

        <!-- Study Program (optional) -->
        <div v-if="studyPrograms && studyPrograms.length > 0" class="space-y-1.5">
          <div class="flex items-center justify-between">
            <Label for="study_program_id" class="text-sm font-medium">
              {{ $t('Studijų programa') }}
            </Label>
            <span class="text-xs text-muted-foreground">
              {{ $t('(neprivaloma)') }}
            </span>
          </div>
          <SingleSelect
            v-model="form.study_program_id"
            :options="studyPrograms"
            label-field="name"
            value-field="id"
            :placeholder="$t('Pasirinkite studijų programą…')"
          />
          <p v-if="form.errors.study_program_id" class="text-xs text-destructive">
            {{ form.errors.study_program_id }}
          </p>
        </div>

        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <Label for="description" class="text-sm font-medium">
              {{ $t('Pastabos / Aprašymas') }}
            </Label>
            <span class="text-xs text-muted-foreground">
              {{ $t('(neprivaloma)') }}
            </span>
          </div>
          <Input
            id="description"
            v-model="form.description"
            :placeholder="$t('Papildoma informacija apie šį priskyrimą…')"
          />
          <p v-if="form.errors.description" class="text-xs text-destructive">
            {{ form.errors.description }}
          </p>
        </div>

        <div class="flex items-center gap-2 pt-1">
          <input
            id="use_original_duty_name"
            v-model="form.use_original_duty_name"
            type="checkbox"
            class="size-4 border-border text-primary focus:ring-primary"
          >
          <Label for="use_original_duty_name" class="cursor-pointer text-xs font-normal text-muted-foreground">
            {{ $t('Naudoti originalų pareigybės pavadinimą viešame puslapyje') }}
          </Label>
        </div>
      </div>

      <!-- Footer extra: Actions for existing occupancy -->
      <template #footer-extra>
        <div v-if="isEditing" class="flex items-center gap-2">
          <Button
            v-if="isActiveTerm"
            type="button"
            variant="outline"
            size="sm"
            class="u-touch"
            :disabled="form.processing || isExOfficio"
            @click="endTerm"
          >
            <CalendarCheck class="size-4 mr-1.5" />
            {{ $t('Baigti kadenciją') }}
          </Button>

          <Button
            type="button"
            variant="ghost"
            size="sm"
            class="u-touch text-destructive hover:bg-destructive/10 hover:text-destructive"
            :disabled="form.processing"
            @click="deleteOccupancy"
          >
            <Trash2 class="size-4 mr-1.5" />
            {{ $t('Ištrinti') }}
          </Button>
        </div>
      </template>
    </SheetForm>

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
import { computed, ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Briefcase,
  CalendarCheck,
  Loader2,
  Search,
  Sparkles,
  Trash2,
  X,
} from 'lucide-vue-next';

import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { DatePicker } from '@/Components/ui/date-picker';
import { SingleSelect } from '@/Components/ui/single-select';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';

export interface UserHit {
  id: string;
  name: string;
  email?: string;
  profile_photo_path?: string | null;
  tenants?: string[];
}

const props = withDefaults(defineProps<{
  open: boolean;
  dutyId?: string | number;
  userId?: string | number;
  duty?: (App.Entities.Duty & Record<string, unknown>) | null;
  user?: (App.Entities.User & Record<string, unknown>) | UserHit | null;
  dutiable?: (App.Entities.Dutiable & Record<string, unknown>) | null;
  studyPrograms?: App.Entities.StudyProgram[];
}>(), {
  dutyId: undefined,
  userId: undefined,
  duty: null,
  user: null,
  dutiable: null,
  studyPrograms: () => [],
});

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'success'): void;
}>();

const { report: accessWarningReport, open: accessWarningOpen, guardedSubmit, confirm: accessWarningConfirm, cancel: accessWarningCancel } = useAccessChangeGuard();

const isEditing = computed(() => !!props.dutiable);
const isExOfficio = computed(() => !!props.dutiable?.via_dutiable_id);

const dutyContext = computed(() => props.duty ?? props.dutiable?.duty ?? null);
const userContext = computed(() => props.user ?? props.dutiable?.user ?? props.dutiable?.dutiable ?? null);

const sheetTitle = computed(() => {
  if (isEditing.value) {
    return $t('Redaguoti priskyrimą');
  }
  return $t('Priskirti pareigoms');
});

const sheetDescription = computed(() => {
  if (isEditing.value) {
    return $t('Nustatykite pareigų ėjimo laikotarpį ir papildomą kontaktų informaciją.');
  }
  return $t('Priskirkite asmenį pareigoms nustatytam laikotarpiui.');
});

const saveLabel = computed(() => {
  if (isEditing.value) {
    return $t('Atnaujinti');
  }
  return $t('Priskirti');
});

const isActiveTerm = computed(() => {
  if (!props.dutiable) return false;
  const end = props.dutiable.end_date;
  if (!end) return true;
  return new Date(end) >= new Date();
});

const form = useForm({
  duty_id: (props.dutyId ?? props.dutiable?.duty_id ?? '') as string,
  user_id: (props.userId ?? props.dutiable?.dutiable_id ?? '') as string,
  start_date: (props.dutiable?.start_date ?? new Date().toISOString().split('T')[0]) as string,
  end_date: (props.dutiable?.end_date ?? null) as string | null,
  study_program_id: (props.dutiable?.study_program_id ?? null) as number | null,
  additional_email: (props.dutiable?.additional_email ?? '') as string,
  description: (props.dutiable?.description ?? '') as string,
  use_original_duty_name: Boolean(props.dutiable?.use_original_duty_name),
  acknowledge_access_change: false,
});

// User Search logic
const selectedUser = ref<UserHit | (App.Entities.User & Record<string, unknown>) | null>(userContext.value ?? null);
const userSearchQuery = ref('');
const userSearchResults = ref<UserHit[]>([]);
const isSearchingUsers = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

// Watch when dutiable / duty / user props change to reset form
watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      form.duty_id = (props.dutyId ?? props.dutiable?.duty_id ?? '') as string;
      form.user_id = (props.userId ?? props.dutiable?.dutiable_id ?? '') as string;
      form.start_date = (props.dutiable?.start_date ?? new Date().toISOString().split('T')[0]) as string;
      form.end_date = (props.dutiable?.end_date ?? null) as string | null;
      form.study_program_id = (props.dutiable?.study_program_id ?? null) as number | null;
      form.additional_email = (props.dutiable?.additional_email ?? '') as string;
      form.description = (props.dutiable?.description ?? '') as string;
      form.use_original_duty_name = Boolean(props.dutiable?.use_original_duty_name);
      form.clearErrors();

      if (userContext.value) {
        selectedUser.value = userContext.value;
      }
      else {
        selectedUser.value = null;
      }
    }
  },
  { immediate: true },
);

const runUserSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  const q = userSearchQuery.value.trim();
  if (q.length < 2) {
    userSearchResults.value = [];
    isSearchingUsers.value = false;
    return;
  }

  isSearchingUsers.value = true;
  searchTimeout = setTimeout(async () => {
    try {
      const url = `${route('api.v1.admin.users.search')}?search=${encodeURIComponent(q)}`;
      const res = await fetch(url, { headers: { Accept: 'application/json' } });
      if (res.ok) {
        const data = await res.json();
        userSearchResults.value = Array.isArray(data) ? data : (data.data ?? []);
      }
    }
    catch {
      userSearchResults.value = [];
    }
    finally {
      isSearchingUsers.value = false;
    }
  }, 250);
};

watch(userSearchQuery, runUserSearch);

const selectUser = (u: UserHit) => {
  selectedUser.value = u;
  form.user_id = u.id;
  userSearchQuery.value = '';
  userSearchResults.value = [];
};

const clearSelectedUser = () => {
  selectedUser.value = null;
  form.user_id = '';
};

const submit = () => {
  guardedSubmit((acknowledge) => {
    form.acknowledge_access_change = acknowledge;

    if (isEditing.value) {
      form.patch(route('dutiables.update', props.dutiable.id), {
        preserveScroll: true,
        onSuccess: () => {
          emit('update:open', false);
          emit('success');
        },
      });
    }
    else {
      form.post(route('dutiables.store'), {
        preserveScroll: true,
        onSuccess: () => {
          emit('update:open', false);
          emit('success');
        },
      });
    }
  });
};

const endTerm = () => {
  if (!props.dutiable) return;
  const today = new Date().toISOString().split('T')[0];
  form.end_date = today;
  submit();
};

const deleteOccupancy = () => {
  if (!props.dutiable) return;
  if (confirm($t('Ar tikrai norite pašalinti šį priskyrimą?'))) {
    guardedSubmit((acknowledge) => {
      router.delete(route('dutiables.destroy', props.dutiable.id), {
        data: { acknowledge_access_change: acknowledge, stay: true },
        preserveScroll: true,
        onSuccess: () => {
          emit('update:open', false);
          emit('success');
        },
      });
    });
  }
};
</script>
