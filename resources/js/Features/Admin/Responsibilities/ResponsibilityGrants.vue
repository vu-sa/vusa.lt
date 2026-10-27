<template>
  <div class="space-y-10" data-slot="responsibility-grants">
    <p v-if="!isSuperAdmin" class="max-w-3xl text-sm text-muted-foreground">
      {{ $t('responsibilities.grants.intro') }}
    </p>

    <OverviewSection
      v-if="isSuperAdmin || grants.users.length"
      variant="home"
      :title="$t('responsibilities.grants.users')"
      :icon="UserRound"
      :count="grants.users.length"
      data-testid="grant-users"
    >
      <p class="text-xs text-muted-foreground">
        {{ $t(isSuperAdmin ? 'responsibilities.grants.users_super_admin_hint' : 'responsibilities.grants.users_hint') }}
      </p>
      <ul v-if="grants.users.length" class="divide-y divide-border">
        <li v-for="holder in grants.users" :key="holder.id" class="flex min-h-14 items-center justify-between gap-3 py-2.5" data-testid="grant-user">
          <span class="flex min-w-0 items-center gap-3">
            <UserAvatar :user="(holder as unknown as App.Entities.User)" :size="32" />
            <span class="min-w-0">
              <Link :href="route('users.show', holder.id)" class="block text-sm font-medium text-foreground hover:underline">{{ holder.name }}</Link>
              <span v-if="!isSuperAdmin" class="block text-xs text-muted-foreground">
                <template v-if="holder.tenants.length">{{ $t('responsibilities.grants.applies_in') }} {{ holder.tenants.join(', ') }}</template>
                <template v-else>{{ $t('responsibilities.grants.no_current_duties') }}</template>
              </span>
            </span>
          </span>
          <Button
            v-if="canRemoveUsers && !isSuperAdmin"
            variant="ghost"
            size="sm"
            voice="sentence"
            class="shrink-0 pointer-coarse:h-11"
            @click="removeTarget = { kind: 'user', id: holder.id, label: holder.name }"
          >
            {{ $t('responsibilities.grants.remove') }}
          </Button>
        </li>
      </ul>
    </OverviewSection>

    <OverviewSection v-if="!isSuperAdmin" variant="home" :title="$t('responsibilities.grants.duties')" :icon="Briefcase" :count="grants.duties.length">
      <template v-if="canUpdate" #actions>
        <Button size="sm" variant="outline" voice="sentence" class="u-touch" data-testid="grant-add" @click="openSheet(DUTY)">
          <Plus class="size-4" aria-hidden="true" />
          {{ $t('responsibilities.grants.add') }}
        </Button>
      </template>
      <ul v-if="grants.duties.length" class="divide-y divide-border">
        <li v-for="grant in grants.duties" :key="grant.id" class="flex min-h-14 items-start justify-between gap-3 py-2.5" data-testid="grant-duty">
          <span class="min-w-0">
            <Link :href="route('duties.show', grant.duty.id)" class="block text-sm font-medium text-foreground hover:underline">
              <InflectedDutyName :name="grant.duty.name" />
            </Link>
            <span class="block text-xs text-muted-foreground">
              {{ [grant.institution?.name, grant.tenant].filter(Boolean).join(' · ') }}
              · {{ $tChoice('responsibilities.grants.holders', grant.holders_count, { count: grant.holders_count }) }}
            </span>
            <span v-if="grant.scope_name" class="mt-1 inline-flex border border-border px-1.5 py-0.5 text-xs text-foreground">
              {{ $t(`responsibilities.scopes.${grant.scope_type}`) }} · {{ grant.scope_name }}
            </span>
            <span v-if="grant.missing_permissions.length" class="mt-1 flex items-start gap-1.5 text-xs text-status-attention">
              <TriangleAlert class="mt-px size-3.5 shrink-0" aria-hidden="true" />
              {{ $t('responsibilities.grants.missing', { permissions: grant.missing_permissions.map(permissionLabel).join(', ') }) }}
            </span>
          </span>
          <Button v-if="canUpdate" variant="ghost" size="sm" voice="sentence" class="shrink-0 pointer-coarse:h-11" @click="removeTarget = { kind: 'grant', id: grant.id, label: grant.duty.name }">
            {{ $t('responsibilities.grants.remove') }}
          </Button>
        </li>
      </ul>
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('responsibilities.grants.duties_empty') }}
      </p>
    </OverviewSection>

    <OverviewSection v-if="!isSuperAdmin" variant="home" :title="$t('responsibilities.grants.duty_types')" :icon="Tags" :count="grants.dutyTypes.length">
      <template v-if="canUpdate" #actions>
        <Button size="sm" variant="outline" voice="sentence" class="u-touch" @click="openSheet(DUTY_TYPE)">
          <Plus class="size-4" aria-hidden="true" />
          {{ $t('responsibilities.grants.add_type') }}
        </Button>
      </template>
      <p class="text-xs text-muted-foreground">
        {{ $t('responsibilities.grants.duty_types_hint') }}
      </p>
      <ul v-if="grants.dutyTypes.length" class="divide-y divide-border">
        <li v-for="grant in grants.dutyTypes" :key="grant.id" class="flex min-h-12 items-center justify-between gap-3 py-2" data-testid="grant-duty-type">
          <span class="min-w-0">
            <Link :href="route('dutyTypes.show', grant.type.id)" class="block text-sm font-medium text-foreground hover:underline">{{ grant.type.title }}</Link>
            <span class="block text-xs text-muted-foreground">
              {{ $tChoice('responsibilities.grants.reaches', grant.duties_count, { count: grant.duties_count }) }}<template v-if="scopeLabel(grant)"> · {{ scopeLabel(grant) }}</template>
            </span>
            <span v-if="grant.needs_scope" class="mt-1 block text-xs text-status-attention" data-testid="grant-needs-scope">
              {{ $t('types.grant_needs_scope') }}
            </span>
          </span>
          <Button v-if="canUpdate" variant="ghost" size="sm" voice="sentence" class="shrink-0 pointer-coarse:h-11" @click="removeTarget = { kind: 'grant', id: grant.id, label: grant.type.title }">
            {{ $t('responsibilities.grants.remove') }}
          </Button>
        </li>
      </ul>
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('responsibilities.grants.duty_types_empty') }}
      </p>
    </OverviewSection>

    <OverviewSection
      v-if="!isSuperAdmin && (grants.institutions.length || canUpdate)"
      variant="home"
      :title="$t('responsibilities.grants.institutions')"
      :icon="Landmark"
      :count="grants.institutions.length"
    >
      <template v-if="canUpdate" #actions>
        <Button size="sm" variant="outline" voice="sentence" class="u-touch" @click="openSheet(INSTITUTION)">
          <Plus class="size-4" aria-hidden="true" />
          {{ $t('responsibilities.grants.add_institution') }}
        </Button>
      </template>
      <p class="text-xs text-muted-foreground">
        {{ $t('responsibilities.grants.institutions_hint') }}
      </p>
      <ul v-if="grants.institutions.length" class="divide-y divide-border">
        <li v-for="grant in grants.institutions" :key="grant.id" class="flex min-h-12 items-center justify-between gap-3 py-2" data-testid="grant-institution">
          <span class="min-w-0">
            <Link :href="route('institutions.show', grant.institution.id)" class="block text-sm font-medium text-foreground hover:underline">{{ grant.institution.name }}</Link>
            <span class="block text-xs text-muted-foreground">
              {{ $tChoice('responsibilities.grants.reaches', grant.duties_count, { count: grant.duties_count }) }}<template v-if="scopeLabel(grant)"> · {{ scopeLabel(grant) }}</template>
            </span>
          </span>
          <Button v-if="canUpdate" variant="ghost" size="sm" voice="sentence" class="shrink-0 pointer-coarse:h-11" @click="removeTarget = { kind: 'grant', id: grant.id, label: grant.institution.name }">
            {{ $t('responsibilities.grants.remove') }}
          </Button>
        </li>
      </ul>
    </OverviewSection>

    <SheetForm
      v-model:open="sheetOpen"
      :title="$t('responsibilities.grants.sheet_title')"
      :description="$t('responsibilities.grants.sheet_description')"
      :save-label="$t('responsibilities.grants.submit')"
      :processing="form.processing"
      :disabled="!form.grantee_id || (scopes.length > 0 && form.scope_type !== OWN_TENANT && !form.scope_id) || (form.grantee_type === DUTY_TYPE && typeGrantBlockedBy.length > 0)"
      :dirty="form.isDirty"
      @submit="submit"
    >
      <div class="space-y-6">
        <FormFieldWrapper id="grantee_type" :label="$t('responsibilities.grants.to')">
          <FormSegmentedControl v-model="form.grantee_type" :options="granteeOptions" :aria-label="$t('responsibilities.grants.to')" test-id-prefix="grantee" />
        </FormFieldWrapper>

        <DutySearchField v-if="form.grantee_type === DUTY" v-model="selectedDuty" include-inactive :error="form.errors.grantee_id" />

        <template v-else-if="form.grantee_type === DUTY_TYPE">
          <p v-if="typeGrantBlockedBy.length" class="text-sm text-status-attention" data-testid="type-grant-blocked">
            {{ $t('responsibilities.validation.not_type_grantable', { permissions: typeGrantBlockedBy.join(', ') }) }}
          </p>
          <FormFieldWrapper
            v-else
            id="grant_type"
            :label="$t('responsibilities.grants.duty_type')"
            :hint="$t('responsibilities.grants.duty_type_hint')"
            :error="form.errors.grantee_id"
            required
          >
            <TypeTreeSelect
              id="grant_type"
              v-model="form.grantee_id"
              :types="dutyTypeOptions"
              as-string
              :placeholder="$t('mailing_lists.sheet.pick')"
            />
          </FormFieldWrapper>
        </template>

        <FormFieldWrapper
          v-else
          id="grant_institution"
          :label="$t('responsibilities.grants.institution')"
          :hint="$t('responsibilities.grants.institutions_hint')"
          :error="form.errors.grantee_id"
          required
        >
          <p v-if="!options" class="text-sm text-muted-foreground">
            {{ $t('Įkeliama…') }}
          </p>
          <SingleSelect
            v-else
            v-model="selectedInstitution"
            :options="options.institutions"
            label-field="name"
            value-field="id"
            :placeholder="$t('mailing_lists.sheet.pick')"
          />
        </FormFieldWrapper>

        <template v-if="scopes.length">
          <FormFieldWrapper v-if="scopeOptions.length > 1" id="grant_scope_type" :label="$t('responsibilities.sheet.scope')" :hint="$t('responsibilities.grants.scope_hint')" :error="form.errors.scope_type">
            <FormSegmentedControl
              v-model="form.scope_type"
              :options="scopeOptions"
              :aria-label="$t('responsibilities.sheet.scope')"
              test-id-prefix="grant-scope"
            />
          </FormFieldWrapper>
          <p v-if="form.scope_type === OWN_TENANT" class="text-sm text-muted-foreground" data-testid="grant-own-tenant-hint">
            {{ $t(form.grantee_type === INSTITUTION ? 'responsibilities.grants.institution_tenant_hint' : 'responsibilities.grants.own_tenant_hint') }}
          </p>
          <FormFieldWrapper v-else id="grant_scope_id" :label="$t(`responsibilities.sheet.${form.scope_type}`)" :error="form.errors.scope_id" required>
            <p v-if="!options" class="text-sm text-muted-foreground">
              {{ $t('Įkeliama…') }}
            </p>
            <TypeTreeSelect
              v-else-if="form.scope_type === 'institution_type'"
              id="grant_scope_id"
              v-model="form.scope_id"
              :types="options.institutionTypes"
              as-string
              :placeholder="$t('mailing_lists.sheet.pick')"
            />
            <SingleSelect
              v-else
              v-model="selectedScope"
              :options="scopeTargets"
              label-field="label"
              value-field="id"
              :placeholder="$t('mailing_lists.sheet.pick')"
            />
          </FormFieldWrapper>
        </template>
      </div>
    </SheetForm>

    <ConfirmDialog
      :open="removeTarget !== null"
      :title="$t('responsibilities.grants.remove_confirm', { name: removeTarget?.label ?? '' })"
      :description="$t('responsibilities.grants.remove_description')"
      :confirm-label="$t('responsibilities.grants.remove')"
      destructive
      @update:open="!$event && (removeTarget = null)"
      @confirm="remove"
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
import { computed, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Briefcase, Landmark, Plus, Tags, TriangleAlert, UserRound } from '@lucide/vue';

import type { GrantScopeValue, ResponsibilityGrantOptions, ResponsibilityGrantsPayload, ResponsibilityScopeValue } from './types';

import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import { ConfirmDialog, FormSegmentedControl, OverviewSection, SheetForm, type FormSegmentOption } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { TypeTreeSelect } from '@/Components/AdminForms/TypeTree';
import { SingleSelect } from '@/Components/ui/single-select';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';
import DutySearchField, { type DutyHit } from '@/Features/Admin/Occupancy/DutySearchField.vue';
import { permissionLabel } from '@/Features/Admin/PermissionMatrix/permissionLabels';

const DUTY = 'duty';
const DUTY_TYPE = 'duty_type';
const INSTITUTION = 'institution';
const OWN_TENANT = 'own_tenant';
type Grantee = typeof DUTY | typeof DUTY_TYPE | typeof INSTITUTION;

const props = withDefaults(defineProps<{
  roleId: string;
  grants: ResponsibilityGrantsPayload;
  /** Levels the responsibility's purpose may cover; empty for most responsibilities. */
  scopes: ResponsibilityScopeValue[];
  canUpdate: boolean;
  /** Only a super admin may take away a responsibility someone holds directly. */
  canRemoveUsers?: boolean;
  isSuperAdmin?: boolean;
  dutyTypeOptions: { id: number; title: string; parent_id: number | null }[];
  /** Permissions that keep this responsibility from being given to a duty type. */
  typeGrantBlockedBy?: string[];
  /** Optional prop: loaded when the sheet opens. */
  options?: ResponsibilityGrantOptions;
}>(), {
  typeGrantBlockedBy: () => [],
  options: undefined,
});

const { report: accessWarningReport, open: accessWarningOpen, guardedSubmit, confirm: accessWarningConfirm, cancel: accessWarningCancel } = useAccessChangeGuard();
const sheetOpen = ref(false);
const removeTarget = ref<{ kind: 'grant' | 'user'; id: string; label: string } | null>(null);
let dutyHit: DutyHit | null = null;

const form = useForm({
  grantee_type: DUTY as Grantee,
  grantee_id: '',
  scope_type: (props.scopes[0] ?? null) as GrantScopeValue | null,
  scope_id: '',
});

const granteeOptions: FormSegmentOption<Grantee>[] = [
  { value: DUTY, label: $t('responsibilities.grants.to_duty') },
  { value: DUTY_TYPE, label: $t('responsibilities.grants.to_duty_type') },
  { value: INSTITUTION, label: $t('responsibilities.grants.to_institution') },
];

// A type reaches duties in every padalinys, so it may cover each duty's own one; an institution's
// duties all share its padalinys, so that is the only padalinys it can cover.
const scopeOptions = computed<FormSegmentOption<GrantScopeValue>[]>(() => {
  const hasTenant = props.scopes.includes('tenant');
  const values: GrantScopeValue[] = {
    [DUTY]: [...props.scopes],
    [DUTY_TYPE]: hasTenant ? [OWN_TENANT, ...props.scopes] : [...props.scopes],
    [INSTITUTION]: props.scopes.map((scope): GrantScopeValue => (scope === 'tenant' ? OWN_TENANT : scope)),
  }[form.grantee_type];

  return values.map(scope => ({ value: scope, label: $t(`responsibilities.scopes.${scope}`) }));
});

function scopeLabel(grant: { scope_type: GrantScopeValue | null; scope_name: string | null }): string | null {
  return grant.scope_type === OWN_TENANT ? $t('responsibilities.scopes.own_tenant') : grant.scope_name;
}

// Pickers work on whole objects; the form stores ids.
const selectedDuty = computed<DutyHit | null>({
  get: () => (form.grantee_id ? dutyHit : null),
  set: (duty) => {
    dutyHit = duty;
    form.grantee_id = duty?.id ?? '';
  },
});
const selectedInstitution = computed({
  get: () => props.options?.institutions.find(institution => institution.id === form.grantee_id) ?? null,
  set: (institution: { id: string } | null) => { form.grantee_id = institution?.id ?? ''; },
});

const scopeTargets = computed(() => {
  if (!props.options) {
    return [];
  }

  return {
    tenant: props.options.tenants.map(tenant => ({ id: tenant.id, label: tenant.shortname })),
    institution_type: props.options.institutionTypes.map(type => ({ id: type.id, label: type.title })),
    institution: props.options.institutions.map(institution => ({ id: institution.id, label: institution.name })),
  }[form.scope_type === OWN_TENANT || !form.scope_type ? 'tenant' : form.scope_type];
});
const selectedScope = computed({
  get: () => scopeTargets.value.find(target => target.id === form.scope_id) ?? null,
  set: (target: { id: string } | null) => { form.scope_id = target?.id ?? ''; },
});

watch(() => form.grantee_type, () => {
  form.grantee_id = '';
  dutyHit = null;
  form.scope_type = scopeOptions.value[0]?.value ?? null;
});
watch(() => form.scope_type, () => {
  form.scope_id = '';
});

// A full visit drops the deferred grants and swaps the list for its skeleton, which resets the scroll.
const GRANT_CHANGE_PROPS = ['grants', 'people', 'facts', 'can', 'flash'];

function openSheet(grantee: Grantee): void {
  form.reset();
  form.clearErrors();
  form.grantee_type = grantee;
  form.scope_type = scopeOptions.value[0]?.value ?? null;
  sheetOpen.value = true;
  router.reload({ only: ['grantOptions'] });
}

function submit(): void {
  form
    .transform((data) => {
      if (!props.scopes.length) {
        return { grantee_type: data.grantee_type, grantee_id: data.grantee_id };
      }

      return data.scope_type === OWN_TENANT ? { grantee_type: data.grantee_type, grantee_id: data.grantee_id, scope_type: data.scope_type } : data;
    })
    .post(route('responsibilities.grants.store', props.roleId), {
      preserveScroll: true,
      only: GRANT_CHANGE_PROPS,
      onSuccess: () => {
        form.defaults();
        sheetOpen.value = false;
      },
    });
}

function remove(): void {
  const target = removeTarget.value;
  removeTarget.value = null;

  if (target) {
    guardedSubmit((acknowledge) => {
      const url = target.kind === 'user'
        ? route('responsibilities.users.destroy', { role: props.roleId, user: target.id })
        : route('responsibilities.grants.destroy', { role: props.roleId, grant: target.id });

      router.delete(url, {
        data: { acknowledge_access_change: acknowledge },
        preserveScroll: true,
        only: GRANT_CHANGE_PROPS,
      });
    });
  }
}
</script>
