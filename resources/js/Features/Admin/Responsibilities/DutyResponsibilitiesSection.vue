<template>
  <div class="max-w-4xl" data-slot="duty-responsibilities">
    <OverviewSection variant="home" :title="$t('responsibilities.duty.title')" :icon="Compass" :count="items.length">
      <template v-if="canUpdate" #actions>
        <SpotlightPopover
          :title="$t('responsibilities.spotlight.title')"
          :description="$t('responsibilities.spotlight.body')"
          :is-dismissed="spotlight.isDismissed.value"
          @dismiss="spotlight.dismiss()"
        >
          <Button size="sm" variant="outline" voice="sentence" class="u-touch" data-testid="responsibility-add" @click="openSheet">
            <Plus class="size-4" aria-hidden="true" />
            {{ $t('responsibilities.duty.add') }}
          </Button>
        </SpotlightPopover>
      </template>

      <p class="text-xs text-muted-foreground">
        {{ $t('responsibilities.duty.description') }}
      </p>
      <ul v-if="items.length" class="divide-y divide-border">
        <li v-for="item in items" :key="item.id" class="flex min-h-12 items-start justify-between gap-3 py-2.5" data-testid="responsibility-row">
          <span class="min-w-0">
            <Link :href="route('responsibilities.show', item.role_id)" class="text-sm font-medium text-foreground hover:underline">
              {{ item.title }}
            </Link>
            <span class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
              <PurposeChip :label="item.purpose_label" />
              <span v-if="item.source !== 'duty'" data-testid="responsibility-source">
                {{ $t(`responsibilities.duty.via_${item.source}`, { name: item.source_name ?? '' }) }}
              </span>
              <span v-if="item.scope_type">
                {{ $t(`responsibilities.scopes.${item.scope_type}`) }}<template v-if="item.scope_name"> · {{ item.scope_name }}</template>
              </span>
            </span>
            <span v-if="item.missing_permissions.length" class="mt-1 flex items-start gap-1.5 text-xs text-status-attention" data-testid="responsibility-gap">
              <TriangleAlert class="mt-px size-3.5 shrink-0" aria-hidden="true" />
              {{ $t('responsibilities.grants.missing', { permissions: item.missing_permissions.map(permissionLabel).join(', ') }) }}
            </span>
          </span>
          <Button
            v-if="canUpdate && item.source === 'duty'"
            variant="ghost"
            size="sm"
            voice="sentence"
            class="shrink-0 pointer-coarse:h-11"
            data-testid="responsibility-remove"
            @click="removeTarget = item"
          >
            {{ $t('responsibilities.duty.remove') }}
          </Button>
        </li>
      </ul>
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('responsibilities.duty.empty') }}
      </p>
    </OverviewSection>

    <SheetForm
      v-model:open="sheetOpen"
      :title="$t('responsibilities.sheet.title')"
      :description="$t('responsibilities.sheet.description')"
      :save-label="$t('responsibilities.sheet.submit')"
      :processing="form.processing"
      :disabled="!options || !form.role_id || (scopes.length > 0 && !form.scope_id)"
      :dirty="form.isDirty"
      @submit="submit"
    >
      <p v-if="!options" class="text-sm text-muted-foreground">
        {{ $t('Įkeliama…') }}
      </p>
      <div v-else class="space-y-6">
        <FormFieldWrapper
          id="responsibility"
          :label="$t('responsibilities.sheet.responsibility')"
          :hint="selected?.description ?? $t('responsibilities.sheet.only_yours')"
          :error="form.errors.role_id"
          required
        >
          <SingleSelect
            v-if="options.responsibilities.length > 16"
            v-model="selectedOption"
            :options="options.responsibilities"
            label-field="title"
            value-field="id"
            :placeholder="$t('mailing_lists.sheet.pick')"
          />
          <NativeSelect
            v-else
            id="responsibility"
            v-model="form.role_id"
            :options="options.responsibilities.map(option => ({ value: option.id, label: option.purpose_label ? `${option.title} · ${option.purpose_label}` : option.title }))"
            :placeholder="$t('mailing_lists.sheet.pick')"
          />
        </FormFieldWrapper>

        <template v-if="scopes.length">
          <FormFieldWrapper id="scope_type" :label="$t('responsibilities.sheet.scope')" :error="form.errors.scope_type">
            <FormSegmentedControl
              v-model="form.scope_type"
              :options="scopeOptions"
              :aria-label="$t('responsibilities.sheet.scope')"
              test-id-prefix="responsibility-scope"
            />
          </FormFieldWrapper>

          <FormFieldWrapper v-if="form.scope_type === 'tenant'" id="scope_tenant" :label="$t('responsibilities.sheet.tenant')" :error="form.errors.scope_id">
            <NativeSelect
              id="scope_tenant"
              v-model="form.scope_id"
              :options="options.tenants.map(tenant => ({ value: String(tenant.id), label: tenant.shortname }))"
              data-testid="responsibility-tenant"
            />
          </FormFieldWrapper>
          <FormFieldWrapper v-else-if="form.scope_type === 'institution_type'" id="scope_type_target" :label="$t('responsibilities.sheet.type')" :error="form.errors.scope_id">
            <SingleSelect
              v-model="selectedType"
              :options="options.types"
              label-field="title"
              value-field="id"
              :placeholder="$t('responsibilities.sheet.type_placeholder')"
            />
          </FormFieldWrapper>
          <FormFieldWrapper v-else id="scope_institution" :label="$t('responsibilities.sheet.institution')" :error="form.errors.scope_id">
            <SingleSelect
              v-model="selectedInstitution"
              :options="options.institutions"
              label-field="name"
              value-field="id"
              :placeholder="$t('responsibilities.sheet.institution_pick')"
            />
          </FormFieldWrapper>
        </template>
      </div>
    </SheetForm>

    <ConfirmDialog
      :open="removeTarget !== null"
      :title="$t('responsibilities.duty.remove_confirm', { name: removeTarget?.title ?? '' })"
      :confirm-label="$t('responsibilities.duty.remove')"
      destructive
      @update:open="!$event && (removeTarget = null)"
      @confirm="remove"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Compass, Plus, TriangleAlert } from '@lucide/vue';

import PurposeChip from './PurposeChip.vue';
import type { DutyResponsibilityItem, DutyResponsibilityOptions, ResponsibilityScopeValue } from './types';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { ConfirmDialog, FormSegmentedControl, OverviewSection, SheetForm, type FormSegmentOption } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { NativeSelect } from '@/Components/ui/native-select';
import { SingleSelect } from '@/Components/ui/single-select';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { permissionLabel } from '@/Features/Admin/PermissionMatrix/permissionLabels';

const props = defineProps<{
  dutyId: string;
  /** Duty's own padalinys, the default scope. */
  tenantId?: number | null;
  items: DutyResponsibilityItem[];
  canUpdate: boolean;
  /** Optional prop: loaded when the sheet opens. */
  options?: DutyResponsibilityOptions;
}>();

// v2: responsibilities now carry permissions; returning users learn roles moved here.
const spotlight = useFeatureSpotlight('duty-responsibilities-v2');
const sheetOpen = ref(false);
const removeTarget = ref<DutyResponsibilityItem | null>(null);

const form = useForm({
  role_id: '',
  scope_type: 'tenant' as ResponsibilityScopeValue,
  scope_id: props.tenantId ? String(props.tenantId) : '',
});

const selected = computed(() => props.options?.responsibilities.find(option => option.id === form.role_id));
const scopes = computed(() => selected.value?.scopes ?? []);

const scopeOptions = computed<FormSegmentOption<ResponsibilityScopeValue>[]>(() =>
  scopes.value.map(scope => ({ value: scope, label: $t(`responsibilities.scopes.${scope}`) })));

// SingleSelect works on whole objects; the form stores the id.
const selectedOption = computed({
  get: () => selected.value ?? null,
  set: (option: { id: string } | null) => { form.role_id = option?.id ?? ''; },
});
const selectedType = computed({
  get: () => props.options?.types.find(type => type.id === form.scope_id) ?? null,
  set: (type: { id: string } | null) => { form.scope_id = type?.id ?? ''; },
});
const selectedInstitution = computed({
  get: () => props.options?.institutions.find(institution => institution.id === form.scope_id) ?? null,
  set: (institution: { id: string } | null) => { form.scope_id = institution?.id ?? ''; },
});

watch(scopes, (allowed) => {
  if (allowed.length && !allowed.includes(form.scope_type)) {
    form.scope_type = allowed[0]!;
  }
});
watch(() => form.scope_type, (scope) => {
  form.scope_id = scope === 'tenant' && props.tenantId ? String(props.tenantId) : '';
});

function openSheet(): void {
  spotlight.dismiss();
  form.reset();
  form.clearErrors();
  sheetOpen.value = true;
  router.reload({ only: ['responsibilityOptions'] });
}

function submit(): void {
  form
    .transform(data => (scopes.value.length ? data : { role_id: data.role_id }))
    .post(route('duties.responsibilities.store', props.dutyId), {
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
    router.delete(route('duties.responsibilities.destroy', { duty: props.dutyId, grant: target.id }), { preserveScroll: true });
  }
}
</script>
