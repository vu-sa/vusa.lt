<template>
  <RecordPage
    v-model:section="section"
    :title="responsibility.display_title"
    :entity-type="ModelEnum.ROLE"
    :status="purposeStatus"
    :facts
    :sections
    :primary-action
    :overflow-actions
    :history-subject="{ type: 'role', id: responsibility.id }"
    actions-beside-title
    @action="handleAction"
  >
    <template #subtitle>
      <p v-if="description" class="mt-2 max-w-3xl text-sm text-muted-foreground" data-testid="responsibility-description">
        {{ description }}
      </p>
    </template>

    <template #alert>
      <div class="space-y-3">
        <div
          v-if="responsibility.purpose"
          class="flex items-start gap-3 border border-border p-4"
          data-testid="responsibility-purpose"
        >
          <Compass class="mt-0.5 size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
          <div class="text-sm">
            <p class="font-semibold text-foreground">
              {{ $t('responsibilities.page.purpose', { purpose: responsibility.purpose_label ?? '' }) }}
            </p>
            <p class="mt-0.5 text-muted-foreground">
              {{ responsibility.purpose_drives }}
              <Link v-if="canManageSettings" :href="route('settings.purposes.edit')" class="ml-1 underline underline-offset-4">
                {{ $t('responsibilities.page.purpose_settings') }}
              </Link>
            </p>
          </div>
        </div>
        <div
          v-if="gapCount"
          class="flex items-start gap-3 border border-status-attention-border bg-status-attention-surface p-4 text-status-attention"
          data-testid="responsibility-gaps"
        >
          <TriangleAlert class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
          <p class="text-sm">
            {{ $tChoice('responsibilities.page.gaps', gapCount, { count: gapCount }) }}
          </p>
        </div>
      </div>
    </template>

    <template #permissions>
      <div class="space-y-6">
        <p v-if="responsibility.is_super_admin" class="text-sm text-muted-foreground">
          {{ $t('responsibilities.page.super_admin') }}
        </p>
        <template v-else>
          <p class="max-w-3xl text-sm text-muted-foreground">
            {{ $t('responsibilities.matrix.intro') }}
          </p>
          <PermissionMatrix
            :catalog
            :granted="grantedPermissions"
            :readonly="!can.update"
            :processing="savingPermissions"
            @save="savePermissions"
          />
          <div v-if="legacyPermissions.length" class="border-t border-border pt-4 text-xs text-muted-foreground" data-testid="legacy-permissions">
            <p class="font-medium text-foreground">
              {{ $t('responsibilities.matrix.legacy_title') }}
            </p>
            <p class="mt-1">
              {{ $t('responsibilities.matrix.legacy_description') }}
            </p>
            <p class="mt-1 font-mono">
              {{ legacyPermissions.join(', ') }}
            </p>
          </div>
        </template>
      </div>
    </template>

    <template #grants>
      <Deferred data="grants">
        <template #fallback>
          <div class="space-y-3" data-testid="grants-skeleton">
            <div v-for="n in 4" :key="n" class="h-12 animate-pulse bg-secondary/60" />
          </div>
        </template>
        <ResponsibilityGrants
          :role-id="responsibility.id"
          :grants="grants!"
          :scopes="purposeScopes"
          :can-update="can.update"
          :duty-type-options
          :type-grant-blocked-by
          :options="grantOptions"
        />
      </Deferred>
    </template>

    <template #people>
      <Deferred data="people">
        <template #fallback>
          <div class="space-y-3" data-testid="people-skeleton">
            <div v-for="n in 5" :key="n" class="h-12 animate-pulse bg-secondary/60" />
          </div>
        </template>
        <ResponsibilityPeople :people="people ?? []" />
      </Deferred>
    </template>

    <template #delegation>
      <OverviewSection variant="home" :title="$t('responsibilities.delegation.title')" :icon="Tags" class="max-w-3xl">
        <p class="text-xs text-muted-foreground">
          {{ $t('responsibilities.delegation.description') }}
        </p>
        <TypeTreeChecklist v-model="delegated" :types="dutyTypeOptions" :disabled="!can.update" />
        <div v-if="can.update && delegationDirty" class="flex justify-end gap-2 pt-2">
          <Button variant="outline" voice="sentence" @click="delegated = [...attachableTypes]">
            {{ $t('responsibilities.matrix.discard') }}
          </Button>
          <Button variant="brand" @click="saveDelegation">
            {{ $t('Išsaugoti') }}
          </Button>
        </div>
      </OverviewSection>
    </template>
  </RecordPage>

  <ConfirmDialog
    v-model:open="deleteOpen"
    :title="$t('responsibilities.page.delete_title')"
    :description="$t('responsibilities.page.delete_description')"
    :confirm-label="$t('Šalinti')"
    destructive
    @confirm="router.delete(route('responsibilities.destroy', responsibility.id))"
  />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Deferred, Link, router } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Compass, Edit, Tags, Trash2, TriangleAlert } from '@lucide/vue';

import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { ConfirmDialog, OverviewSection } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { TypeTreeChecklist } from '@/Components/AdminForms/TypeTree';
import type { StatusPresentation } from '@/Constants/statuses';
import PermissionMatrix from '@/Features/Admin/PermissionMatrix/PermissionMatrix.vue';
import type { PermissionCatalog } from '@/Features/Admin/PermissionMatrix/types';
import ResponsibilityGrants from '@/Features/Admin/Responsibilities/ResponsibilityGrants.vue';
import ResponsibilityPeople from '@/Features/Admin/Responsibilities/ResponsibilityPeople.vue';
import type {
  ResponsibilityGrantOptions,
  ResponsibilityGrantsPayload,
  ResponsibilityPerson,
  ResponsibilityScopeValue,
} from '@/Features/Admin/Responsibilities/types';
import { ModelEnum } from '@/Types/enums';

interface Translations { lt?: string | null; en?: string | null }

const props = defineProps<{
  responsibility: {
    id: string;
    name: string;
    title: Translations;
    description: Translations;
    display_title: string;
    purpose: string | null;
    purpose_label: string | null;
    purpose_drives: string | null;
    purpose_scopes: ResponsibilityScopeValue[];
    is_super_admin: boolean;
    updated_at: string | null;
  };
  facts: { permissions: number; duties: number; direct: number; via_types: number; via_institutions: number; people: number; gaps: number };
  catalog: PermissionCatalog;
  grantedPermissions: string[];
  legacyPermissions: string[];
  attachableTypes: number[];
  dutyTypeOptions: { id: number; title: string; parent_id: number | null }[];
  typeGrantBlockedBy?: string[];
  grants?: ResponsibilityGrantsPayload;
  people?: ResponsibilityPerson[];
  grantOptions?: ResponsibilityGrantOptions;
  can: { update: boolean; delete: boolean; manageSettings: boolean };
}>();

const section = ref('permissions');
const deleteOpen = ref(false);
const savingPermissions = ref(false);
const delegated = ref<Array<number | string>>([...props.attachableTypes]);

const locale = computed(() => getActiveLanguage() as 'lt' | 'en');
const description = computed(() => props.responsibility.description?.[locale.value] || props.responsibility.description?.lt || null);
const purposeScopes = computed(() => props.responsibility.purpose_scopes);
const gapCount = computed(() => props.facts.gaps);
const canManageSettings = computed(() => props.can.manageSettings);

// Most responsibilities only grant permissions; the badge marks the few the app finds people by.
const purposeStatus = computed<StatusPresentation | undefined>(() => (props.responsibility.purpose
  ? { label: props.responsibility.purpose_label ?? '', role: 'info', icon: Compass }
  : undefined));

const facts = computed<RecordFact[]>(() => [
  { key: 'permissions', label: $t('responsibilities.facts.permissions'), value: props.facts.permissions },
  { key: 'duties', label: $t('responsibilities.facts.duties'), value: props.facts.duties, detail: grantSourcesDetail.value },
  { key: 'people', label: $t('responsibilities.facts.people'), value: props.facts.people },
]);

const grantSourcesDetail = computed(() => [
  props.facts.direct ? $tChoice('responsibilities.facts.direct', props.facts.direct, { count: props.facts.direct }) : null,
  props.facts.via_types ? $tChoice('responsibilities.facts.via_types', props.facts.via_types, { count: props.facts.via_types }) : null,
  props.facts.via_institutions ? $tChoice('responsibilities.facts.via_institutions', props.facts.via_institutions, { count: props.facts.via_institutions }) : null,
].filter(Boolean).join(' · ') || null);

const sections = computed<RecordPageSection[]>(() => [
  { value: 'permissions', label: $t('responsibilities.tabs.permissions'), count: props.facts.permissions },
  { value: 'grants', label: $t('responsibilities.tabs.grants'), count: props.facts.direct + props.facts.via_types + props.facts.via_institutions },
  { value: 'people', label: $t('responsibilities.tabs.people'), count: props.facts.people },
  { value: 'delegation', label: $t('responsibilities.tabs.delegation'), count: props.attachableTypes.length },
]);

const primaryAction = computed<RecordAction | undefined>(() => (props.can.update ? { key: 'edit', label: $t('Redaguoti'), icon: Edit } : undefined));
const overflowActions = computed<RecordAction[]>(() => (props.can.delete ? [{ key: 'delete', label: $t('Šalinti'), icon: Trash2, destructive: true }] : []));

const delegationDirty = computed(() => [...delegated.value].sort().join() !== [...props.attachableTypes].sort().join());

function savePermissions(permissions: string[]): void {
  savingPermissions.value = true;
  router.put(route('responsibilities.permissions.update', props.responsibility.id), { permissions }, {
    preserveScroll: true,
    onFinish: () => { savingPermissions.value = false; },
  });
}

function saveDelegation(): void {
  router.put(route('responsibilities.attachableTypes.update', props.responsibility.id), { attachable_types: delegated.value }, { preserveScroll: true });
}

function handleAction(action: string): void {
  if (action === 'edit') router.visit(route('responsibilities.edit', props.responsibility.id));
  if (action === 'delete') deleteOpen.value = true;
}
</script>
