<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import TenantSelectField from '@/Components/AdminForms/TenantSelectField.vue';
import { TypeTreeSelect } from '@/Components/AdminForms/TypeTree';
import { SheetForm } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { NativeSelect } from '@/Components/ui/native-select';
import { SingleSelect } from '@/Components/ui/single-select';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import InlineCollectionSelect from '@/Features/Admin/AdminSearch/Components/Select/InlineCollectionSelect.vue';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import DutySearchField, { type DutyHit } from '@/Features/Admin/Occupancy/DutySearchField.vue';
import { LearningAudienceType, LearningImportance, LearningStage, LearningStepKind } from '@/Types/enums';
import type { LearningAudienceDraft, LearningAudienceOptions } from './types';

const props = defineProps<LearningAudienceOptions & {
  audience?: LearningAudienceDraft | null;
  kind: LearningStepKind;
  isSystem?: boolean;
}>();
const open = defineModel<boolean>('open', { required: true });
const emit = defineEmits<{ save: [audience: LearningAudienceDraft] }>();
const form = useForm<LearningAudienceDraft>({
  type: LearningAudienceType.Everyone, source_id: null, tenant_id: null,
  importance: LearningImportance.Essential, stage: LearningStage.BeforeStart,
  description: { lt: '', en: '' }, order: 0, label: '',
});
const pickingInstitution = ref(false);
const targetLabel = ref('');
const selectedDuty = ref<DutyHit | null>(null);
const meetingPrep = computed(() => props.kind === LearningStepKind.MeetingPrep);
const hasStage = computed(() => props.kind === LearningStepKind.Action);
const fixedTarget = computed(() => props.isSystem || props.audience?.type === LearningAudienceType.Purpose);
const types = computed(() => props.audienceTypes.filter(type => type !== LearningAudienceType.Purpose && (!meetingPrep.value || [LearningAudienceType.InstitutionType, LearningAudienceType.Institution].includes(type))));
const typeOptions = computed(() => types.value.map(type => ({ value: type, label: $t(`learning.audience_types.${type}`) })));
const targetTypes = computed(() => form.type === LearningAudienceType.DutyType ? props.dutyTypes : props.institutionTypes);
const importanceOptions = computed(() => Object.values(LearningImportance).map(value => ({ value, label: $t(`learning.importance.${value}`) })));
const stageOptions = computed(() => Object.values(LearningStage).map(value => ({ value, label: $t(`learning.stages.${value}`) })));
const selectedResponsibility = computed({
  get: () => props.responsibilities.find(item => item.id === form.source_id) ?? null,
  set: (item: { id: string; title: string } | null) => { form.source_id = item?.id ?? null; },
});
const complete = computed(() => fixedTarget.value || (types.value.includes(form.type) && (form.type === LearningAudienceType.Everyone || Boolean(form.source_id)) && (props.canTargetEverywhere || form.tenant_id !== null)));
watch(() => form.type, () => { form.source_id = null; targetLabel.value = ''; selectedDuty.value = null; }, { flush: 'sync' });
watch(selectedDuty, duty => {
  if (form.type !== LearningAudienceType.Duty) return;
  form.source_id = duty?.id ?? null;
  targetLabel.value = duty?.name ?? '';
}, { flush: 'sync' });
watch(open, value => {
  if (!value) return;
  form.clearErrors();
  pickingInstitution.value = false;
  const audience = props.audience;
  form.type = audience?.type ?? types.value[0] ?? LearningAudienceType.Everyone;
  form.source_id = audience?.source_id ?? null;
  form.tenant_id = audience?.tenant_id ?? (!props.canTargetEverywhere && props.tenants.length === 1 ? props.tenants[0]!.id : null);
  form.importance = audience?.importance ?? LearningImportance.Essential;
  form.stage = hasStage.value ? audience?.stage ?? LearningStage.BeforeStart : null;
  form.description = { lt: audience?.description.lt ?? '', en: audience?.description.en ?? '' };
  form.order = audience?.order ?? 0;
  form.label = audience?.label ?? '';
  targetLabel.value = audience?.label ?? '';
  selectedDuty.value = audience?.type === LearningAudienceType.Duty && audience.source_id ? { id: audience.source_id, name: audience.label } : null;
  form.defaults();
});
function pickInstitution(hits: NormalizedSearchHit[]) {
  const hit = hits[0];
  if (hit) { form.source_id = hit.recordId; targetLabel.value = hit.title; }
  pickingInstitution.value = false;
}
function save() {
  if (!complete.value) return;
  const target = targetTypes.value.find(type => String(type.id) === form.source_id)?.title
    ?? props.responsibilities.find(item => item.id === form.source_id)?.title ?? targetLabel.value;
  const unit = props.tenants.find(tenant => tenant.id === form.tenant_id)?.shortname;
  const label = fixedTarget.value ? props.audience!.label
    : [$t(`learning.audience_types.${form.type}`), form.type === LearningAudienceType.Everyone ? '' : target].filter(Boolean).join(': ') + (unit ? ` · ${unit}` : '');
  emit('save', {
    ...(props.audience?.id ? { id: props.audience.id } : {}),
    type: form.type, source_id: form.type === LearningAudienceType.Everyone ? null : form.source_id,
    tenant_id: form.tenant_id, importance: form.importance, stage: hasStage.value ? form.stage : null,
    description: { ...form.description }, order: form.order, label,
  });
  form.defaults();
  open.value = false;
}
</script>

<template>
  <SheetForm v-model:open="open" :title="$t(audience ? 'learning_library.audiences.edit' : 'learning_library.audiences.add')" :dirty="form.isDirty" :disabled="!complete || pickingInstitution" required-note @submit="save">
    <div v-if="pickingInstitution" class="space-y-3">
      <Button type="button" variant="ghost" voice="sentence" class="min-h-11" @click="pickingInstitution = false">{{ $t('Atgal') }}</Button>
      <InlineCollectionSelect collection="institutions" :base-filter-by="canTargetEverywhere ? undefined : `tenant_id:=[${tenants.map(tenant => tenant.id).join(',') || '-1'}]`" :confirm-label="$t('Pasirinkti')" @confirm="pickInstitution" />
    </div>
    <template v-else>
      <p v-if="fixedTarget" class="text-sm font-semibold">{{ audience?.label }}</p>
      <template v-else>
        <FormFieldWrapper id="audience-type" :label="$t('learning_library.audiences.type')" required>
          <NativeSelect id="audience-type" v-model="form.type" :options="typeOptions" data-testid="audience-type" />
        </FormFieldWrapper>
        <DutySearchField v-if="form.type === LearningAudienceType.Duty" v-model="selectedDuty" :tenant-ids="canTargetEverywhere ? undefined : tenants.map(tenant => tenant.id)" />
        <FormFieldWrapper v-else-if="form.type === LearningAudienceType.DutyType || form.type === LearningAudienceType.InstitutionType" id="audience-target" :label="$t('learning_library.audiences.target')" required>
          <TypeTreeSelect id="audience-target" v-model="form.source_id" :types="targetTypes" as-string :placeholder="$t('Pasirinkti')" />
        </FormFieldWrapper>
        <FormFieldWrapper v-else-if="form.type === LearningAudienceType.Responsibility" id="audience-responsibility" :label="$t('learning_library.audiences.target')" required>
          <SingleSelect v-if="responsibilities.length > 16" v-model="selectedResponsibility" :options="responsibilities" label-field="title" value-field="id" />
          <NativeSelect v-else id="audience-responsibility" v-model="form.source_id" :placeholder-value="null" :options="responsibilities.map(item => ({ value: item.id, label: item.title }))" :placeholder="$t('Pasirinkti')" />
        </FormFieldWrapper>
        <FormFieldWrapper v-else-if="form.type === LearningAudienceType.Institution" id="audience-institution" :label="$t('learning_library.audiences.target')" required>
          <Button type="button" id="audience-institution" variant="outline" voice="sentence" class="min-h-11 w-full justify-start whitespace-normal text-left" @click="pickingInstitution = true">{{ targetLabel || $t('Pasirinkti instituciją…') }}</Button>
        </FormFieldWrapper>
        <TenantSelectField v-if="!canTargetEverywhere" id="audience-tenant" v-model="form.tenant_id" :tenants="tenants" />
        <FormFieldWrapper v-else id="audience-tenant" :label="$t('learning_library.fields.tenant')">
          <NativeSelect id="audience-tenant" v-model="form.tenant_id" :placeholder-value="null" placeholder-selectable :options="tenants.map(tenant => ({ value: tenant.id, label: tenant.shortname }))" :placeholder="$t('learning_library.audiences.all_units')" />
        </FormFieldWrapper>
      </template>
      <FormFieldWrapper v-if="!isSystem" id="audience-importance" :label="$t('learning_library.fields.importance')" :hint="$t('learning_library.fields.importance_hint')" required>
        <NativeSelect id="audience-importance" v-model="form.importance" :options="importanceOptions" />
      </FormFieldWrapper>
      <FormFieldWrapper v-if="hasStage" id="audience-stage" :label="$t('learning_library.fields.stage')" required>
        <NativeSelect id="audience-stage" v-model="form.stage" :options="stageOptions" data-testid="audience-stage" />
      </FormFieldWrapper>
      <template v-if="!isSystem">
        <FormFieldWrapper v-for="locale in (['lt', 'en'] as const)" :id="`audience-description-${locale}`" :key="locale" :label="$t(`learning_library.audiences.description_${locale}`)" :hint="$t('learning_library.audiences.description_hint')">
          <Textarea :id="`audience-description-${locale}`" v-model="form.description[locale]" rows="3" />
        </FormFieldWrapper>
      </template>
      <FormFieldWrapper v-if="isSystem" id="audience-order" :label="$t('learning_library.fields.order')">
        <Input id="audience-order" v-model.number="form.order" type="number" min="0" />
      </FormFieldWrapper>
    </template>
  </SheetForm>
</template>
