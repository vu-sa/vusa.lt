<template>
  <section v-if="coordinator" class="flex flex-col gap-3" data-slot="coordinator-card">
    <h2 class="flex items-center gap-2 border-b border-border pb-3 text-sm font-bold uppercase tracking-[0.18em] text-foreground">
      <UserRound class="size-4 shrink-0 text-brand" aria-hidden="true" />
      {{ $t('Tavo koordinatorius') }}
    </h2>
    <div class="flex items-center gap-4">
      <UserAvatar :user="{ name: coordinator.name, profile_photo_path: coordinator.profile_photo_path }" :size="compact ? 32 : 48" />
      <div class="min-w-0 flex-1">
        <p class="truncate font-medium">
          {{ coordinator.name }}
        </p>
        <p v-if="coordinator.duty" class="truncate text-sm text-muted-foreground">
          {{ coordinator.duty }}
        </p>
      </div>
      <Button v-if="coordinator.email" as-child variant="outline" size="sm" class="pointer-coarse:h-11">
        <a :href="`mailto:${coordinator.email}`">
          <Mail aria-hidden="true" />
          {{ $t('Parašyti') }}
        </a>
      </Button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Mail, UserRound } from 'lucide-vue-next';

import type { HomeCoordinator } from './types';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Button } from '@/Components/ui/button';

defineProps<{
  /** The institution manager the rep asks when stuck (O22); null when none is configured. */
  coordinator: HomeCoordinator | null;
  /** A quieter one-line form for screens where the coordinator is a footnote, not a panel. */
  compact?: boolean;
}>();
</script>
