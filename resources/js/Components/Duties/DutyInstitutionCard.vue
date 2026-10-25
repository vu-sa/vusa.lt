<template>
  <EntityLinkCard
    :href="route('institutions.show', institution.id)"
    :eyebrow="$t('Institucija')"
    :title="institution.name"
    :icon="InstitutionIconFilled"
  >
    <template #trailing><InactiveInstitutionBadge :active="institution.is_active" /></template>
  </EntityLinkCard>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import { EntityLinkCard } from '@/Components/Patterns';
import { InstitutionIconFilled } from '@/Components/icons';

defineProps<{
  institution: { id: string | number; name: string; is_active?: boolean | number; short_name?: string | null };
}>();
</script>
