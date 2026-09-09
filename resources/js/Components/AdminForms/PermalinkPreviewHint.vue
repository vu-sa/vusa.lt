<template>
  <div v-if="preview || isChecking" class="space-y-1.5">
    <div class="flex items-center gap-2 text-sm text-muted-foreground">
      <IFluentLink24Regular class="h-4 w-4 shrink-0" />
      <span v-if="isChecking" class="italic">{{ $t('Skaičiuojama...') }}</span>
      <span v-else-if="preview" class="truncate font-mono">{{ preview.url }}</span>
    </div>
    <p v-if="preview" class="flex items-center gap-1 text-xs text-muted-foreground">
      <IFluentInfo16Regular class="h-3.5 w-3.5 shrink-0" />
      {{ $t('Nuorodą galėsite pakeisti vėliau, redaguodami įrašą.') }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

defineProps<{
  preview: { permalink: string; url: string } | null;
  isChecking?: boolean;
}>();
</script>
