<template>
  <div class="grid gap-10 xl:grid-cols-2 xl:gap-16" data-slot="duty-responsibilities">
    <OverviewSection variant="home" :title="$t('responsibilities.duty.title')" :icon="Compass">
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
        <li v-for="item in items" :key="item.id" class="flex min-h-11 items-center justify-between gap-3 py-2" data-testid="responsibility-row">
          <span class="min-w-0">
            <span class="block text-sm font-medium text-foreground">{{ item.label }}</span>
            <span class="block text-xs text-muted-foreground">
              {{ $t(`responsibilities.scopes.${item.scope_type}`) }}<template v-if="item.scope_name"> · {{ item.scope_name }}</template>
            </span>
          </span>
          <Button
            v-if="canUpdate"
            variant="ghost"
            size="sm"
            voice="sentence"
            class="pointer-coarse:h-11"
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

    <OverviewSection variant="home" :title="$t('responsibilities.duty.roles_title')" :icon="ShieldCheck">
      <p class="text-xs text-muted-foreground">
        {{ $t('responsibilities.duty.roles_description') }}
      </p>
      <ul v-if="roles.length" class="divide-y divide-border">
        <li v-for="role in roles" :key="role.id" class="py-2.5 text-sm font-medium text-foreground" data-testid="duty-role-row">
          {{ role.name }}
        </li>
      </ul>
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('responsibilities.duty.roles_empty') }}
      </p>
    </OverviewSection>

    <SheetForm
      v-model:open="sheetOpen"
      :title="$t('responsibilities.sheet.title')"
      :description="$t('responsibilities.sheet.description')"
      :save-label="$t('responsibilities.sheet.submit')"
      :processing="form.processing"
      :disabled="!options || !form.scope_id"
      :dirty="form.isDirty"
      @submit="submit"
    >
      <p v-if="!options" class="text-sm text-muted-foreground">
        {{ $t('Įkeliama…') }}
      </p>
      <div v-else class="space-y-6">
        <FormFieldWrapper
          v-if="options.responsibilities.length > 1"
          id="responsibility"
          :label="$t('responsibilities.sheet.responsibility')"
          :error="form.errors.responsibility"
        >
          <Select v-model="form.responsibility">
            <SelectTrigger id="responsibility">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="option in options.responsibilities" :key="option.value" :value="option.value">
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
        <div v-else class="text-sm">
          <p class="font-medium text-foreground">
            {{ selectedResponsibility?.label }}
          </p>
          <p class="text-muted-foreground">
            {{ selectedResponsibility?.description }}
          </p>
        </div>

        <FormFieldWrapper id="scope_type" :label="$t('responsibilities.sheet.scope')" :error="form.errors.scope_type">
          <FormSegmentedControl
            v-model="form.scope_type"
            :options="scopeOptions"
            :aria-label="$t('responsibilities.sheet.scope')"
            test-id-prefix="responsibility-scope"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          v-if="form.scope_type === 'tenant'"
          id="scope_tenant"
          :label="$t('responsibilities.sheet.tenant')"
          :error="form.errors.scope_id"
        >
          <select id="scope_tenant" v-model="form.scope_id" :class="nativeSelectClass" data-testid="responsibility-tenant">
            <option v-for="tenant in options.tenants" :key="tenant.id" :value="String(tenant.id)">
              {{ tenant.shortname }}
            </option>
          </select>
        </FormFieldWrapper>

        <FormFieldWrapper
          v-else-if="form.scope_type === 'institution_type'"
          id="scope_type_target"
          :label="$t('responsibilities.sheet.type')"
          :error="form.errors.scope_id"
        >
          <SingleSelect
            v-model="selectedType"
            :options="options.types"
            label-field="title"
            value-field="id"
            :placeholder="$t('responsibilities.sheet.type_placeholder')"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          v-else
          id="scope_institution"
          :label="$t('responsibilities.sheet.institution')"
          :error="form.errors.scope_id"
        >
          <SingleSelect
            v-model="selectedInstitution"
            :options="options.institutions"
            label-field="name"
            value-field="id"
            :placeholder="$t('responsibilities.sheet.institution_pick')"
          />
        </FormFieldWrapper>
      </div>
    </SheetForm>

    <ConfirmDialog
      :open="removeTarget !== null"
      :title="$t('responsibilities.duty.remove_confirm', { name: removeTarget?.label ?? '' })"
      :confirm-label="$t('responsibilities.duty.remove')"
      destructive
      @update:open="!$event && (removeTarget = null)"
      @confirm="remove"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Compass, Plus, ShieldCheck } from 'lucide-vue-next';

import type { DutyResponsibilityItem, DutyResponsibilityOptions, ResponsibilityScopeValue } from './types';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { ConfirmDialog, FormSegmentedControl, OverviewSection, SheetForm, type FormSegmentOption } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { SingleSelect } from '@/Components/ui/single-select';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

const props = defineProps<{
  dutyId: string;
  /** Duty's own padalinys, the default target. */
  tenantId?: number | null;
  items: DutyResponsibilityItem[];
  roles: { id: string; name: string }[];
  canUpdate: boolean;
  /** Optional prop: loaded when the sheet opens. */
  options?: DutyResponsibilityOptions;
}>();

const nativeSelectClass = 'h-11 w-full border border-input bg-background px-3 text-sm';

const spotlight = useFeatureSpotlight('duty-responsibilities-v1');
const sheetOpen = ref(false);
const removeTarget = ref<DutyResponsibilityItem | null>(null);

const form = useForm({
  responsibility: 'student_rep_coordination',
  scope_type: 'tenant' as ResponsibilityScopeValue,
  scope_id: props.tenantId ? String(props.tenantId) : '',
});

const selectedResponsibility = computed(() =>
  props.options?.responsibilities.find(option => option.value === form.responsibility));

const scopeOptions = computed<FormSegmentOption<ResponsibilityScopeValue>[]>(() =>
  (selectedResponsibility.value?.scopes ?? ['tenant', 'institution_type', 'institution']).map(scope => ({
    value: scope,
    label: $t(`responsibilities.scopes.${scope}`),
  })));

// SingleSelect works on whole objects; the form stores the id.
const selectedType = computed({
  get: () => props.options?.types.find(type => type.id === form.scope_id) ?? null,
  set: (type: { id: string } | null) => { form.scope_id = type?.id ?? ''; },
});
const selectedInstitution = computed({
  get: () => props.options?.institutions.find(institution => institution.id === form.scope_id) ?? null,
  set: (institution: { id: string } | null) => { form.scope_id = institution?.id ?? ''; },
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
  form.post(route('duties.responsibilities.store', props.dutyId), {
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

  if (!target) {
    return;
  }

  router.delete(route('duties.responsibilities.destroy', { duty: props.dutyId, responsibility: target.id }), { preserveScroll: true });
}
</script>
