<template>
  <dl v-if="createdAt || updatedAt" class="border border-border bg-background text-sm" data-slot="model-timestamps">
    <div v-if="createdAt" class="flex items-center justify-between gap-4 border-b border-border px-4 py-3 last:border-b-0">
      <dt class="text-muted-foreground">
        {{ $t('Sukurta') }}
      </dt>
      <dd class="text-right font-bold text-foreground tabular-nums">
        <time :datetime="createdAt">{{ formatDateTime(createdAt) }}</time>
      </dd>
    </div>
    <div v-if="updatedAt" class="flex items-center justify-between gap-4 px-4 py-3">
      <dt class="text-muted-foreground">
        {{ $t('Atnaujinta') }}
      </dt>
      <dd class="text-right font-bold text-foreground tabular-nums">
        <time :datetime="updatedAt">{{ formatDateTime(updatedAt) }}</time>
      </dd>
    </div>
  </dl>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { formatDateTime } from '@/Utils/dateTime';

defineProps<{
  createdAt?: string | null;
  updatedAt?: string | null;
}>();
</script>
