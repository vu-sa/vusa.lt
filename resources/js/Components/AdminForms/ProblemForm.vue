<template>
  <FormPage
    :title="isEditing ? problemTitle : $t('Nauja problema')"
    :bar-title
    :head-title="isEditing ? problemTitle : $t('Nauja problema')"
    :lead="isEditing ? undefined : $t('Užregistruok problemą, su kuria susidūrė studentai.')"
    :entity-type="ModelEnum.PROBLEM"
    :back-href="isEditing && form.id ? route('problems.show', form.id) : route('problems.index')"
    :back-label="isEditing ? $t('Į problemą') : $t('Problemos')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isEditing ? 'edit' : 'create'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    :missing-locale-counts
    :created-at="isEditing ? (source.created_at as string | undefined) : undefined"
    :updated-at="isEditing ? (source.updated_at as string | undefined) : undefined"
    :activity-subject="isEditing && form.id ? { type: 'problem', id: String(form.id) } : undefined"
    @update:locale="activeLocale = $event"
    @submit="emit('submit:form', form)"
  >
    <template v-if="isEditing" #title-status>
      <StatusBadge :status="problemStatuses[form.status as keyof typeof problemStatuses]" />
    </template>

    <div class="space-y-4">
      <p class="max-w-prose border-l border-border pl-3 text-sm leading-relaxed text-muted-foreground" data-testid="problem-required-note">
        {{ $t('problems.form.instructions') }}
      </p>
      <FormFieldWrapper
        id="problem-title"
        :label="`${capitalize($tChoice('entities.problem.title', 1))} (${activeLocale.toUpperCase()})`"
        :required="!form.title[otherLocale].trim()"
        :error="form.errors[`title.${activeLocale}`]"
      >
        <Input
          id="problem-title"
          v-model="form.title[activeLocale]"
          :placeholder="capitalize($tChoice('entities.problem.title', 1))"
          :class="['h-11', fieldSurfaceClass]"
        />
      </FormFieldWrapper>
    </div>

    <FormFieldWrapper
      id="problem-description"
      :label="`${capitalize($tChoice('entities.problem.description', 1))} (${activeLocale.toUpperCase()})`"
      :required="!form.description[otherLocale].trim()"
      :error="form.errors[`description.${activeLocale}`]"
    >
      <TiptapEditor :key="activeLocale" v-model="form.description[activeLocale]" tools="description" html />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="problem-steps-taken"
      :label="capitalize($tChoice('entities.problem.steps_taken', 1))"
      :hint="$t('Aprašykite veiksmus, kurie jau buvo atlikti bandant išspręsti šią problemą.')"
      :error="form.errors[`steps_taken.${activeLocale}`]"
    >
      <TiptapEditor :key="activeLocale" v-model="form.steps_taken[activeLocale]" tools="description" html />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="problem-solution"
      :label="capitalize($tChoice('entities.problem.solution', 1))"
      :hint="$t('Aprašykite problemos sprendimą, jei toks jau rastas. Šis laukas gali būti užpildytas vėliau.')"
      :error="form.errors[`solution.${activeLocale}`]"
    >
      <TiptapEditor :key="activeLocale" v-model="form.solution[activeLocale]" tools="description" html />
    </FormFieldWrapper>

    <template #aside>
      <FormPanel :title="$t('Būsena ir priskyrimas')" :icon="CircleDot" title-class="text-brand">
        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper
            id="problem-tenant"
            :label="capitalize($tChoice('entities.tenant.model', 1))"
            required
            :error="form.errors.tenant_id"
          >
            <Select v-model="tenantIdString">
              <SelectTrigger id="problem-tenant">
                <SelectValue :placeholder="capitalize($tChoice('entities.tenant.model', 1))" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                  {{ tenant.shortname || tenant.fullname }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>

          <FormFieldWrapper
            id="problem-status"
            :label="capitalize($tChoice('entities.problem.status', 1))"
            required
            :error="form.errors.status"
          >
            <Select v-model="form.status">
              <SelectTrigger id="problem-status">
                <SelectValue :placeholder="capitalize($tChoice('entities.problem.status', 1))" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="open">
                  {{ $t('Atvira') }}
                </SelectItem>
                <SelectItem value="in_progress">
                  {{ $t('Vykdoma') }}
                </SelectItem>
                <SelectItem value="resolved">
                  {{ $t('Išspręsta') }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>
        </div>

        <FormFieldWrapper
          id="problem-responsible-user"
          :label="capitalize($tChoice('entities.problem.responsible_user', 1))"
          :error="form.errors.responsible_user_id"
        >
          <Combobox
            v-model="selectedUser"
            ignore-filter
            open-on-focus
            open-on-click
            @update:model-value="handleUserSelect"
          >
            <ComboboxAnchor class="flex min-h-11 w-full items-center justify-between gap-2 border border-border bg-card px-3 py-2 text-sm">
              <ComboboxInput
                :display-value="(val: unknown) => (val as UserOption)?.name ?? ''"
                :placeholder="capitalize($tChoice('entities.problem.responsible_user', 1))"
                class="w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                @input="onUserSearchInput"
              />
              <button
                v-if="selectedUser"
                type="button"
                class="u-touch shrink-0 opacity-50 hover:opacity-100"
                @click.prevent.stop="clearSelectedUser"
              >
                <X class="size-4" />
              </button>
              <ChevronsUpDown v-else class="size-4 shrink-0 opacity-50" />
            </ComboboxAnchor>
            <ComboboxList class="min-w-[var(--reka-popper-anchor-width)]">
              <ComboboxViewport class="max-h-60">
                <div v-if="userSearchTerm.length < 2 && !selectedUser" class="px-2 py-4 text-center text-sm text-muted-foreground">
                  {{ $t('Įveskite bent 2 simbolius') }}
                </div>
                <div v-else-if="isSearchingUsers" class="px-2 py-4 text-center text-sm text-muted-foreground">
                  {{ $t('Ieškoma...') }}
                </div>
                <template v-else>
                  <ComboboxEmpty>{{ $t('Nerasta') }}</ComboboxEmpty>
                  <ComboboxItem
                    v-for="user in userOptions"
                    :key="user.id"
                    :value="user"
                  >
                    <span class="flex min-w-0 flex-col">
                      <span class="truncate">{{ user.name }}</span>
                      <span v-if="user.email" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
                    </span>
                  </ComboboxItem>
                </template>
              </ComboboxViewport>
            </ComboboxList>
          </Combobox>
        </FormFieldWrapper>

        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper
            id="problem-occurred-at"
            :label="capitalize($tChoice('entities.problem.occurred_at', 1))"
            required
            :error="form.errors.occurred_at"
          >
            <Input id="problem-occurred-at" v-model="form.occurred_at" type="date" :class="fieldSurfaceClass" />
          </FormFieldWrapper>

          <FormFieldWrapper
            id="problem-resolved-at"
            :label="capitalize($tChoice('entities.problem.resolved_at', 1))"
            :error="form.errors.resolved_at"
          >
            <Input id="problem-resolved-at" v-model="form.resolved_at" type="date" :class="fieldSurfaceClass" />
          </FormFieldWrapper>
        </div>
      </FormPanel>

      <FormPanel :title="$t('Klasifikacija')" :icon="Layers" title-class="text-brand">
        <FormFieldWrapper
          id="problem-categories"
          :label="capitalize($tChoice('entities.problem.categories', 2))"
          :error="form.errors.categories"
        >
          <MultiSelect
            v-model="selectedCategories"
            :options="categoryOptions"
            :placeholder="capitalize($tChoice('entities.problem.categories', 2))"
          >
            <template #option="{ item }">
              <div class="flex flex-col">
                <span>{{ item.label }}</span>
                <span v-if="item.description" class="line-clamp-2 text-xs text-muted-foreground">{{ item.description }}</span>
              </div>
            </template>
          </MultiSelect>
        </FormFieldWrapper>

        <FormFieldWrapper
          id="problem-institutions"
          :label="capitalize($tChoice('entities.institution.model', 2))"
          :error="form.errors.institutions"
        >
          <CollectionSelectDialog
            v-model:open="institutionDialogOpen"
            collection="institutions"
            multiple
            allow-empty
            :base-filter-by="institutionBaseFilterBy"
            :initial-hits="selectedInstitutionHits"
            :title="capitalize($tChoice('entities.institution.model', 2))"
            :confirm-label="$t('Pasirinkti')"
            :search-placeholder="$t('Ieškoti institucijos pagal pavadinimą...')"
            :empty-message="$t('Institucijų nerasta')"
            @confirm="onInstitutionsConfirm"
          >
            <template #trigger>
              <Button
                id="problem-institutions"
                type="button"
                variant="outline"
                voice="sentence"
                :class="['w-full justify-between font-normal', fieldSurfaceClass]"
              >
                <span class="truncate" :class="{ 'text-muted-foreground': selectedInstitutionHits.length === 0 }">
                  {{ selectedInstitutionLabel }}
                </span>
                <ChevronsUpDown class="size-4 shrink-0 opacity-50" />
              </Button>
            </template>
          </CollectionSelectDialog>
        </FormFieldWrapper>
      </FormPanel>
    </template>

    <template v-if="isEditing && enableDelete" #danger-zone>
      <Button
        type="button"
        variant="outline"
        class="border-destructive/40 text-destructive hover:border-destructive hover:bg-destructive hover:text-destructive-foreground pointer-coarse:min-h-11"
        @click="deleteConfirmOpen = true"
      >
        <Trash2 class="size-4" />
        {{ $t('Ištrinti problemą') }}
      </Button>

      <ConfirmDialog
        v-model:open="deleteConfirmOpen"
        :title="$t('Ištrinti problemą?')"
        :description="$t('Problema bus perkelta į šiukšlinę.')"
        :confirm-label="$t('Ištrinti')"
        destructive
        @confirm="emit('delete')"
      />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ChevronsUpDown, CircleDot, Layers, Trash2, X } from 'lucide-vue-next';
import {
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxRoot as Combobox,
} from 'reka-ui';
import { capitalize, computed, ref } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import FormPanel from '@/Components/Patterns/FormPanel.vue';
import StatusBadge from '@/Components/Patterns/StatusBadge.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import {
  ComboboxItem,
  ComboboxList,
  ComboboxViewport,
} from '@/Components/ui/combobox';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { MultiSelect } from '@/Components/ui/multi-select';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { useApi } from '@/Composables/useApi';
import { problemStatuses } from '@/Constants/statuses';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { ModelEnum } from '@/Types/enums';

interface Translated {
  lt: string;
  en: string;
}

interface UserOption {
  id: string;
  name: string;
  email?: string;
}

const props = defineProps<{
  problem?: App.Entities.Problem | Record<string, unknown>;
  form?: Record<string, unknown>;
  tenants: Array<App.Entities.Tenant>;
  categories: Array<App.Entities.ProblemCategory>;
  initialResponsibleUser?: { id: string; name: string } | null;
  institutions: Array<App.Entities.Institution>;
  rememberKey?: string;
  enableDelete?: boolean;
}>();

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const activeLocale = ref<'lt' | 'en'>('lt');
const otherLocale = computed(() => activeLocale.value === 'lt' ? 'en' : 'lt');
const deleteConfirmOpen = ref(false);
const institutionDialogOpen = ref(false);

const asTranslated = (value: unknown): Translated => {
  if (typeof value === 'string') {
    return { lt: value, en: '' };
  }
  const text = (value && typeof value === 'object' && !Array.isArray(value) ? value : {}) as Partial<Translated>;
  return { lt: text.lt ?? '', en: text.en ?? '' };
};

function formatDateForPicker(dateValue: string | null | undefined): string | null {
  if (!dateValue) return null;
  try {
    const date = new Date(dateValue);
    if (isNaN(date.getTime())) return null;
    return date.toISOString().split('T')[0] || null;
  }
  catch {
    return null;
  }
}

const source = (props.problem ?? props.form ?? {}) as Record<string, unknown>;

const initial = () => ({
  id: (source.id ?? undefined) as string | undefined,
  title: asTranslated(source.title),
  description: asTranslated(source.description),
  solution: asTranslated(source.solution),
  steps_taken: asTranslated(source.steps_taken),
  tenant_id: (source.tenant_id ?? null) as number | null,
  responsible_user_id: (source.responsible_user_id ?? null) as string | null,
  occurred_at: (source.occurred_at ? formatDateForPicker(source.occurred_at as string) : new Date().toISOString().split('T')[0]) as string,
  resolved_at: (source.resolved_at ? formatDateForPicker(source.resolved_at as string) : null) as string | null,
  status: (source.status ?? 'open') as string,
  categories: (Array.isArray(source.categories) ? source.categories.map((c: unknown) => (c && typeof c === 'object' && 'id' in c ? c.id : c)) : []) as number[],
  institutions: (Array.isArray(source.institutions) ? source.institutions.map((i: unknown) => (i && typeof i === 'object' && 'id' in i ? i.id : i)) : []) as string[],
});

const form = props.rememberKey ? useForm(props.rememberKey, initial()) : useForm(initial());

const isEditing = computed(() => Boolean(form.id));

const problemTitle = computed(() => form.title.lt || form.title.en || '');
const barTitle = computed(() => (isEditing.value ? (problemTitle.value || $t('Problema')) : $t('Nauja problema')));

const fieldIds = {
  'title.lt': 'problem-title',
  'title.en': 'problem-title',
  'description.lt': 'problem-description',
  'description.en': 'problem-description',
  'tenant_id': 'problem-tenant',
  'status': 'problem-status',
  'occurred_at': 'problem-occurred-at',
  'resolved_at': 'problem-resolved-at',
  'responsible_user_id': 'problem-responsible-user',
  'categories': 'problem-categories',
  'institutions': 'problem-institutions',
  'steps_taken.lt': 'problem-steps-taken',
  'steps_taken.en': 'problem-steps-taken',
  'solution.lt': 'problem-solution',
  'solution.en': 'problem-solution',
};

const missingLocaleCounts = computed(() => ({
  lt: form.title.lt.trim() === '' ? 1 : 0,
  en: form.title.en.trim() === '' ? 1 : 0,
}));

const tenantIdString = computed({
  get: () => (form.tenant_id != null ? String(form.tenant_id) : ''),
  set: (val: string) => {
    const tenantId = val ? Number(val) : null;
    if (tenantId !== form.tenant_id) {
      form.institutions = [];
      selectedInstitutionHits.value = [];
    }
    form.tenant_id = tenantId;
  },
});

// --- User search ---
const userSearchTerm = ref('');
const selectedUser = ref<UserOption | null>(props.initialResponsibleUser ?? null);
const userSearchUrl = ref('');

const { data: searchedUsers, isFetching: isSearchingUsers, execute: executeUserSearch } = useApi<UserOption[]>(
  userSearchUrl,
  { immediate: false, showErrorToast: false },
);

const userOptions = computed<UserOption[]>(() => searchedUsers.value ?? []);

const debouncedSearch = useDebounceFn(() => {
  if (userSearchTerm.value.length >= 2) {
    const params = new URLSearchParams({
      search: userSearchTerm.value,
      permission: isEditing.value ? 'problems.update.padalinys' : 'problems.create.padalinys',
    });
    userSearchUrl.value = `${route('api.v1.admin.users.search')}?${params.toString()}`;
    executeUserSearch();
  }
}, 300);

function onUserSearchInput(event: Event) {
  const target = event.target as HTMLInputElement;
  userSearchTerm.value = target.value;
  if (userSearchTerm.value.length >= 2) {
    debouncedSearch();
  }
}

function handleUserSelect(val: unknown) {
  const user = val as UserOption | null;
  selectedUser.value = user;
  form.responsible_user_id = user?.id ?? null;
}

function clearSelectedUser() {
  selectedUser.value = null;
  userSearchTerm.value = '';
  form.responsible_user_id = null;
}

const categoryOptions = computed(() =>
  props.categories.map(category => ({
    label: category.name as string,
    value: category.id,
    description: category.description as string | null,
  })),
);

const selectedCategories = computed({
  get: () =>
    (form.categories as number[])
      .map(id => categoryOptions.value.find(opt => opt.value === id))
      .filter((opt): opt is { label: string; value: number } => Boolean(opt)),
  set: (items: { label: string; value: number }[]) => {
    form.categories = items.map(item => item.value);
  },
});

const selectedInstitutionHits = ref<NormalizedSearchHit[]>(props.institutions
  .filter(institution => (form.institutions as string[]).includes(institution.id))
  .map(institution => normalizeHit('institutions', {
    id: institution.id,
    name_lt: institution.name,
    tenant_id: institution.tenant_id,
  })));

const institutionBaseFilterBy = computed(() => {
  const tenantIds = form.tenant_id != null
    ? [form.tenant_id]
    : props.tenants.map(tenant => tenant.id);
  return `tenant_ids:=[${tenantIds.length ? tenantIds.join(',') : -1}]`;
});

const selectedInstitutionLabel = computed(() => selectedInstitutionHits.value.length
  ? selectedInstitutionHits.value.map(hit => hit.title).join(', ')
  : capitalize($tChoice('entities.institution.model', 2)));

function onInstitutionsConfirm(hits: NormalizedSearchHit[]) {
  selectedInstitutionHits.value = hits;
  form.institutions = hits.map(hit => hit.recordId);
}
</script>
