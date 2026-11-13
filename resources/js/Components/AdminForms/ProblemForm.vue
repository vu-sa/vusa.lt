<template>
  <FormPage
    :title="isEditing ? problemTitle : $t('Nauja problema')"
    :bar-title
    :head-title="barTitle"
    :lead="isEditing ? undefined : $t('Užregistruok problemą, su kuria susidūrė studentai.')"
    :entity-type="ModelEnum.PROBLEM"
    required-note
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
      <p class="max-w-prose text-sm leading-relaxed text-muted-foreground">
        {{ $t('problems.internal_support.guidance') }}
        <Link :href="route('mySupportRequests.index')" class="text-brand underline underline-offset-4">
          {{ $t('problems.internal_support.link') }}
        </Link>
      </p>
      <FormFieldWrapper
        id="problem-title"
        :label="capitalize($tChoice('entities.problem.title', 1))"
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
      :label="capitalize($tChoice('entities.problem.description', 1))"
      :required="!form.description[otherLocale].trim()"
      :error="form.errors[`description.${activeLocale}`]"
    >
      <TiptapEditor :key="activeLocale" v-model="form.description[activeLocale]" tools="description" html data-testid="problem-description-editor" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="problem-steps-taken"
      :label="capitalize($tChoice('entities.problem.steps_taken', 1))"
      :hint="$t('problems.hints.steps_taken')"
      :error="form.errors[`steps_taken.${activeLocale}`]"
    >
      <TiptapEditor :key="activeLocale" v-model="form.steps_taken[activeLocale]" tools="description" html />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="problem-solution"
      :label="capitalize($tChoice('entities.problem.solution', 1))"
      :hint="$t('problems.hints.solution')"
      :error="form.errors[`solution.${activeLocale}`]"
    >
      <TiptapEditor :key="activeLocale" v-model="form.solution[activeLocale]" tools="description" html />
    </FormFieldWrapper>

    <template #aside>
      <FormPanel :title="$t('Būsena ir priskyrimas')" :icon="CircleDot">
        <TenantSelectField
          id="problem-tenant"
          :model-value="form.tenant_id"
          :tenants
          required
          :error="form.errors.tenant_id"
          @update:model-value="onTenantChanged"
        />

        <FormFieldWrapper
          id="problem-status"
          :label="capitalize($tChoice('entities.problem.status', 1))"
          required
          :error="form.errors.status"
        >
          <FormSegmentedControl
            id="problem-status"
            v-model="form.status"
            stack-on-phone
            tabindex="-1"
            :options="statusOptions"
            :label="capitalize($tChoice('entities.problem.status', 1))"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="problem-responsible-user"
          :label="capitalize($tChoice('entities.problem.responsible_user', 1))"
          :error="form.errors.responsible_user_id"
        >
          <UserSearchField
            input-id="problem-responsible-user"
            :model-value="selectedUser"
            :aria-label="capitalize($tChoice('entities.problem.responsible_user', 1))"
            :placeholder="$t('problems.form.responsible_user_placeholder')"
            :permission="isEditing ? 'problems.update.padalinys' : 'problems.create.padalinys'"
            @update:model-value="handleUserSelect"
          />
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

      <FormPanel :title="$t('Klasifikacija')" :icon="Layers">
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
import { Link, useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ChevronsUpDown, CircleDot, Layers, Trash2 } from '@lucide/vue';
import { capitalize, computed, ref } from 'vue';

import StatusBadge from '@/Components/Patterns/StatusBadge.vue';
import { problemStatuses, statusRoleClasses } from '@/Constants/statusIcons';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import TenantSelectField from '@/Components/AdminForms/TenantSelectField.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import FormPanel from '@/Components/Patterns/FormPanel.vue';
import FormSegmentedControl, { type FormSegmentOption } from '@/Components/Patterns/FormSegmentedControl.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { MultiSelect } from '@/Components/ui/multi-select';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import UserSearchField, { type MemberHit } from '@/Features/Admin/AdminSearch/Components/Select/UserSearchField.vue';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { ModelEnum } from '@/Types/enums';
import { formatDate, todayIso } from '@/Utils/dateTime';

interface Translated {
  lt: string;
  en: string;
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
  if (/^\d{4}-\d{2}-\d{2}/.test(dateValue)) return dateValue.slice(0, 10);
  return formatDate(dateValue) || null;
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
  occurred_at: (source.occurred_at ? formatDateForPicker(source.occurred_at as string) : todayIso()) as string,
  resolved_at: (source.resolved_at ? formatDateForPicker(source.resolved_at as string) : null) as string | null,
  status: (source.status ?? 'open') as string,
  categories: (Array.isArray(source.categories) ? source.categories.map((c: unknown) => (c && typeof c === 'object' && 'id' in c ? c.id : c)) : []) as number[],
  institutions: (Array.isArray(source.institutions) ? source.institutions.map((i: unknown) => (i && typeof i === 'object' && 'id' in i ? i.id : i)) : []) as string[],
});

const form = props.rememberKey ? useForm(props.rememberKey, initial()) : useForm(initial());

const isEditing = computed(() => Boolean(form.id));

const problemTitle = computed(() => form.title.lt || form.title.en || '');
const barTitle = computed(() => (isEditing.value ? (getTranslatedValue(source.title) || $t('Problema')) : $t('Nauja problema')));

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

const statusOptions = computed<FormSegmentOption<string>[]>(() =>
  Object.entries(problemStatuses).map(([value, presentation]) => ({
    value,
    label: $t(presentation.label),
    icon: presentation.icon,
    activeClass: statusRoleClasses[presentation.role],
  })),
);

const onTenantChanged = (tenantId: number | null) => {
  if (tenantId !== form.tenant_id) {
    form.institutions = [];
    selectedInstitutionHits.value = [];
  }
  form.tenant_id = tenantId;
};

const selectedUser = ref<MemberHit | null>(props.initialResponsibleUser ?? null);

function handleUserSelect(user: MemberHit | null) {
  selectedUser.value = user;
  form.responsible_user_id = user?.id ?? null;
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
    is_active: institution.is_active === undefined ? true : Boolean(institution.is_active),
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
