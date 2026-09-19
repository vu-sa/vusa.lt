<template>
  <div v-if="message" role="status" class="mb-6 flex gap-3 border border-status-info-border bg-status-info-surface p-4 text-status-info">
    <Info class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
    <div>
      <h3 class="font-medium">
        {{ $t('System Announcement') }}
      </h3>
      <!-- eslint-disable-next-line vue/no-v-html -- HTML authored by admins in Nustatymai, rendered as the legacy shell does -->
      <div class="mt-1 text-sm" v-html="message" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Info } from 'lucide-vue-next';

defineProps<{ message?: string | null }>();
</script>
