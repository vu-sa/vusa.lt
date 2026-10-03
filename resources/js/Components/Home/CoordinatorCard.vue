<template>
  <OverviewSection
    v-if="coordinators.length > 0"
    :title="coordinators.length > 1 ? $t('Tavo koordinatoriai') : $t('Tavo koordinatorius')"
    :icon="UserRound"
    variant="home"
    data-slot="coordinator-card"
  >
    <ul :class="coordinators.length > 1 && 'divide-y divide-border/60'">
      <li
        v-for="coordinator in coordinators"
        :key="coordinator.id ?? coordinator.name"
        :class="['flex flex-wrap items-center gap-x-4 gap-y-3', coordinators.length > 1 && 'py-3']"
      >
        <div class="flex min-w-0 flex-1 basis-56 items-center gap-3">
          <UserAvatar :user="{ name: coordinator.name, profile_photo_path: coordinator.profile_photo_path }" :size="compact ? 32 : 40" />
          <div class="min-w-0">
            <p class="truncate font-bold text-foreground">
              {{ coordinator.name }}
            </p>
            <p v-if="coordinator.duty" class="line-clamp-2 text-xs text-muted-foreground">
              {{ coordinator.duty }}
            </p>
            <!-- Only worth saying when there is a choice of whom to ask. -->
            <p v-if="coordinators.length > 1 && coordinator.institutions?.length" class="line-clamp-2 text-xs text-muted-foreground">
              {{ $t('Kuruoja') }}: {{ coordinator.institutions.join(', ') }}
            </p>
          </div>
        </div>
        <Button v-if="coordinator.email" as-child variant="outline" size="sm" class="w-full sm:w-auto pointer-coarse:h-11">
          <a :href="`mailto:${coordinator.email}`">
            <Mail aria-hidden="true" />
            {{ $t('Parašyti') }}
          </a>
        </Button>
      </li>
    </ul>
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
  /** The institution managers the rep asks when stuck (O22), one per padalinys; empty when none is configured. */
  coordinators: HomeCoordinator[];
  /** A quieter one-line form for screens where the coordinator is a footnote, not a panel. */
  compact?: boolean;
}>();
</script>
