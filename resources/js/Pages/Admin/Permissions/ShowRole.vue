<template>
  <RecordPage
    v-model:section="section"
    :title="role.name"
    :entity-type="ModelEnum.ROLE"
    :facts
    :sections
    :primary-action
    :overflow-actions
    actions-beside-title
    @action="handleAction"
  >
    <template #permissions>
      <RolePermissionForms v-if="can.update" :role :all-available-permissions :baseline-access :retired-permissions model-route="roles.update" />
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('Neturi teisės keisti šios rolės teisių.') }}
      </p>
    </template>

    <template #duties>
      <OverviewSection variant="home" :title="$t('Priskirtos pareigybės')" :icon="Briefcase" class="max-w-4xl">
        <template v-if="can.update" #actions>
          <Button size="sm" @click="saveDuties">
            {{ $t('Išsaugoti') }}
          </Button>
        </template>
        <p class="text-xs text-muted-foreground">
          {{ $t('Rolė taikoma šias pareigybes einantiems nariams.') }}
        </p>
        <TransferList v-if="can.update" v-model="dutyIds" :options="dutyOptions" />
        <ul v-else class="divide-y divide-border">
          <li v-for="duty in role.duties ?? []" :key="duty.id" class="py-3 text-sm font-medium">
            <InflectedDutyName :name="duty.name" />
          </li>
          <li v-if="!role.duties?.length" class="py-5 text-sm text-muted-foreground">
            {{ $t('Pareigybių nepriskirta.') }}
          </li>
        </ul>
      </OverviewSection>
    </template>

    <template #types>
      <OverviewSection variant="home" :title="$t('Priskiriami tipai')" :icon="Tags" class="max-w-4xl">
        <template v-if="can.update" #actions>
          <Button size="sm" @click="saveAttachableTypes">
            {{ $t('Išsaugoti') }}
          </Button>
        </template>
        <p class="text-xs text-muted-foreground">
          {{ $t('Ši rolė gali būti priskiriama tik pasirinktiems tipams.') }}
        </p>
        <TransferList v-if="can.update" v-model="attachableTypeIds" :options="typeOptions" />
        <ul v-else class="divide-y divide-border">
          <li v-for="type in attachedTypes" :key="type.id" class="py-3 text-sm font-medium">
            {{ localized(type.title) }}
          </li>
          <li v-if="!attachedTypes.length" class="py-5 text-sm text-muted-foreground">
            {{ $t('Tipų nepriskirta.') }}
          </li>
        </ul>
      </OverviewSection>
    </template>
  </RecordPage>

  <ConfirmDialog
    v-model:open="deleteOpen"
    :title="$t('Šalinti rolę?')"
    :description="$t('Rolė bus pašalinta iš sistemos ir nebebus prieinama pareigybėms.')"
    :confirm-label="$t('Šalinti')"
    destructive
    @confirm="router.delete(route('roles.destroy', role.id))"
  />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';
import { Briefcase, Edit, Tags, Trash2 } from 'lucide-vue-next';

import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import RolePermissionForms from '@/Components/AdminForms/RolePermissionForms.vue';
import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { ConfirmDialog, OverviewSection } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { TransferList } from '@/Components/ui/transfer-list';
import { ModelEnum } from '@/Types/enums';

type Translation = string | { lt?: string; en?: string };
interface Duty { id: string; name: string }
interface RoleType { id: string; title: Translation }

const props = defineProps<{
  role: App.Entities.Role & { duties?: Duty[]; attachable_types?: string[] };
  tenantsWithDuties: Array<{ institutions?: Array<{ duties?: Duty[] }> }>;
  allTypes: RoleType[];
  allAvailablePermissions: Record<string, string[]>;
  /** What every member may do without a role, by permission resource (BaselineAccess). */
  baselineAccess: Record<string, string>;
  /** Permissions every member already has, so no role may hold them (BaselineAccess). */
  retiredPermissions: string[];
  can: { update: boolean; delete: boolean };
}>();

const section = ref('permissions');
const deleteOpen = ref(false);
const dutyIds = ref<string[]>(props.role.duties?.map(duty => duty.id) ?? []);
const attachableTypeIds = ref<number[]>(props.role.attachable_types?.map(Number) ?? []);
const locale = computed(() => getActiveLanguage() as 'lt' | 'en');
const localized = (value: Translation): string => typeof value === 'string' ? value : value[locale.value] ?? value.lt ?? value.en ?? '—';
const dutyOptions = computed(() => props.tenantsWithDuties.flatMap(tenant => tenant.institutions ?? []).flatMap(institution => institution.duties ?? []).map(duty => ({ value: duty.id, label: duty.name })));
const typeOptions = computed(() => props.allTypes.map(type => ({ value: type.id, label: localized(type.title) })));
const attachedTypes = computed(() => props.allTypes.filter(type => attachableTypeIds.value.includes(Number(type.id))));
const facts = computed<RecordFact[]>(() => [
  { key: 'guard', label: $t('Apsaugos kontekstas'), value: props.role.guard_name },
  { key: 'permissions', label: $t('Teisės'), value: String(props.role.permissions?.length ?? 0) },
  { key: 'duties', label: $t('Pareigybės'), value: String(dutyIds.value.length) },
]);
const sections = computed<RecordPageSection[]>(() => [
  { value: 'permissions', label: $t('Teisės'), count: props.role.permissions?.length },
  { value: 'duties', label: $t('Pareigybės'), count: dutyIds.value.length },
  { value: 'types', label: $t('Tipai'), count: attachableTypeIds.value.length },
]);
const primaryAction = computed<RecordAction | undefined>(() => props.can.update ? { key: 'edit', label: $t('Keisti pavadinimą'), icon: Edit } : undefined);
const overflowActions = computed<RecordAction[]>(() => props.can.delete ? [{ key: 'delete', label: $t('Šalinti'), icon: Trash2, destructive: true }] : []);

function saveDuties(): void {
  router.put(route('roles.syncDuties', props.role.id), { duties: dutyIds.value }, { preserveScroll: true });
}
function saveAttachableTypes(): void {
  router.put(route('roles.syncAttachableTypes', props.role.id), { attachable_types: attachableTypeIds.value }, { preserveScroll: true });
}
function handleAction(action: string): void {
  if (action === 'edit') router.visit(route('roles.edit', props.role.id));
  if (action === 'delete') deleteOpen.value = true;
}

</script>
