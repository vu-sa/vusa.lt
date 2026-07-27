<template>
  <div
    v-if="isStaging && !dismissed"
    data-slot="staging-status"
    role="status"
    class="rounded-xl border border-amber-300/70 bg-amber-100 text-amber-950 shadow-sm print:hidden dark:border-amber-800/70 dark:bg-amber-950/60 dark:text-amber-100"
  >
    <div class="flex min-h-11 items-center justify-between gap-3 px-3 py-2 text-sm sm:px-4">
      <div class="flex min-w-0 items-start gap-3 sm:items-center">
        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-200/70 sm:mt-0 dark:bg-amber-900/70">
          <AlertTriangle class="h-4 w-4 text-amber-800 dark:text-amber-300" />
        </div>
        <div class="flex min-w-0 flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
          <span class="shrink-0 font-bold">STAGING ENVIRONMENT</span>
          <span class="hidden text-amber-700 sm:inline dark:text-amber-300">|</span>
          <span
            v-if="hasSharedResources"
            class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-amber-800 sm:text-sm dark:text-amber-200"
          >
            <template v-for="(warning, index) in warnings" :key="warning">
              <span class="inline-flex items-center gap-1">
                <FileWarning v-if="warning.includes('File')" class="h-3 w-3" />
                <CloudOff v-if="warning.includes('SharePoint')" class="h-3 w-3" />
                {{ warning }}
              </span>
              <span v-if="index < warnings.length - 1" aria-hidden="true">•</span>
            </template>
          </span>
          <span v-else class="text-xs text-amber-800 sm:text-sm dark:text-amber-200">
            Test environment — data may differ from production
          </span>
        </div>
      </div>
      <button
        type="button"
        :class="[
          'shrink-0 rounded-lg p-1.5 transition-colors',
          'hover:bg-amber-200/70 dark:hover:bg-amber-900/60',
          'focus-visible:outline-none focus-visible:ring-2',
          'focus-visible:ring-amber-600 dark:focus-visible:ring-amber-400',
        ]"
        aria-label="Dismiss staging banner"
        @click="dismissed = true"
      >
        <X class="h-4 w-4" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { AlertTriangle, X, FileWarning, CloudOff } from 'lucide-vue-next';

const dismissed = ref(false);

interface StagingProps {
  isStaging: boolean;
  filesReadOnly: boolean;
  sharepointReadOnly: boolean;
}

const staging = computed(() => usePage().props.staging as StagingProps | undefined);

const isStaging = computed(() => staging.value?.isStaging ?? false);
const hasSharedResources = computed(() =>
  staging.value?.filesReadOnly || staging.value?.sharepointReadOnly,
);

const warnings = computed(() => {
  const list: string[] = [];
  if (staging.value?.filesReadOnly) {
    list.push('File storage is shared with production (read-only)');
  }
  if (staging.value?.sharepointReadOnly) {
    list.push('SharePoint is shared with production (read-only)');
  }
  return list;
});
</script>
