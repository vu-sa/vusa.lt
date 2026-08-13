<template>
  <EntityLinkCard
    :href="route('institutions.show', institution.id)"
    :eyebrow="$t('Institucija')"
    :title="institution.name"
    :icon="InstitutionIconFilled"
  />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { EntityLinkCard } from '@/Components/Patterns';
import { InstitutionIconFilled } from '@/Components/icons';

defineProps<{
  institution: { id: string | number; name: string; short_name?: string | null };
}>();
</script>
