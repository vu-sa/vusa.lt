<template>
  <LearningPageFrame :can-see-reps="canSeeReps">
    <LearningStepForm
      :layers="layers"
      :tenants="tenants"
      :institution-types="institutionTypes"
      :duty-types="dutyTypes"
    />
  </LearningPageFrame>
</template>

<script setup lang="ts">
import LearningPageFrame from '@/Features/Admin/Learning/LearningPageFrame.vue';
import LearningStepForm from '@/Features/Admin/Learning/LearningStepForm.vue';
import type { LearningLayer } from '@/Types/enums';
import type { LearningTenantOption, LearningTypeOption } from '@/Types/learning';

defineProps<{
  layers?: LearningLayer[];
  tenants?: LearningTenantOption[];
  institutionTypes?: LearningTypeOption[];
  dutyTypes?: LearningTypeOption[];
  canSeeReps?: boolean;
}>();
</script>
