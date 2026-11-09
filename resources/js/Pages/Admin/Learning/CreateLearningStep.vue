<template>
  <LearningStepForm
    :layers="layers"
    :tenants="tenants"
    :institution-types="institutionTypes"
    :duty-types="dutyTypes"
  />
</template>

<script setup lang="ts">
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
