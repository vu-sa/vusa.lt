<template>
  <LearningStepForm
    :step="step"
    :layers="layers"
    :tenants="tenants"
    :institution-types="institutionTypes"
    :duty-types="dutyTypes"
    :can-delete="canDelete"
  />
</template>

<script setup lang="ts">
import LearningStepForm from '@/Features/Admin/Learning/LearningStepForm.vue';
import type { LearningLayer } from '@/Types/enums';
import type { LearningStepEditData, LearningTenantOption, LearningTypeOption } from '@/Types/learning';

defineProps<{
  step: LearningStepEditData;
  layers?: LearningLayer[];
  tenants?: LearningTenantOption[];
  institutionTypes?: LearningTypeOption[];
  dutyTypes?: LearningTypeOption[];
  canDelete?: boolean;
  canSeeReps?: boolean;
}>();
</script>
