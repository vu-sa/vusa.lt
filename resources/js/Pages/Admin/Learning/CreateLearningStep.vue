<template>
  <LearningStepForm
    :audience-types="audienceTypes"
    :can-target-everywhere="canTargetEverywhere"
    :responsibilities="responsibilities"
    :tenants="tenants"
    :institution-types="institutionTypes"
    :duty-types="dutyTypes"
  />
</template>

<script setup lang="ts">
import LearningStepForm from '@/Features/Admin/Learning/LearningStepForm.vue';
import type { LearningAudienceType } from '@/Types/enums';
import type { LearningTenantOption, LearningTypeOption } from '@/Types/learning';

defineProps<{
  audienceTypes: LearningAudienceType[];
  canTargetEverywhere: boolean;
  responsibilities: { id: string; title: string }[];
  tenants?: LearningTenantOption[];
  institutionTypes?: LearningTypeOption[];
  dutyTypes?: LearningTypeOption[];
}>();
</script>
