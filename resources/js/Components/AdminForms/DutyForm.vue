<template>
  <FormPage
    :title="isEditing ? dutyTitle : $t('Nauja pareigybė')"
    :bar-title="isEditing ? dutyTitle : undefined"
    :head-title="isEditing ? dutyTitle : $t('Nauja pareigybė')"
    :lead="isEditing ? (duty?.institution?.short_name ?? duty?.institution?.name) : $t('Sukurk naują pareigybę institucijoje')"
    :entity-type="ModelEnum.DUTY"
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
        :label="`${$t('Pavadinimas')} (${activeLocale.toUpperCase()})`"
        required
        :error="form.errors[`name.${activeLocale}`]"
        :hint="$t('forms.helpers.duty_name_inflected_hint')"
      >
        <Input
          id="duty-name"
          v-model="form.name[activeLocale]"
          :placeholder="activeLocale === 'lt' ? $t('Pirmininkas, Koordinatorius…') : 'Chair, Coordinator…'"
        />
        <!-- Inflected preview for Lithuanian -->
        <div v-if="form.name.lt" class="mt-2 text-sm">
          <InflectedDutyName :name="form.name.lt" locale="lt" class="font-medium text-foreground" />
        </div>
      </FormFieldWrapper>

      <!-- Duplicate Duty Warning -->
      <DuplicateDutyWarning
        :matches="duplicateMatches"
        :current-duty-id="duty?.id ?? null"
        class="mt-2"
      />

      <!-- Description -->
      <FormFieldWrapper
        id="duty-description"
        :label="$t('Aprašymas')"
        :hint="$t('Aprašymas rodomas viešame puslapyje prie pareigybės.')"
        :error="form.errors[`description.${activeLocale}`]"
      >
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
      </FormFieldWrapper>
    </template>

    <!-- Aside Slot -->
    <template #aside>
      <FormPanel :title="$t('Institucija ir kontaktai')" :icon="Building2" title-class="text-brand">
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

        <!-- Places to occupy & Contacts grouping -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormFieldWrapper
            id="places_to_occupy"
            :label="$t('Kiek vietų')"
            :error="form.errors.places_to_occupy"
          >
            <NumberField
              id="places_to_occupy"
              v-model="form.places_to_occupy"
              :min="1"
            />
          </FormFieldWrapper>

          <FormFieldWrapper
            id="contacts_grouping"
            :label="$t('Kontaktų grupavimas')"
            :error="form.errors.contacts_grouping"
          >
            <Select v-model="form.contacts_grouping">
              <SelectTrigger id="contacts_grouping">
                <SelectValue :placeholder="$t('forms.placeholders.select_grouping')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">
                  {{ $t('Be grupavimo') }}
                </SelectItem>
                <SelectItem value="study_program">
                  {{ $t('Pagal studijų programą') }}
                </SelectItem>
                <SelectItem value="tenant">
                  {{ $t('Pagal padalinį') }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>
        </div>

        <!-- Categories / Types -->
        <FormFieldWrapper
          v-if="dutyTypes && dutyTypes.length > 0"
          id="duty-types"
          :label="$t('forms.fields.duty_type')"
          :error="form.errors.types"
        >
          <MultiSelect
            id="duty-types"
            v-model="selectedTypes"
            :options="dutyTypes"
            label-field="title"
            value-field="id"
            :placeholder="$t('forms.placeholders.select_category')"
          />
        </FormFieldWrapper>
      </FormPanel>

      <!-- Assignable Tenants (Delegated Seats) -->
      <FormPanel
        v-if="assignableTenants && assignableTenants.length > 0"
        :title="$t('forms.fields.assignable_tenants')"
        :icon="Users"
        title-class="text-brand"
      >
        <div class="flex items-center justify-between">
          <div>
            <h4 class="text-xs font-semibold text-foreground">
              {{ $t('Leisti deleguotas vietas') }}
            </h4>
            <p class="text-xs text-muted-foreground">
              {{ $t('Leisti kitiems padaliniams skirti atstovus į šią pareigybę.') }}
            </p>
          </div>
          <Switch
            :model-value="allowExternal"
            @update:model-value="toggleAllowExternal"
          />
        </div>

        <div v-if="allowExternal || !canEditDuty" class="space-y-3 pt-2">
          <MultiSelect
            v-if="canEditDuty"
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
        </div>
      </FormPanel>

      <!-- Advanced Settings: Ex-officio and Roles -->
      <FormPanel
        v-if="$page.props.auth?.user?.isSuperAdmin || (assignableDuties && assignableDuties.length > 0)"
        :title="$t('Papildomi nustatymai')"
        :icon="SlidersHorizontal"
        title-class="text-brand"
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

        <!-- Administrative Roles (Superadmin only) -->
        <FormFieldWrapper
          v-if="$page.props.auth?.user?.isSuperAdmin"
          id="admin_role"
          :label="`${$t('forms.fields.admin_role')} (superadmin)`"
        >
          <MultiSelect
            id="admin_role"
            v-model="selectedRoles"
            :options="rolesOptions"
            label-field="label"
            value-field="value"
            :placeholder="$t('forms.placeholders.no_role')"
          />
        </FormFieldWrapper>
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
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, ChevronsUpDown, SlidersHorizontal, Trash2, Users } from 'lucide-vue-next';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel } from '@/Components/Patterns';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { NumberField } from '@/Components/ui/number-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { MultiSelect } from '@/Components/ui/multi-select';
import InstitutionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/InstitutionSelectDialog.vue';
import CollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import DuplicateDutyWarning from '@/Components/AdminForms/DuplicateDutyWarning.vue';
import { useDuplicateDutyCheck } from '@/Composables/useDuplicateDutyCheck';
import { ModelEnum } from '@/Types/enums';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

interface AssignableTenantRow {
  tenant_id: number;
  quota: number | null;
}

interface DutyPropType {
  id?: string;
  name?: { lt?: string; en?: string } | string;
  description?: { lt?: string; en?: string } | string;
  email?: string | null;
  institution_id?: string | null;
  places_to_occupy?: number;
  contacts_grouping?: string;
  types?: Array<{ id: number; title?: string }>;
  roles?: Array<{ id: number; name?: string }>;
  ex_officio_target_duties?: Array<{ id: string; name?: string }>;
  assignable_tenants?: Array<{ id: number; shortname?: string; pivot?: { quota?: number | null } }>;
  created_at?: string | null;
  updated_at?: string | null;
  institution?: { id?: string; name?: string; short_name?: string } | null;
}

const props = withDefaults(defineProps<{
  duty?: DutyPropType;
  dutyTypes?: App.Entities.Type[];
  assignableInstitutions?: App.Entities.Institution[];
  roles?: App.Entities.Role[];
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
    places_to_occupy: 1,
    contacts_grouping: 'none',
    types: [],
    roles: [],
    ex_officio_target_duties: [],
    assignable_tenants: [],
  }),
  dutyTypes: () => [],
  assignableInstitutions: () => [],
  roles: () => [],
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

const dutyTitle = computed(() => {
  if (typeof props.duty?.name === 'string') return props.duty.name;
  return props.duty?.name?.lt || props.duty?.name?.en || '';
});

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
};

// Form Initialization
const form = useForm({
  name: {
    lt: (typeof props.duty?.name === 'object' ? props.duty?.name?.lt : props.duty?.name) ?? '',
    en: (typeof props.duty?.name === 'object' ? props.duty?.name?.en : '') ?? '',
  },
  email: props.duty?.email ?? '',
  institution_id: props.duty?.institution_id ?? null,
  places_to_occupy: props.duty?.places_to_occupy ?? 1,
  contacts_grouping: props.duty?.contacts_grouping ?? 'none',
  description: {
    lt: (typeof props.duty?.description === 'object' ? props.duty?.description?.lt : props.duty?.description) ?? '',
    en: (typeof props.duty?.description === 'object' ? props.duty?.description?.en : '') ?? '',
  },
  types: (props.duty?.types?.map((t: { id: number }) => t.id) ?? []) as number[],
  roles: (props.duty?.roles?.map((r: { id: number }) => r.id) ?? []) as number[],
  ex_officio_target_duty_ids: (props.duty?.ex_officio_target_duties?.map((d: { id: string }) => d.id) ?? []) as string[],
  assignable_tenants: (props.duty?.assignable_tenants?.map((t: { id: number; pivot?: { quota?: number | null } }) => ({
    tenant_id: t.id,
    quota: t.pivot?.quota ?? null,
  })) ?? []) as AssignableTenantRow[],
});

// Missing translation counts
const missingLocaleCounts = computed(() => ({
  lt: !form.name.lt ? 1 : 0,
  en: !form.name.en ? 1 : 0,
}));

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
  return [{
    id: String(inst.id),
    title: inst.name,
    subtitle: inst.tenant?.shortname ?? '',
    avatar: null,
    badge: inst.tenant?.shortname ?? null,
    entityType: 'institution',
    raw: inst,
  }];
});

const onInstitutionConfirm = (hits: NormalizedSearchHit[]) => {
  if (hits.length > 0) {
    form.institution_id = hits[0].id;
  }
};

// Categories / Types
const selectedTypes = computed({
  get: () => form.types,
  set: (val: number[]) => {
    form.types = val;
  },
});

// Roles (Superadmin only)
const rolesOptions = computed(() =>
  (props.roles ?? []).map(r => ({ label: r.name, value: r.id })),
);
const selectedRoles = computed({
  get: () => form.roles,
  set: (val: number[]) => {
    form.roles = val;
  },
});

// Ex-officio Target Duties
const exOfficioDialogOpen = ref(false);
const exOfficioBaseFilterBy = computed(() => {
  if (!props.duty?.id) return '';
  return `id:!=${props.duty.id}`;
});
const exOfficioDisabledIds = computed(() => new Set(props.duty?.id ? [props.duty.id] : []));

const selectedExOfficioDuties = computed(() => {
  const ids = new Set(form.ex_officio_target_duty_ids.map(String));
  return (props.assignableDuties ?? []).filter(d => ids.has(String(d.id)));
});

const exOfficioInitialHits = computed<NormalizedSearchHit[]>(() =>
  selectedExOfficioDuties.value.map(d => ({
    id: String(d.id),
    title: d.name,
    subtitle: d.institution?.name ?? '',
    avatar: null,
    badge: d.institution?.short_name ?? null,
    entityType: 'duty',
    raw: d,
  })),
);

const onExOfficioConfirm = (hits: NormalizedSearchHit[]) => {
  form.ex_officio_target_duty_ids = hits.map(h => h.id);
};

// Assignable Tenants (Delegated Seats)
const allowExternal = ref((form.assignable_tenants?.length ?? 0) > 0);

const toggleAllowExternal = (val: boolean) => {
  allowExternal.value = val;
  if (!val) {
    form.assignable_tenants = [];
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

const removeTenantRow = (tenantId: number) => {
  form.assignable_tenants = form.assignable_tenants.filter(r => r.tenant_id !== tenantId);
};
</script>
