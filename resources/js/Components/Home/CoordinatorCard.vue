<template>
  <OverviewSection v-if="coordinator" :title="$t('Tavo koordinatorius')" :icon="UserRound" variant="home" data-slot="coordinator-card">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
      <div class="flex min-w-0 flex-1 basis-56 items-center gap-3">
        <UserAvatar :user="{ name: coordinator.name, profile_photo_path: coordinator.profile_photo_path }" :size="compact ? 32 : 40" />
        <div class="min-w-0">
          <p class="truncate font-bold text-foreground">
            {{ coordinator.name }}
          </p>
          <p v-if="coordinator.duty" class="line-clamp-2 text-xs text-muted-foreground">
            {{ coordinator.duty }}
          </p>
        </div>
      </div>
      <Button v-if="coordinator.email" as-child variant="outline" size="sm" class="w-full sm:w-auto pointer-coarse:h-11">
        <a :href="`mailto:${coordinator.email}`">
          <Mail aria-hidden="true" />
          {{ $t('Parašyti') }}
        </a>
      </Button>
    </div>
  </OverviewSection>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Mail, UserRound } from 'lucide-vue-next';

import type { HomeCoordinator } from './types';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { Button } from '@/Components/ui/button';

defineProps<{
  /** The institution manager the rep asks when stuck (O22); null when none is configured. */
  coordinator: HomeCoordinator | null;
  /** A quieter one-line form for screens where the coordinator is a footnote, not a panel. */
  compact?: boolean;
}>();
</script>
