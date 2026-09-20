<template>
  <fieldset class="space-y-4 border-0 p-0 m-0">
    <legend class="p-0 mb-1 block w-full">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-base font-semibold text-foreground">
          {{ title }}
        </h2>
        <div class="flex items-center gap-2">
          <span
            v-if="missingCount && missingCount > 0"
            class="text-xs text-[var(--status-attention)]"
          >
            {{ $t('Trūksta :count laukų', { count: String(missingCount) }) }}
          </span>
          <span
            v-if="badge"
            class="inline-flex items-center gap-1 border border-border bg-secondary px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
          >
            <Globe v-if="publicMarker" class="size-3" />
            {{ badge }}
          </span>
        </div>
      </div>
      <p v-if="description" class="mt-1 text-sm text-muted-foreground">
        {{ description }}
      </p>
    </legend>

    <div class="space-y-4 pt-1">
      <slot />
    </div>
  </fieldset>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Globe } from 'lucide-vue-next';

withDefaults(defineProps<{
  title: string;
  description?: string;
  badge?: string;
  publicMarker?: boolean;
  missingCount?: number;
}>(), {
  description: undefined,
  badge: undefined,
  missingCount: undefined,
});
</script>
