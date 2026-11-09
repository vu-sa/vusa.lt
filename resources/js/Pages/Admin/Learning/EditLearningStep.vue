<template>
  <LearningPageFrame :can-see-reps="canSeeReps">
    <LearningStepForm
      :step="step"
      :layers="layers"
      :tenants="tenants"
      :institution-types="institutionTypes"
      :duty-types="dutyTypes"
      :can-delete="canDelete"
    />
  </LearningPageFrame>
</template>

<script setup lang="ts">
import LearningPageFrame from '@/Features/Admin/Learning/LearningPageFrame.vue';
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
