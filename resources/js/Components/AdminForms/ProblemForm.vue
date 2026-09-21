<template>
  <FormPage
    :title="isEditing ? problemTitle : $t('Nauja problema')"
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
    @update:locale="activeLocale = $event"
    @submit="emit('submit:form', form)"
  >
    <FormSection
      :title="$t('Kas tai?')"
      :description="$t('Nurodyk problemos pavadinimą, aprašymą, padalinį ir susijusias institucijas.')"
    >
      <div class="space-y-1.5">
        <Label for="problem-title" class="text-sm font-medium">
          {{ capitalize($tChoice('entities.problem.title', 1)) }} ({{ activeLocale.toUpperCase() }}) *
        </Label>
        <Input
          id="problem-title"
          v-model="form.title[activeLocale]"
          :placeholder="capitalize($tChoice('entities.problem.title', 1))"
        />
        <p v-if="form.errors[`title.${activeLocale}`]" class="text-xs text-destructive">
          {{ form.errors[`title.${activeLocale}`] }}
        </p>
      </div>

      <div class="space-y-2">
        <Label class="text-sm font-medium">
          {{ capitalize($tChoice('entities.problem.description', 1)) }} ({{ activeLocale.toUpperCase() }})
        </Label>
        <TiptapEditor
          v-if="activeLocale === 'lt'"
          v-model="form.description.lt"
          preset="full"
          html
        />
        <TiptapEditor
          v-else
          v-model="form.description.en"
          preset="full"
          html
        />
        <p v-if="form.errors[`description.${activeLocale}`]" class="text-xs text-destructive">
          {{ form.errors[`description.${activeLocale}`] }}
        </p>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="space-y-1.5">
          <Label for="problem-tenant" class="text-sm font-medium">
            {{ capitalize($tChoice('entities.tenant.model', 1)) }} *
          </Label>
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
          <p v-if="form.errors.tenant_id" class="text-xs text-destructive">
            {{ form.errors.tenant_id }}
          </p>
        </div>

        <div class="space-y-1.5">
          <Label for="problem-status" class="text-sm font-medium">
            {{ capitalize($tChoice('entities.problem.status', 1)) }} *
          </Label>
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
          <p v-if="form.errors.status" class="text-xs text-destructive">
            {{ form.errors.status }}
          </p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="space-y-1.5">
          <Label for="problem-occurred-at" class="text-sm font-medium">
            {{ capitalize($tChoice('entities.problem.occurred_at', 1)) }} *
          </Label>
          <Input id="problem-occurred-at" v-model="form.occurred_at" type="date" />
          <p v-if="form.errors.occurred_at" class="text-xs text-destructive">
            {{ form.errors.occurred_at }}
          </p>
        </div>

        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <Label for="problem-resolved-at" class="text-sm font-medium">
              {{ capitalize($tChoice('entities.problem.resolved_at', 1)) }}
            </Label>
            <span class="text-xs text-muted-foreground">{{ $t('(neprivaloma)') }}</span>
          </div>
          <Input id="problem-resolved-at" v-model="form.resolved_at" type="date" />
          <p v-if="form.errors.resolved_at" class="text-xs text-destructive">
            {{ form.errors.resolved_at }}
          </p>
        </div>
      </div>

      <div class="space-y-1.5">
        <div class="flex items-center justify-between">
          <Label class="text-sm font-medium">
            {{ capitalize($tChoice('entities.problem.responsible_user', 1)) }}
          </Label>
          <span class="text-xs text-muted-foreground">{{ $t('(neprivaloma)') }}</span>
        </div>
        <Combobox
          v-model="selectedUser"
          :filter-function="() => userOptions"
          @update:model-value="handleUserSelect"
        >
          <ComboboxAnchor class="flex h-9 w-full items-center justify-between gap-2 border border-input bg-card px-3 py-2 text-sm">
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
          <ComboboxList>
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
                  {{ user.name }}
                </ComboboxItem>
              </template>
            </ComboboxViewport>
          </ComboboxList>
        </Combobox>
        <p v-if="form.errors.responsible_user_id" class="text-xs text-destructive">
          {{ form.errors.responsible_user_id }}
        </p>
      </div>

      <div class="space-y-1.5">
        <div class="flex items-center justify-between">
          <Label class="text-sm font-medium">
            {{ capitalize($tChoice('entities.problem.categories', 2)) }}
          </Label>
          <span class="text-xs text-muted-foreground">{{ $t('(neprivaloma)') }}</span>
        </div>
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
        <p v-if="form.errors.categories" class="text-xs text-destructive">
          {{ form.errors.categories }}
        </p>
      </div>

      <div class="space-y-1.5">
        <div class="flex items-center justify-between">
          <Label class="text-sm font-medium">
            {{ capitalize($tChoice('entities.institution.model', 2)) }}
          </Label>
          <span class="text-xs text-muted-foreground">{{ $t('(neprivaloma)') }}</span>
        </div>
        <MultiSelect
          v-model="selectedInstitutions"
          :options="institutionOptions"
          :placeholder="capitalize($tChoice('entities.institution.model', 2))"
        />
        <p v-if="form.errors.institutions" class="text-xs text-destructive">
          {{ form.errors.institutions }}
        </p>
      </div>
    </FormSection>

    <FormSection
      :title="capitalize($tChoice('entities.problem.steps_taken', 1))"
      :description="$t('Aprašykite veiksmus, kurie jau buvo atlikti bandant išspręsti šią problemą.')"
    >
      <div class="space-y-2">
        <TiptapEditor
          v-if="activeLocale === 'lt'"
          v-model="form.steps_taken.lt"
          preset="full"
          html
        />
        <TiptapEditor
          v-else
          v-model="form.steps_taken.en"
          preset="full"
          html
        />
        <p v-if="form.errors[`steps_taken.${activeLocale}`]" class="text-xs text-destructive">
          {{ form.errors[`steps_taken.${activeLocale}`] }}
        </p>
      </div>
    </FormSection>

    <FormSection
      :title="capitalize($tChoice('entities.problem.solution', 1))"
      :description="$t('Aprašykite problemos sprendimą, jei toks jau rastas. Šis laukas gali būti užpildytas vėliau.')"
    >
      <div class="space-y-2">
        <TiptapEditor
          v-if="activeLocale === 'lt'"
          v-model="form.solution.lt"
          preset="full"
          html
        />
        <TiptapEditor
          v-else
          v-model="form.solution.en"
          preset="full"
          html
        />
        <p v-if="form.errors[`solution.${activeLocale}`]" class="text-xs text-destructive">
          {{ form.errors[`solution.${activeLocale}`] }}
        </p>
      </div>
    </FormSection>

    <template v-if="isEditing && enableDelete" #danger-zone>
      <div class="flex items-center justify-between gap-4">
        <div>
          <h4 class="text-sm font-semibold text-destructive">
            {{ $t('Ištrinti problemą') }}
          </h4>
          <p class="text-xs text-muted-foreground">
            {{ $t('Problema bus perkelta į šiukšlinę.') }}
          </p>
        </div>
        <Button type="button" variant="destructive" size="sm" class="u-touch shrink-0" @click="deleteConfirmOpen = true">
          <Trash2 class="mr-1.5 size-4" />
          {{ $t('Ištrinti') }}
        </Button>
      </div>

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
import { ChevronsUpDown, Trash2, X } from 'lucide-vue-next';
import {
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxRoot as Combobox,
} from 'reka-ui';
import { capitalize, computed, ref } from 'vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import {
  ComboboxItem,
  ComboboxList,
  ComboboxViewport,
} from '@/Components/ui/combobox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { useApi } from '@/Composables/useApi';
import { ModelEnum } from '@/Types/enums';

interface Translated {
  lt: string;
  en: string;
}

interface UserOption {
  id: string;
  name: string;
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
const deleteConfirmOpen = ref(false);

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

const fieldIds = {
  'title.lt': 'problem-title',
  'title.en': 'problem-title',
  'tenant_id': 'problem-tenant',
  'status': 'problem-status',
  'occurred_at': 'problem-occurred-at',
  'resolved_at': 'problem-resolved-at',
};

const missingLocaleCounts = computed(() => ({
  lt: form.title.lt.trim() === '' ? 1 : 0,
  en: form.title.en.trim() === '' ? 1 : 0,
}));

const tenantIdString = computed({
  get: () => (form.tenant_id != null ? String(form.tenant_id) : ''),
  set: (val: string) => {
    form.tenant_id = val ? Number(val) : null;
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
      permission: 'problems.create.padalinys',
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

const institutionOptions = computed(() => {
  const filtered = form.tenant_id
    ? props.institutions.filter(i => i.tenant_id === form.tenant_id)
    : props.institutions;

  return filtered.map(institution => ({
    label: institution.name as string,
    value: institution.id,
  }));
});

const selectedInstitutions = computed({
  get: () =>
    (form.institutions as string[])
      .map(id => institutionOptions.value.find(opt => opt.value === id))
      .filter((opt): opt is { label: string; value: string } => Boolean(opt)),
  set: (items: { label: string; value: string }[]) => {
    form.institutions = items.map(item => item.value);
  },
});
</script>
