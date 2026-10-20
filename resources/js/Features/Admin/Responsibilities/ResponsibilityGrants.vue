<template>
  <div class="space-y-10" data-slot="responsibility-grants">
    <p class="max-w-3xl text-sm text-muted-foreground">
      {{ $t('responsibilities.grants.intro') }}
    </p>

    <OverviewSection variant="home" :title="$t('responsibilities.grants.duties')" :icon="Briefcase" :count="grants.duties.length">
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
          <Button v-if="canUpdate" variant="ghost" size="sm" voice="sentence" class="shrink-0 pointer-coarse:h-11" @click="removeTarget = { id: grant.id, label: grant.duty.name }">
            {{ $t('responsibilities.grants.remove') }}
          </Button>
        </li>
      </ul>
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('responsibilities.grants.duties_empty') }}
      </p>
    </OverviewSection>

    <OverviewSection variant="home" :title="$t('responsibilities.grants.duty_types')" :icon="Tags" :count="grants.dutyTypes.length">
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
              {{ $tChoice('responsibilities.grants.reaches', grant.duties_count, { count: grant.duties_count }) }}<template v-if="grant.scope_name"> · {{ grant.scope_name }}</template>
            </span>
            <span v-if="grant.needs_scope" class="mt-1 block text-xs text-status-attention" data-testid="grant-needs-scope">
              {{ $t('types.grant_needs_scope') }}
            </span>
          </span>
          <Button v-if="canUpdate" variant="ghost" size="sm" voice="sentence" class="shrink-0 pointer-coarse:h-11" @click="removeTarget = { id: grant.id, label: grant.type.title }">
            {{ $t('responsibilities.grants.remove') }}
          </Button>
        </li>
      </ul>
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('responsibilities.grants.duty_types_empty') }}
      </p>
    </OverviewSection>

    <OverviewSection
      v-if="grants.institutions.length || canUpdate"
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
              {{ $tChoice('responsibilities.grants.reaches', grant.duties_count, { count: grant.duties_count }) }}
            </span>
          </span>
          <Button v-if="canUpdate" variant="ghost" size="sm" voice="sentence" class="shrink-0 pointer-coarse:h-11" @click="removeTarget = { id: grant.id, label: grant.institution.name }">
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
      :disabled="!form.grantee_id || (scopes.length > 0 && !form.scope_id)"
      :dirty="form.isDirty"
      @submit="submit"
    >
      <div class="space-y-6">
        <FormFieldWrapper id="grantee_type" :label="$t('responsibilities.grants.to')">
          <FormSegmentedControl v-model="form.grantee_type" :options="granteeOptions" :aria-label="$t('responsibilities.grants.to')" test-id-prefix="grantee" />
        </FormFieldWrapper>

        <DutySearchField v-if="form.grantee_type === DUTY" v-model="selectedDuty" include-inactive :error="form.errors.grantee_id" />

        <FormFieldWrapper v-else-if="form.grantee_type === DUTY_TYPE" id="grant_type" :label="$t('responsibilities.grants.duty_type')" :error="form.errors.grantee_id" required>
          <NativeSelect
            id="grant_type"
            v-model="form.grantee_id"
            :options="dutyTypeOptions.map(type => ({ value: String(type.id), label: type.title }))"
            :placeholder="$t('mailing_lists.sheet.pick')"
          />
        </FormFieldWrapper>

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
          <FormFieldWrapper id="grant_scope_type" :label="$t('responsibilities.sheet.scope')" :hint="$t('responsibilities.grants.scope_hint')" :error="form.errors.scope_type">
            <FormSegmentedControl
              v-model="form.scope_type"
              :options="scopes.map(scope => ({ value: scope, label: $t(`responsibilities.scopes.${scope}`) }))"
              :aria-label="$t('responsibilities.sheet.scope')"
              test-id-prefix="grant-scope"
            />
          </FormFieldWrapper>
          <FormFieldWrapper id="grant_scope_id" :label="$t(`responsibilities.sheet.${form.scope_type}`)" :error="form.errors.scope_id" required>
            <p v-if="!options" class="text-sm text-muted-foreground">
              {{ $t('Įkeliama…') }}
            </p>
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
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Briefcase, Landmark, Plus, Tags, TriangleAlert } from '@lucide/vue';

import type { ResponsibilityGrantOptions, ResponsibilityGrantsPayload, ResponsibilityScopeValue } from './types';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import { ConfirmDialog, FormSegmentedControl, OverviewSection, SheetForm, type FormSegmentOption } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { NativeSelect } from '@/Components/ui/native-select';
import { SingleSelect } from '@/Components/ui/single-select';
import DutySearchField, { type DutyHit } from '@/Features/Admin/Occupancy/DutySearchField.vue';
import { permissionLabel } from '@/Features/Admin/PermissionMatrix/permissionLabels';

const DUTY = 'duty';
const DUTY_TYPE = 'duty_type';
const INSTITUTION = 'institution';
type Grantee = typeof DUTY | typeof DUTY_TYPE | typeof INSTITUTION;

const props = defineProps<{
  roleId: string;
  grants: ResponsibilityGrantsPayload;
  /** Levels the responsibility's purpose may cover; empty for most responsibilities. */
  scopes: ResponsibilityScopeValue[];
  canUpdate: boolean;
  dutyTypeOptions: { id: number; title: string }[];
  /** Optional prop: loaded when the sheet opens. */
  options?: ResponsibilityGrantOptions;
}>();

const sheetOpen = ref(false);
const removeTarget = ref<{ id: string; label: string } | null>(null);
let dutyHit: DutyHit | null = null;

const form = useForm({
  grantee_type: DUTY as Grantee,
  grantee_id: '',
  scope_type: (props.scopes[0] ?? null) as ResponsibilityScopeValue | null,
  scope_id: '',
});

const granteeOptions: FormSegmentOption<Grantee>[] = [
  { value: DUTY, label: $t('responsibilities.grants.to_duty') },
  { value: DUTY_TYPE, label: $t('responsibilities.grants.to_duty_type') },
  { value: INSTITUTION, label: $t('responsibilities.grants.to_institution') },
];

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
  }[form.scope_type ?? 'tenant'];
});
const selectedScope = computed({
  get: () => scopeTargets.value.find(target => target.id === form.scope_id) ?? null,
  set: (target: { id: string } | null) => { form.scope_id = target?.id ?? ''; },
});

watch(() => form.grantee_type, () => {
  form.grantee_id = '';
  dutyHit = null;
});
watch(() => form.scope_type, () => {
  form.scope_id = '';
});

function openSheet(grantee: Grantee): void {
  form.reset();
  form.clearErrors();
  form.grantee_type = grantee;
  sheetOpen.value = true;
  router.reload({ only: ['grantOptions'] });
}

function submit(): void {
  form
    .transform(data => (props.scopes.length ? data : { grantee_type: data.grantee_type, grantee_id: data.grantee_id }))
    .post(route('responsibilities.grants.store', props.roleId), {
      preserveScroll: true,
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
    router.delete(route('responsibilities.grants.destroy', { role: props.roleId, grant: target.id }), { preserveScroll: true });
  }
}
</script>
