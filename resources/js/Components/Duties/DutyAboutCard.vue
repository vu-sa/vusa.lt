<template>
  <SectionCard :title="$t('Apie pareigas')" :icon="Info">
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div
      v-if="description"
      class="text-sm text-muted-foreground max-w-none"
      v-html="description"
    />

  </SectionCard>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Info } from 'lucide-vue-next';

import { SectionCard } from '@/Components/ui/section-card';

defineProps<{
  description?: string | null;
}>();
</script>
