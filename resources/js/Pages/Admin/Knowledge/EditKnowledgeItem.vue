<template>
  <KnowledgeItemForm
    :item="item"
    :audience-types="audienceTypes"
    :can-target-everywhere="canTargetEverywhere"
    :responsibilities="responsibilities"
    :sections="sections"
    :tenants="tenants"
    :institution-types="institutionTypes"
    :duty-types="dutyTypes"
    :can-manage-audiences="canManageAudiences"
    :page-tenants="pageTenants"
    :can-publish-everywhere="canPublishEverywhere"
    :can-delete="canDelete"
  />
</template>

<script setup lang="ts">
import KnowledgeItemForm from '@/Features/Admin/Learning/KnowledgeItemForm.vue';
import type { LearningSectionOption } from '@/Features/Admin/Learning/types';
import type { LearningAudienceType } from '@/Types/enums';
import type { KnowledgeItemEditData, LearningTenantOption, LearningTypeOption } from '@/Types/learning';

defineProps<{
  item: KnowledgeItemEditData;
  audienceTypes: LearningAudienceType[];
  canTargetEverywhere: boolean;
  responsibilities: { id: string; title: string }[];
  sections: LearningSectionOption[];
  tenants?: LearningTenantOption[];
  institutionTypes?: LearningTypeOption[];
  dutyTypes?: LearningTypeOption[];
  canManageAudiences?: boolean;
  pageTenants?: LearningTenantOption[];
  canPublishEverywhere?: boolean;
  canDelete?: boolean;
}>();
</script>
