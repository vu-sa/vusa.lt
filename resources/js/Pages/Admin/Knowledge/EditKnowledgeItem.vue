<template>
  <KnowledgeItemForm
    :item
    :audience-types
    :can-target-everywhere
    :responsibilities
    :sections
    :tenants
    :institution-types
    :duty-types
    :can-manage-audiences
    :page-tenants
    :can-publish-everywhere
    :can-delete
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
