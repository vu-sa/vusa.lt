<template>
  <div
    v-if="isStaging && !compact && !dismissed"
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
          <span class="shrink-0 font-bold uppercase">{{ $t('Bandomoji aplinka') }}</span>
          <span class="hidden text-amber-700 sm:inline dark:text-amber-300">|</span>
          <span
            v-if="hasSharedResources"
            class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-amber-800 sm:text-sm dark:text-amber-200"
          >
            <template v-for="(warning, index) in warnings" :key="warning.label">
              <span class="inline-flex items-center gap-1">
                <FileWarning v-if="warning.kind === 'files'" class="h-3 w-3" />
                <CloudOff v-if="warning.kind === 'sharepoint'" class="h-3 w-3" />
                {{ $t(warning.label) }}
              </span>
              <span v-if="index < warnings.length - 1" aria-hidden="true">•</span>
            </template>
          </span>
          <span v-else class="text-xs text-amber-800 sm:text-sm dark:text-amber-200">
            {{ $t('Bandomosios aplinkos duomenys gali skirtis nuo tikrosios') }}
          </span>
        </div>
      </div>
      <button
        type="button"
        :class="[
          'flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-lg transition-colors',
          'hover:bg-amber-200/70 dark:hover:bg-amber-900/60',
          'focus-visible:outline-none focus-visible:ring-2',
          'focus-visible:ring-amber-600 dark:focus-visible:ring-amber-400',
        ]"
        :aria-label="$t('Suskleisti bandomosios aplinkos įspėjimą')"
        @click="emit('update:dismissed', true)"
      >
        <X class="h-4 w-4" />
      </button>
    </div>
  </div>
  <button
    v-else-if="isStaging && compact && dismissed"
    type="button"
    data-slot="staging-warning-button"
    :aria-label="`${$t('Atverti bandomosios aplinkos įspėjimą')}: ${warningText}`"
    :title="`${$t('Bandomoji aplinka')}: ${warningText}`"
    :class="[
      'flex size-11 shrink-0 items-center justify-center',
      'border border-status-attention-border bg-status-attention-surface text-status-attention',
      'transition-colors hover:bg-secondary focus-visible:outline-2 focus-visible:outline-ring',
    ]"
    @click="emit('update:dismissed', false)"
  >
    <AlertTriangle class="size-5" aria-hidden="true" />
  </button>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { AlertTriangle, X, FileWarning, CloudOff } from 'lucide-vue-next';

defineProps<{ dismissed?: boolean; compact?: boolean }>();
const emit = defineEmits<{ 'update:dismissed': [value: boolean] }>();

interface StagingProps {
  isStaging: boolean;
  filesReadOnly: boolean;
  sharepointReadOnly: boolean;
}

const staging = computed(() => usePage().props.staging as StagingProps | undefined);

const isStaging = computed(() => staging.value?.isStaging ?? false);

const warnings = computed(() => {
  const list: { kind: 'files' | 'sharepoint'; label: string }[] = [];
  if (staging.value?.filesReadOnly) {
    list.push({ kind: 'files', label: 'Failų saugykla bendrinama su tikrąja aplinka (tik skaitymui)' });
  }
  if (staging.value?.sharepointReadOnly) {
    list.push({ kind: 'sharepoint', label: 'SharePoint bendrinama su tikrąja aplinka (tik skaitymui)' });
  }
  else if (staging.value?.isStaging) {
    list.push({ kind: 'sharepoint', label: 'SharePoint failai įkeliami į bandomąją svetainę' });
  }
  return list;
});

const warningText = computed(() => warnings.value.map(warning => $t(warning.label)).join('; '));
const hasSharedResources = computed(() => warnings.value.length > 0);
</script>
