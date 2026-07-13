<template>
  <SectionCard :title="$t('Apie pareigas')" :icon="Info">
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div
      v-if="description"
      class="prose prose-sm prose-zinc max-w-none text-muted-foreground dark:prose-invert"
      v-html="description"
    />

    <div v-if="responsibilityItems.length > 0" :class="{ 'mt-5': description }">
      <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
        {{ $t('Atsakomybės') }}
      </h4>
      <ul class="space-y-1.5">
        <li
          v-for="(item, index) in responsibilityItems"
          :key="index"
          class="flex items-start gap-2 text-sm text-foreground"
        >
          <Check class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
          <span>{{ item }}</span>
        </li>
      </ul>
    </div>
  </SectionCard>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Info, Check } from 'lucide-vue-next';

import { SectionCard } from '@/Components/ui/section-card';

const props = defineProps<{
  description?: string | null;
  responsibilities?: string | null;
}>();

/** Responsibilities are stored as newline-separated text; render each line as a bullet. */
const responsibilityItems = computed(() => {
  if (!props.responsibilities) {
    return [];
  }
  return props.responsibilities
    .split('\n')
    .map((line) => line.replace(/^[-*•\s]+/, '').trim())
    .filter((line) => line.length > 0);
});
</script>
