<template>
  <FormPage
    :title="isEditing ? (goalTitle || $t('goals.new')) : $t('goals.new')"
    :bar-title
    :lead="isEditing ? undefined : $t('goals.lead')"
    :entity-type="ModelEnum.GOAL"
    required-note
    :back-href="isEditing && form.id ? route('goals.show', form.id) : route('goals.index')"
    :back-label="isEditing ? $tChoice('entities.goal.model', 1) : $t('shell.sections.tikslai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isEditing ? 'edit' : 'create'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    :missing-locale-counts
    :activity-subject="isEditing && form.id ? { type: 'goal', id: String(form.id) } : undefined"
    @update:locale="activeLocale = $event"
    @submit="emit('submit:form', form)"
  >
    <template #title-status>
      <StatusBadge :status="goalStatuses[form.status as GoalStatus]" />
      <Badge variant="outline" class="text-xs">
        {{ $t('goals.experimental') }}
      </Badge>
    </template>

    <p class="max-w-prose text-sm leading-relaxed text-muted-foreground">
      {{ $t('goals.form.about_intro') }}
    </p>

    <FormFieldWrapper
      id="goal-title"
      :label="capitalize($t('entities.goal.title'))"
      :required="!form.title[otherLocale].trim()"
      :error="form.errors[`title.${activeLocale}`]"
    >
      <Input id="goal-title" v-model="form.title[activeLocale]" :class="['h-11', fieldSurfaceClass]" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="goal-expected-result"
      :label="capitalize($t('entities.goal.expected_result'))"
      :hint="$t('goals.form.expected_result_hint')"
      :error="form.errors[`expected_result.${activeLocale}`]"
    >
      <Textarea id="goal-expected-result" v-model="form.expected_result[activeLocale]" rows="2" :class="fieldSurfaceClass" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="goal-description"
      :label="capitalize($t('entities.goal.description'))"
      :error="form.errors[`description.${activeLocale}`]"
    >
      <TiptapEditor :key="activeLocale" v-model="form.description[activeLocale]" tools="description" html />
    </FormFieldWrapper>

    <FormFieldWrapper
      v-if="isEditing"
      id="goal-evaluation"
      :label="capitalize($t('entities.goal.evaluation'))"
      :hint="$t('goals.form.evaluation_intro')"
      :error="form.errors[`evaluation.${activeLocale}`]"
    >
      <TiptapEditor :key="activeLocale" v-model="form.evaluation[activeLocale]" tools="description" html />
    </FormFieldWrapper>

    <template #aside>
      <FormPanel :title="$t('goals.form.where')" :icon="CircleDot">
        <TenantSelectField
          id="goal-tenant"
          :model-value="form.tenant_id"
          :tenants
          required
          :error="form.errors.tenant_id"
          @update:model-value="onTenantChanged"
        />

        <FormFieldWrapper id="goal-cadence" :label="capitalize($t('entities.goal.cadence'))" :error="form.errors.cadence_id">
          <NativeSelect
            id="goal-cadence"
            v-model="form.cadence_id"
            placeholder-selectable
            :placeholder-value="null"
            :options="cadenceOptions"
            :placeholder="$t('goals.form.no_cadence')"
          />
        </FormFieldWrapper>

        <FormFieldWrapper id="goal-status" :label="capitalize($t('entities.goal.status'))" required :error="form.errors.status">
          <!-- One status per row: five labels do not fit the aside's width side by side. -->
          <FormSegmentedControl
            v-model="form.status"
            :options="statusOptions"
            :aria-label="$t('entities.goal.status')"
            test-id-prefix="goal-status"
            class="h-auto grid-cols-1 pointer-coarse:h-auto [&>button]:min-h-10 [&>button]:justify-start [&>button]:px-3 pointer-coarse:[&>button]:min-h-11"
          />
        </FormFieldWrapper>

        <FormFieldWrapper id="goal-duty" :label="capitalize($t('entities.goal.responsible_duty'))" :error="form.errors.responsible_duty_id">
          <SingleSelect
            v-model="selectedDuty"
            :options="tenantDuties"
            label-field="label"
            value-field="id"
            :placeholder="$t('goals.form.no_duty')"
            variant="surface"
          />
        </FormFieldWrapper>

        <div class="flex items-start gap-3">
          <Checkbox id="goal-is-public" v-model="form.is_public" class="mt-0.5" />
          <div class="space-y-1">
            <Label for="goal-is-public">{{ $t('goals.filters.public') }}</Label>
            <p class="text-xs text-muted-foreground">
              {{ $t('goals.form.is_public_hint') }}
            </p>
          </div>
        </div>
      </FormPanel>
    </template>

    <template v-if="isEditing && enableDelete" #danger-zone>
      <Button
        type="button"
        variant="outline"
        voice="sentence"
        class="border-destructive/40 text-destructive hover:border-destructive hover:bg-destructive hover:text-destructive-foreground pointer-coarse:min-h-11"
        @click="deleteConfirmOpen = true"
      >
        <Trash2 class="size-4" />
        {{ $t('goals.delete') }}
      </Button>

      <ConfirmDialog
        v-model:open="deleteConfirmOpen"
        :title="$t('goals.delete_confirm')"
        :description="$t('goals.delete_description')"
        :confirm-label="$t('Ištrinti')"
        destructive
        @confirm="emit('delete')"
      />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { CircleDot, Trash2 } from '@lucide/vue';
import { capitalize, computed, ref } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import TenantSelectField from '@/Components/AdminForms/TenantSelectField.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import FormPanel from '@/Components/Patterns/FormPanel.vue';
import FormSegmentedControl, { type FormSegmentOption } from '@/Components/Patterns/FormSegmentedControl.vue';
import StatusBadge from '@/Components/Patterns/StatusBadge.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { NativeSelect } from '@/Components/ui/native-select';
import SingleSelect from '@/Components/ui/single-select/SingleSelect.vue';
import { Textarea } from '@/Components/ui/textarea';
import { goalStatuses, statusRoleClasses } from '@/Constants/statuses';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { GoalStatus, ModelEnum } from '@/Types/enums';

interface Translated { lt: string; en: string }
interface DutyOption { id: string; name: string; institution: string | null; tenant_id: number | null }

const props = defineProps<{
  goal?: Record<string, unknown> | null;
  tenants: { id: number; shortname: string }[];
  cadences: { id: string; label: string }[];
  statuses: { value: string; label: string }[];
  duties: DutyOption[];
  enableDelete?: boolean;
}>();

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const activeLocale = ref<'lt' | 'en'>('lt');
const otherLocale = computed(() => (activeLocale.value === 'lt' ? 'en' : 'lt'));
const deleteConfirmOpen = ref(false);

const asTranslated = (value: unknown): Translated => {
  const text = (value && typeof value === 'object' && !Array.isArray(value) ? value : {}) as Partial<Translated>;
  return { lt: text.lt ?? '', en: text.en ?? '' };
};

const source = (props.goal ?? {}) as Record<string, unknown>;

const form = useForm({
  id: (source.id ?? undefined) as string | undefined,
  title: asTranslated(source.title),
  description: asTranslated(source.description),
  expected_result: asTranslated(source.expected_result),
  evaluation: asTranslated(source.evaluation),
  tenant_id: (source.tenant_id ?? null) as number | null,
  cadence_id: (source.cadence_id ?? null) as string | null,
  responsible_duty_id: (source.responsible_duty_id ?? null) as string | null,
  status: (source.status ?? GoalStatus.Planned) as string,
  is_public: Boolean(source.is_public),
});

const isEditing = computed(() => Boolean(form.id));
const goalTitle = computed(() => form.title.lt || form.title.en);
const barTitle = computed(() => (isEditing.value ? getTranslatedValue(source.title) || $t('goals.new') : $t('goals.new')));

const fieldIds = {
  'title.lt': 'goal-title',
  'title.en': 'goal-title',
  'tenant_id': 'goal-tenant',
  'cadence_id': 'goal-cadence',
  'status': 'goal-status',
  'responsible_duty_id': 'goal-duty',
};

const missingLocaleCounts = computed(() => ({
  lt: form.title.lt.trim() === '' ? 1 : 0,
  en: form.title.en.trim() === '' ? 1 : 0,
}));

const onTenantChanged = (tenantId: number | null) => {
  // A duty belongs to one padalinys; switching padalinys drops it.
  if (tenantId !== form.tenant_id) {
    form.responsible_duty_id = null;
  }
  form.tenant_id = tenantId;
};

// The chosen status reads in the same colours as its tag in the list and on the record.
const statusOptions = computed<FormSegmentOption<string>[]>(() => props.statuses.map((option) => {
  const presentation = goalStatuses[option.value as GoalStatus];

  return {
    value: option.value,
    label: option.label,
    icon: presentation?.icon,
    activeClass: presentation ? statusRoleClasses[presentation.role] : undefined,
  };
}));

const cadenceOptions = computed(() => props.cadences.map(c => ({ value: c.id, label: c.label })));

const tenantDuties = computed(() => props.duties
  .filter(duty => duty.tenant_id === form.tenant_id)
  .map(duty => ({ ...duty, label: duty.institution ? `${duty.name} · ${duty.institution}` : duty.name })));

// SingleSelect works on whole objects; the form stores the id.
const selectedDuty = computed({
  get: () => tenantDuties.value.find(duty => duty.id === form.responsible_duty_id) ?? null,
  set: (duty: { id: string } | null) => {
    form.responsible_duty_id = duty?.id ?? null;
  },
});
</script>
