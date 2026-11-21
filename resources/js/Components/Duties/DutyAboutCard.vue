<template>
  <SectionCard :title="$t('Apie pareigas')" :icon="Info">
    <!-- v-html: sanitized on write (Duty / Dutiable::sanitizedHtmlTranslations) -->
    <div
      v-if="description"
      class="text-sm text-muted-foreground max-w-none"
      v-html="description"
    />
  </SectionCard>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Info } from '@lucide/vue';

import { SectionCard } from '@/Components/Patterns';

defineProps<{
  description?: string | null;
}>();
</script>
