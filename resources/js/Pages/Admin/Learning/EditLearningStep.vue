<template>
  <LearningStepForm
    :step
    :audience-types
    :can-target-everywhere
    :responsibilities
    :tenants
    :institution-types
    :duty-types
    :can-delete
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
