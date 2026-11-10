<template>
  <LearningPageFrame :can-see-reps="canSeeReps">
    <LearningStepForm
      :audience-types="audienceTypes"
      :can-target-everywhere="canTargetEverywhere"
      :responsibilities="responsibilities"
      :sections="sections"
      :tenants="tenants"
      :institution-types="institutionTypes"
      :duty-types="dutyTypes"
    />
  </LearningPageFrame>
</template>

<script setup lang="ts">
import LearningPageFrame from '@/Features/Admin/Learning/LearningPageFrame.vue';
import LearningStepForm from '@/Features/Admin/Learning/LearningStepForm.vue';
import type { LearningSectionOption } from '@/Features/Admin/Learning/types';
import type { LearningAudienceType } from '@/Types/enums';
import type { LearningTenantOption, LearningTypeOption } from '@/Types/learning';

defineProps<{
  audienceTypes: LearningAudienceType[];
  canTargetEverywhere: boolean;
  responsibilities: { id: string; title: string }[];
  sections: LearningSectionOption[];
  tenants?: LearningTenantOption[];
  institutionTypes?: LearningTypeOption[];
  dutyTypes?: LearningTypeOption[];
  canSeeReps?: boolean;
}>();
</script>
