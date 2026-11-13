<template>
  <OverviewPage class="gap-6! lg:gap-8!" :title="$t('learning.pages.path')">
    <LearningPathView :path :coordinators :institution-contacts />
  </OverviewPage>
</template>

<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import LearningPathView from '@/Features/Admin/Learning/LearningPathView.vue';
import type { LearningPathData, LearningCoordinator, LearningInstitutionContacts } from '@/Types/learning';

defineProps<{ path: LearningPathData; coordinators: LearningCoordinator[]; institutionContacts: LearningInstitutionContacts[] }>();
</script>
