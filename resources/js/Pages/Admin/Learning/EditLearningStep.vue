<template>
  <LearningStepForm
    :step="step"
    :audience-types="audienceTypes"
    :can-target-everywhere="canTargetEverywhere"
    :responsibilities="responsibilities"
    :tenants="tenants"
    :institution-types="institutionTypes"
    :duty-types="dutyTypes"
    :can-delete="canDelete"
  />
</template>

<script setup lang="ts">
import LearningStepForm from '@/Features/Admin/Learning/LearningStepForm.vue';
import type { LearningAudienceType } from '@/Types/enums';
import type { LearningStepEditData, LearningTenantOption, LearningTypeOption } from '@/Types/learning';

defineProps<{
  step: LearningStepEditData;
  audienceTypes: LearningAudienceType[];
  canTargetEverywhere: boolean;
  responsibilities: { id: string; title: string }[];
  tenants?: LearningTenantOption[];
  institutionTypes?: LearningTypeOption[];
  dutyTypes?: LearningTypeOption[];
  canDelete?: boolean;
}>();
</script>
