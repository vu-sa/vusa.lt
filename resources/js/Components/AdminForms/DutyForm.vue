<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <Alert v-if="!canEditDuty" class="mb-4">
      <AlertDescription>
        {{ $t('forms.fields.cross_tenant_duty_notice') }}
      </AlertDescription>
    </Alert>
    <template v-if="canEditDuty">
      <FormElement>
        <template #title>
          {{ $t("forms.context.main_info") }}
        </template>
        <template #description>
          Pareigos rodymas pagal žmogaus įvardį, jeigu įvardyje pirmas žodis yra:
          <li>
            jis (he) - <strong>{{ changeDutyNameEndings(null, duty.name.lt, $page.props.app.locale, "jis/jo", false)
            }}</strong>
          </li>
          <li>
            ji (she) - <strong>{{ changeDutyNameEndings(null, duty.name.lt, $page.props.app.locale, "ji/jos", false)
            }}</strong>
          </li>
          <li>
            jie (they) - <strong>{{ changeDutyNameEndings(null, duty.name.lt, $page.props.app.locale, "jie/jų", false)
            }}</strong>
          </li>
        </template>
        <FormFieldWrapper id="name" :label="$t('forms.fields.title')" :error="form.errors.name">
          <MultiLocaleInput v-model:input="form.name" />
        </FormFieldWrapper>

        <FormFieldWrapper id="email" :label="$t('forms.fields.email')" :error="form.errors.email">
          <Input id="email" v-model="form.email" placeholder="vusa@vusa.lt" />
        </FormFieldWrapper>

        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper id="institution_id" :label="$t('forms.fields.institution')" :error="form.errors.institution_id">
            <InstitutionSelectDialog
              v-model:open="institutionDialogOpen"
              :institutions="assignableInstitutions as unknown as InstitutionOption[]"
              :initial-hits="institutionInitialHits"
              @confirm="onInstitutionConfirm"
            >
              <template #trigger>
                <Button type="button" variant="outline" class="w-full justify-between font-normal">
                  <span class="truncate" :class="{ 'text-muted-foreground': !selectedInstitution }">
                    {{ selectedInstitution?.name ?? $t('Pasirink instituciją pagal pavadinimą...') }}
                  </span>
                  <span class="flex shrink-0 items-center gap-2">
                    <Badge v-if="selectedInstitution?.tenant?.shortname" variant="secondary" class="text-xs">
                      {{ selectedInstitution.tenant.shortname }}
                    </Badge>
                    <ChevronsUpDown class="size-4 opacity-50" />
                  </span>
                </Button>
              </template>
            </InstitutionSelectDialog>
          </FormFieldWrapper>

          <FormFieldWrapper id="places_to_occupy" :label="$t('forms.fields.duty_people_count')" :error="form.errors.places_to_occupy">
            <NumberField id="places_to_occupy" v-model="form.places_to_occupy" :min="0" />
          </FormFieldWrapper>
        </div>

        <FormFieldWrapper id="contacts_grouping" :label="$t('forms.fields.contacts_grouping')" :error="form.errors.contacts_grouping">
          <Select v-model="form.contacts_grouping">
            <SelectTrigger>
              <SelectValue :placeholder="$t('forms.placeholders.select_grouping')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">
                Be grupavimo
              </SelectItem>
              <SelectItem value="study_program">
                Pagal studijų programą
              </SelectItem>
              <SelectItem value="tenant">
                Pagal padalinį
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
      </FormElement>
      <FormElement>
        <template #title>
          {{ $t("forms.fields.description") }}
        </template>
        <template #description>
          Aprašymas yra rodomas vusa.lt puslapyje prie pareigybės
        </template>
        <div class="space-y-2">
          <div class="inline-flex items-center gap-2">
            <Label for="description">Aprašymas</Label>
            <SimpleLocaleButton v-model:locale="locale" />
          </div>
          <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" :html="true" />
          <TiptapEditor v-else v-model="form.description.en" preset="full" :html="true" />
          <p v-if="form.errors.description" class="text-xs text-red-600 dark:text-red-400">
            {{ form.errors.description }}
          </p>
        </div>
      </FormElement>

      <FormElement v-if="false">
        <template #title>
          {{ $t('Skyrimas ir atsakomybės') }}
        </template>
        <template #description>
          {{ $t('Nurodykite, kaip užimama pareigybė ir kokios jos atsakomybės. Tušti laukai paveldimi iš institucijos.') }}
        </template>

        <FormFieldWrapper id="selection_method" :label="$t('Skyrimo būdas')" :error="form.errors.selection_method">
          <Select v-model="form.selection_method">
            <SelectTrigger>
              <SelectValue :placeholder="$t('Paveldima iš institucijos')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="option in selectionMethodOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper id="appointed_by" :label="$t('Skiria')" :error="form.errors.appointed_by">
            <MultiLocaleInput v-model:input="form.appointed_by" />
          </FormFieldWrapper>
          <FormFieldWrapper id="term_length" :label="$t('Kadencija')" :error="form.errors.term_length">
            <MultiLocaleInput v-model:input="form.term_length" />
          </FormFieldWrapper>
        </div>

        <div class="space-y-2">
          <div class="inline-flex items-center gap-2">
            <Label for="responsibilities">{{ $t('Atsakomybės') }}</Label>
            <SimpleLocaleButton v-model:locale="locale" />
          </div>
          <Textarea
            v-if="locale === 'lt'"
            id="responsibilities"
            v-model="form.responsibilities.lt"
            :rows="4"
            :placeholder="$t('Kiekviena atsakomybė nurodoma naujoje eilutėje')"
          />
          <Textarea
            v-else
            id="responsibilities"
            v-model="form.responsibilities.en"
            :rows="4"
            :placeholder="$t('Kiekviena atsakomybė nurodoma naujoje eilutėje')"
          />
          <p v-if="form.errors.responsibilities" class="text-xs text-red-600 dark:text-red-400">
            {{ form.errors.responsibilities }}
          </p>
        </div>
      </FormElement>
    </template>

    <!-- Owning-tenant members — only when canEditDuty -->
    <FormElement v-if="canEditDuty">
      <template #title>
        Asmenys
      </template>
      <template #description>
        <p class="mb-4">
          Pareigybę gali užimti daug naudotojų.
        </p>
        <p>
          Jeigu sąraše nėra asmens, kuris užima pareigybę, šį asmenį reikia
          sukurti.
        </p>
      </template>
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2">
          <Label><strong>{{ $t("Nariai") }}</strong></Label>
          <Button as="a" variant="link" size="xs" target="_blank" :href="route('users.create')">
            <IFluentAdd24Filled />
            Sukurti naują asmenį
          </Button>
        </div>
        <div class="inline-flex items-center gap-2 text-sm">
          <Switch id="show-all-users" v-model="showAllUsers" />
          <Label for="show-all-users" class="cursor-pointer font-normal">{{ $t('forms.fields.show_all_users') }}</Label>
        </div>
        <p v-if="!showAllUsers" class="text-xs text-muted-foreground">
          {{ $t('forms.fields.recent_users_only_hint', { shown: recentUsersCount, total: assignableUsersTotal }) }}
        </p>
        <TransferList v-model="form.current_users" :options="owningTenantUserOptions">
          <template #source-label="{ option }">
            <span class="inline-flex items-center gap-2">
              {{ option.label }}
              <a
                target="_blank"
                :href="route('users.edit', option.value)"
                class="inline-flex size-5 items-center justify-center rounded text-muted-foreground hover:bg-accent hover:text-foreground"
                @click.stop
              >
                <IconEdit class="size-3.5" />
              </a>
            </span>
          </template>
          <template #target-label="{ option }">
            <div class="flex items-center gap-2">
              <UserAvatar :size="24" :user="option.user" />
              <span class="inline-flex items-center gap-2">
                {{ option.label }}
                <a
                  target="_blank"
                  :href="route('users.edit', option.value)"
                  class="inline-flex size-5 items-center justify-center rounded text-muted-foreground hover:bg-accent hover:text-foreground"
                >
                  <IconEye class="size-3.5" />
                </a>
              </span>
            </div>
          </template>
        </TransferList>
      </div>

      <!-- Current members list with cross-tenant badges -->
      <div v-if="duty.current_users && duty.current_users.length > 0" class="mt-4">
        <div class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
          Dabartiniai nariai:
        </div>
        <div class="space-y-2">
          <div v-for="user in duty.current_users" :key="user.id"
            class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <UserAvatar :user :size="32" />
              <div>
                <div class="font-medium inline-flex items-center gap-2">
                  <a :href="route('users.edit', user.id)" target="_blank" class="hover:underline">{{ user.name }}</a>
                  <Badge v-if="isExOfficioUser(user)" variant="outline" class="text-xs">
                    ex-officio
                  </Badge>
                  <Badge v-if="getCrossTenantLabel(user)" variant="secondary" class="text-xs">
                    {{ getCrossTenantLabel(user) }}
                  </Badge>
                </div>
              </div>
            </div>
            <Button
              v-if="getUserDutiableId(user)"
              as="a"
              variant="link"
              size="xs"
              target="_blank"
              :href="route('dutiables.edit', { dutiable: getUserDutiableId(user) })"
            >
              <IconEdit />
              Redaguoti pareigybės laikotarpį
            </Button>
          </div>
        </div>
      </div>
    </FormElement>

    <template v-if="canEditDuty">
      <FormElement>
        <template #title>
          Papildoma informacija
        </template>
        <template #description>
          <div class="flex flex-col gap-2">
            <p>
              <strong>Pareigybės tipas</strong> reikalingas tam, kad tam tikrais
              atvejais, nariai būtų rodomi viešame studentų atstovybės
              puslapyje. Pavyzdžiui, studentų atstovo tipui priklausantys
              asmenys rodomi prie institucijos kontaktų.
            </p>
            <p>
              <strong>Administracinė vusa.lt rolė </strong> leidžia
              registruotiems naudotojams atlikti jiems priskirtus veiksmus
              vidiniame mano.vusa.lt tinklalapyje
            </p>
          </div>
        </template>
        <FormFieldWrapper id="types" :label="$t('forms.fields.duty_type')" :error="form.errors.types">
          <MultiSelect v-model="selectedTypes" :options="dutyTypes" label-field="title" value-field="id"
            :placeholder="$t('forms.placeholders.select_category')" />
        </FormFieldWrapper>

        <FormFieldWrapper id="roles" :label="$t('forms.fields.admin_role')" :error="form.errors.roles">
          <MultiSelect v-model="selectedRoles" :options="rolesOptions" label-field="label" value-field="value"
            :disabled="!$page.props.auth?.user.isSuperAdmin" :placeholder="$t('forms.placeholders.no_role')" />
        </FormFieldWrapper>

        <FormFieldWrapper id="ex_officio_target_duty_ids" :label="$t('forms.fields.ex_officio_duties')" :error="form.errors.ex_officio_target_duty_ids">
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
            :search-placeholder="$t('Ieškoti pareigų pagal pavadinimą...')"
            :empty-message="$t('Pareigų nerasta')"
            @confirm="onExOfficioConfirm"
          >
            <template #trigger>
              <Button type="button" variant="outline" class="w-full justify-between font-normal">
                <span class="truncate" :class="{ 'text-muted-foreground': selectedExOfficioDuties.length === 0 }">
                  {{ selectedExOfficioDuties.length > 0
                    ? selectedExOfficioDuties.map(d => d.name).join(', ')
                    : $t('forms.fields.ex_officio_duties') }}
                </span>
                <span class="flex shrink-0 items-center gap-2">
                  <Badge v-if="selectedExOfficioDuties.length > 0" variant="secondary" class="text-xs">
                    {{ selectedExOfficioDuties.length }}
                  </Badge>
                  <ChevronsUpDown class="size-4 opacity-50" />
                </span>
              </Button>
            </template>
          </CollectionSelectDialog>
        </FormFieldWrapper>
      </FormElement>
    </template>

    <!-- Assignable tenants section — owning admin edits config + reps; cross-tenant admin only sees their row -->
    <FormElement>
      <template #title>
        {{ $t("forms.fields.assignable_tenants") }}
      </template>
      <template #description>
        <p>
          Pareigybė priklauso vienam padaliniui, bet kiti padaliniai gali skirti
          į ją savo narius (su kvota).
        </p>
      </template>

      <!-- Toggle only shown to owning admin -->
      <FormFieldWrapper v-if="canEditDuty" id="allow_external_dutiables" :label="$t('forms.fields.allow_external_dutiables')">
        <Switch :model-value="allowExternal" @update:model-value="toggleAllowExternal" />
      </FormFieldWrapper>

      <div v-if="allowExternal || !canEditDuty" class="space-y-4">
        <!-- Owning admin: configure which tenants can assign reps + their quotas -->
        <div v-if="canEditDuty" class="space-y-3">
          <div
            v-for="(row, index) in visibleAssignableTenantRows"
            :key="row.tenant_id ?? index"
            class="flex items-end gap-3"
          >
            <FormFieldWrapper :id="`assignable_tenant_${index}`" :label="$t('forms.fields.tenant')" class="flex-1">
              <SingleSelect
                :model-value="assignableTenants.find(t => t.id === row.tenant_id) ?? null"
                :options="availableTenantOptions(index)"
                label-field="shortname"
                value-field="id"
                @update:model-value="(val: AssignableTenantOption | null) => row.tenant_id = val?.id ?? null"
              />
            </FormFieldWrapper>
            <FormFieldWrapper :id="`assignable_tenant_quota_${index}`" :label="$t('forms.fields.tenant_quota')" :hint="$t('forms.fields.tenant_quota_hint')" class="w-32">
              <NumberField v-model="row.quota" :min="1" />
            </FormFieldWrapper>
            <Button type="button" variant="ghost" size="icon" @click="removeAssignableTenant(index)">
              <IFluentDelete24Regular />
            </Button>
          </div>
        </div>

        <!-- User filter toggle — only shown to cross-tenant admins (owning admins have it in the members section above) -->
        <template v-if="!canEditDuty">
          <div class="inline-flex items-center gap-2 text-sm">
            <Switch id="show-all-users-tenant" v-model="showAllUsers" />
            <Label for="show-all-users-tenant" class="cursor-pointer font-normal">{{ $t('forms.fields.show_all_users') }}</Label>
          </div>
          <p v-if="!showAllUsers" class="text-xs text-muted-foreground">
            {{ $t('forms.fields.recent_users_only_hint', { shown: recentUsersCount, total: assignableUsersTotal }) }}
          </p>
        </template>

        <!-- Per-tenant rep pickers in collapsible accordion sections -->
        <Accordion v-if="visibleAssignableTenantRows.length > 0" type="multiple" :default-value="defaultOpenTenantValues">
          <AccordionItem
            v-for="row in visibleAssignableTenantRows"
            :key="formIndexFor(row)"
            :value="String(row.tenant_id ?? `new-${formIndexFor(row)}`)"
          >
            <AccordionTrigger>
              <span class="flex w-full items-center justify-between gap-3 pr-2">
                <span>{{ tenantShortname(row) }}</span>
                <Badge variant="outline">
                  {{ selectedTenantUserIds[formIndexFor(row)]?.length ?? 0 }} / {{ row.quota ?? '∞' }}
                </Badge>
              </span>
            </AccordionTrigger>
            <AccordionContent>
              <div v-if="!canEditDuty" class="mb-2 text-sm text-gray-500">
                {{ $t('forms.fields.tenant_quota') }}: {{ row.quota ?? '∞' }}
              </div>
              <TransferList
                :model-value="selectedTenantUserIds[formIndexFor(row)] ?? []"
                :options="tenantTransferListOptions(formIndexFor(row))"
                @update:model-value="(next: string[]) => applyTenantSelection(formIndexFor(row), next, row.quota)"
              >
                <template #target-label="{ option }">
                  <span class="flex items-center gap-2">
                    <UserAvatar :size="24" :user="(option as any).user" />
                    <span>{{ option.label }}</span>
                  </span>
                </template>
              </TransferList>
              <p v-if="tenantQuotaReached(row)" class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                {{ $t('forms.fields.quota_reached') }}
              </p>
              <p v-if="(form.errors as any)[`assignable_tenants.${formIndexFor(row)}.user_ids`]" class="mt-1 text-xs text-red-600 dark:text-red-400">
                {{ (form.errors as any)[`assignable_tenants.${formIndexFor(row)}.user_ids`] }}
              </p>
            </AccordionContent>
          </AccordionItem>
        </Accordion>

        <Button v-if="canEditDuty" type="button" variant="outline" size="sm" @click="addAssignableTenant">
          <IFluentAdd24Filled />
          {{ $t("forms.fields.add_assignable_tenant") }}
        </Button>

        <p v-if="form.errors.assignable_tenants" class="text-xs text-red-600 dark:text-red-400">
          {{ form.errors.assignable_tenants }}
        </p>
      </div>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronsUpDown } from 'lucide-vue-next';

import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';
import UserAvatar from '../Avatars/UserAvatar.vue';
import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import IconEdit from '~icons/fluent/edit16-filled';
import IconEye from '~icons/fluent/eye16-regular';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';
import { NumberField } from '@/Components/ui/number-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { SingleSelect } from '@/Components/ui/single-select';
import { CollectionSelectDialog, InstitutionSelectDialog, type InstitutionOption } from '@/Features/Admin/AdminSearch/Components/Select';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import { TransferList } from '@/Components/ui/transfer-list';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';
import { changeDutyNameEndings } from '@/Utils/String';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';

interface AssignableTenantOption { id: number; shortname: string; type?: string }
interface AssignableDutyOption {
  id: string;
  name: string;
  institution?: { id: string; name: string; short_name?: string | null; tenant?: { id: number; shortname: string } | null } | null;
}
interface AssignableTenantRow { tenant_id: number | null; quota: number | null; user_ids: string[] }
interface UserWithPivot extends App.Entities.User {
  pivot?: { id?: string | null; tenant_id?: number | null; via_dutiable_id?: string | null };
}
interface AssignableUserOption {
  id: string;
  name: string;
  profile_photo_path?: string | null;
  is_recent: boolean;
}

const props = withDefaults(defineProps<{
  duty: App.Entities.Duty;
  dutyTypes: App.Entities.Type[];
  assignableUsers: AssignableUserOption[];
  roles: App.Entities.Role[];
  assignableInstitutions: App.Entities.Institution[];
  assignableTenants: AssignableTenantOption[];
  assignableDuties: AssignableDutyOption[];
  /** Map of tenantId → array of currently-active user ids for that tenant. */
  assignableTenantUsers?: Record<number, string[]>;
  /** Tenant ids the acting admin is managing (empty = owning admin). */
  actingAssignableTenantIds?: number[];
  /** False when opened by a cross-tenant admin — only the assignable-tenants section is editable. */
  canEditDuty?: boolean;
  rememberKey?: string;
}>(), {
  canEditDuty: true,
  assignableTenantUsers: () => ({}),
  actingAssignableTenantIds: () => [],
});

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const locale = ref('lt');
const showAllUsers = ref(false);

const initialAssignableTenantRows = (props.duty.assignable_tenants ?? []).map((t): AssignableTenantRow => ({
  tenant_id: t.id,
  quota: (t as App.Entities.Tenant & { pivot?: { quota?: number | null } }).pivot?.quota ?? null,
  user_ids: props.assignableTenantUsers[t.id] ?? [],
}));

const initialFormData = {
  ...(props.duty as any),
  roles: props.duty.roles?.map(role => role.id) ?? [],
  types: props.duty.types?.map(type => type.id) ?? [],
  current_users: (props.duty.current_users as UserWithPivot[] | undefined ?? [])
    .filter(u => u.pivot?.tenant_id == null && !u.pivot?.via_dutiable_id)
    .map(u => u.id),
  ex_officio_target_duty_ids: props.duty.ex_officio_target_duties?.map(d => d.id) ?? [],
  assignable_tenants: initialAssignableTenantRows,
  selection_method: (props.duty as any).selection_method ?? null,
  appointed_by: (props.duty as any).appointed_by ?? { lt: '', en: '' },
  term_length: (props.duty as any).term_length ?? { lt: '', en: '' },
  responsibilities: (props.duty as any).responsibilities ?? { lt: '', en: '' },
};

const selectionMethodOptions = [
  { value: 'elected', label: $t('Renkama') },
  { value: 'delegated', label: $t('Deleguojama') },
  { value: 'appointed', label: $t('Skiriama') },
];

const form = props.rememberKey
  ? useForm(props.rememberKey, initialFormData)
  : useForm(initialFormData);

// Reactive selected user ids per assignable-tenant row (string[] per row, mirrors form.assignable_tenants[i].user_ids)
const selectedTenantUserIds = ref<string[][]>(
  initialAssignableTenantRows.map(row => row.user_ids.slice()),
);

// Keep form.assignable_tenants[i].user_ids in sync with selectedTenantUserIds
watch(selectedTenantUserIds, (val) => {
  (form.assignable_tenants as AssignableTenantRow[]).forEach((row, i) => {
    row.user_ids = (val[i] ?? []).slice();
  });
}, { deep: true });

const allowExternal = ref<boolean>((form.assignable_tenants as AssignableTenantRow[]).length > 0);

function toggleAllowExternal(val: boolean) {
  allowExternal.value = val;
  if (!val) {
    form.assignable_tenants = [];
    selectedTenantUserIds.value = [];
  }
}

function addAssignableTenant() {
  (form.assignable_tenants as AssignableTenantRow[]).push({ tenant_id: null, quota: null, user_ids: [] });
  selectedTenantUserIds.value.push([]);
}

function removeAssignableTenant(index: number) {
  (form.assignable_tenants as AssignableTenantRow[]).splice(index, 1);
  selectedTenantUserIds.value.splice(index, 1);
}

function availableTenantOptions(index: number): AssignableTenantOption[] {
  const usedIds = (form.assignable_tenants as AssignableTenantRow[])
    .filter((_, i) => i !== index)
    .map(r => r.tenant_id);
  return props.assignableTenants.filter(t => !usedIds.includes(t.id));
}

// For cross-tenant admins: only show their rows; for owning admin: all rows.
const visibleAssignableTenantRows = computed<AssignableTenantRow[]>(() => {
  const rows = form.assignable_tenants as AssignableTenantRow[];
  if (props.canEditDuty || props.actingAssignableTenantIds.length === 0) {
    return rows;
  }
  return rows.filter(r => r.tenant_id !== null && props.actingAssignableTenantIds.includes(r.tenant_id));
});

// Map visible row back to the form index (for error key lookup).
function formIndexFor(row: AssignableTenantRow): number {
  return (form.assignable_tenants as AssignableTenantRow[]).indexOf(row);
}

function tenantQuotaReached(row: AssignableTenantRow): boolean {
  if (row.quota === null) {
    return false;
  }
  const idx = (form.assignable_tenants as AssignableTenantRow[]).indexOf(row);
  return (selectedTenantUserIds.value[idx]?.length ?? 0) >= row.quota;
}

const selectedExOfficioDuties = ref<AssignableDutyOption[]>(
  props.assignableDuties.filter(d =>
    (form.ex_officio_target_duty_ids as string[]).includes(d.id),
  ),
);

watch(selectedExOfficioDuties, (val) => {
  form.ex_officio_target_duty_ids = val.map(d => d.id);
}, { deep: true });

const exOfficioDialogOpen = ref(false);

/** Build a normalized duty hit from an assignable-duty option (for pre-selection). */
function dutyOptionToHit(option: AssignableDutyOption): NormalizedSearchHit {
  return normalizeHit('duties', {
    id: option.id,
    name_lt: option.name,
    name_en: option.name,
    institution_name_lt: option.institution?.name,
    institution_name_en: option.institution?.name,
    tenant_shortname: option.institution?.tenant?.shortname,
  });
}

// Scope the duties search to the assignable set's tenants (reproduces the
// server-side assignable scope); the current duty can't target itself.
const exOfficioBaseFilterBy = computed(() => {
  const tenantIds = [
    ...new Set(props.assignableDuties.map(d => d.institution?.tenant?.id).filter((id): id is number => id != null)),
  ];
  return tenantIds.length > 0 ? `tenant_ids:[${tenantIds.join(',')}]` : undefined;
});

const exOfficioDisabledIds = computed(() => new Set([`duties-${props.duty.id}`]));

const exOfficioInitialHits = computed(() => selectedExOfficioDuties.value.map(dutyOptionToHit));

function onExOfficioConfirm(hits: NormalizedSearchHit[]) {
  selectedExOfficioDuties.value = hits.map(hit =>
    props.assignableDuties.find(d => d.id === hit.recordId) ?? { id: hit.recordId, name: hit.title },
  );
}

// TransferList options for owning-tenant reps (exclude users already in a cross-tenant slot)
const crossTenantUserIds = computed(() => selectedTenantUserIds.value.flat());

const recentUsersCount = computed(() => props.assignableUsers.filter(u => u.is_recent).length);
const assignableUsersTotal = computed(() => props.assignableUsers.length);

const owningTenantUserOptions = computed(() => {
  const selected = new Set((form.current_users as string[] | undefined) ?? []);
  return props.assignableUsers
    .filter(u => !crossTenantUserIds.value.includes(u.id))
    .filter(u => showAllUsers.value || u.is_recent || selected.has(u.id))
    .map(user => ({ label: user.name, value: user.id, user }));
});

function tenantShortname(row: AssignableTenantRow): string {
  if (row.tenant_id === null) { return '...'; }
  return props.assignableTenants.find(t => t.id === row.tenant_id)?.shortname ?? String(row.tenant_id);
}

function tenantTransferListOptions(rowIndex: number) {
  const otherRowIds = new Set<string>();
  selectedTenantUserIds.value.forEach((ids, i) => {
    if (i !== rowIndex) { ids.forEach(id => otherRowIds.add(id)); }
  });
  const owningIds = new Set<string>((form.current_users as string[] | undefined) ?? []);
  const selectedHere = new Set<string>(selectedTenantUserIds.value[rowIndex] ?? []);
  return props.assignableUsers
    .filter(u => !otherRowIds.has(u.id) && !owningIds.has(u.id))
    .filter(u => showAllUsers.value || u.is_recent || selectedHere.has(u.id))
    .map(u => ({ value: u.id, label: u.name, user: u }));
}

function applyTenantSelection(index: number, next: string[], quota: number | null) {
  const current = selectedTenantUserIds.value[index] ?? [];
  if (quota !== null && next.length > quota && next.length > current.length) {
    return;
  }
  selectedTenantUserIds.value[index] = next;
}

const defaultOpenTenantValues = computed<string[]>(() => {
  const rows = visibleAssignableTenantRows.value;
  if (!props.canEditDuty && rows.length === 1) {
    const row = rows[0];
    return [String(row.tenant_id ?? `new-${formIndexFor(row)}`)];
  }
  return [];
});

const rolesOptions = props.roles.map(role => ({
  label: role.name,
  value: role.id,
}));

const selectedTypes = computed({
  get: () => props.dutyTypes.filter(dt => form.types?.includes(dt.id)),
  set: (items: App.Entities.Type[]) => {
    form.types = items.map(item => item.id);
  },
});

const selectedRoles = computed({
  get: () => rolesOptions.filter(opt => form.roles?.includes(opt.value)),
  set: (items: { label: string; value: number }[]) => {
    form.roles = items.map(item => item.value);
  },
});

const selectedInstitution = computed({
  get: () => props.assignableInstitutions.find(i => i.id === form.institution_id) ?? null,
  set: (val: App.Entities.Institution | null) => {
    form.institution_id = val?.id ?? null;
  },
});

const institutionDialogOpen = ref(false);

const institutionInitialHits = computed<NormalizedSearchHit[]>(() => {
  const current = selectedInstitution.value as (App.Entities.Institution & { tenant?: { shortname?: string } }) | null;
  if (!current) {
    return [];
  }
  return [normalizeHit('institutions', {
    id: current.id,
    name_lt: current.name,
    name_en: current.name,
    tenant_shortname: current.tenant?.shortname,
  })];
});

function onInstitutionConfirm(hits: NormalizedSearchHit[]) {
  form.institution_id = hits[0]?.recordId ?? null;
}

const isExOfficioUser = (user: App.Entities.User) => !!(user as UserWithPivot).pivot?.via_dutiable_id;

const getUserDutiableId = (user: UserWithPivot) => user.pivot?.id || null;

/** Returns the tenant shortname badge for cross-tenant reps, or null for owning-tenant reps. */
function getCrossTenantLabel(user: App.Entities.User): string | null {
  const pivotTenantId = (user as UserWithPivot).pivot?.tenant_id;
  if (!pivotTenantId) {
    return null;
  }
  const tenant = props.assignableTenants.find(t => t.id === pivotTenantId);
  return tenant?.shortname ?? String(pivotTenantId);
}
</script>
