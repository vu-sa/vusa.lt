<template>
  <div class="space-y-0.5 text-xs" data-slot="resource-managers">
    <p v-if="!hideLabel" class="text-muted-foreground">
      {{ $t('reservations.resource.managers') }}
    </p>
    <p v-if="managers.length === 0" class="text-muted-foreground">
      {{ $t('reservations.resource.no_managers') }}
    </p>
    <ul v-else class="flex flex-wrap gap-x-3 gap-y-1">
      <li v-for="manager in managers" :key="manager.id">
        <a
          v-if="manager.email"
          :href="`mailto:${manager.email}`"
          class="inline-flex min-h-6 items-center underline underline-offset-4 pointer-coarse:min-h-11"
        >{{ manager.name }}</a>
        <span v-else>{{ manager.name }}</span>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

export interface ResourceManagerContact {
  id: string;
  name: string;
  email: string | null;
}

defineProps<{ managers: ResourceManagerContact[]; hideLabel?: boolean }>();
</script>
