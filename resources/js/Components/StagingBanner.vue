<template>
  <Teleport to="body">
    <div
      v-if="isStaging && !dismissed"
      class="fixed top-0 left-0 right-0 z-[9999] bg-amber-500 text-amber-950 px-4 py-2 text-sm font-medium shadow-lg print:hidden"
    >
      <div class="container mx-auto flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <AlertTriangle class="h-5 w-5 shrink-0" />
          <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
            <span class="font-bold">STAGING ENVIRONMENT</span>
            <span class="hidden sm:inline text-amber-800">|</span>
            <span v-if="hasSharedResources" class="text-amber-900 text-xs sm:text-sm">
              <template v-for="(warning, index) in warnings" :key="warning">
                <span class="inline-flex items-center gap-1">
                  <FileWarning v-if="warning.includes('File')" class="h-3 w-3" />
                  <CloudOff v-if="warning.includes('SharePoint')" class="h-3 w-3" />
                  {{ warning }}
                </span>
                <span v-if="index < warnings.length - 1" class="mx-2">•</span>
              </template>
            </span>
            <span v-else class="text-amber-900 text-xs sm:text-sm">
              Test environment — data may differ from production
            </span>
          </div>
        </div>
        <button
          class="p-1.5 hover:bg-amber-600/50 rounded-md transition-colors"
          aria-label="Dismiss staging banner"
          @click="dismissed = true"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
    </div>
  </Teleport>

  <!-- Spacer to prevent content from being hidden behind the banner -->
  <div v-if="isStaging && !dismissed" class="h-10 print:hidden" />
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
