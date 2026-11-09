<template>
  <OverviewSection
    v-if="coordinators.length > 0"
    :title="coordinators.length > 1 ? $t('Tavo koordinatoriai') : $t('Tavo koordinatorius')"
    :icon="UserRound"
    variant="home"
    data-slot="coordinator-card"
  >
    <PeopleContactList :people="coordinators" :compact>
      <template #details="{ person: coordinator }">
        <p v-if="coordinator.duty" class="line-clamp-2 text-xs text-muted-foreground">
          <InflectedDutyName :name="coordinator.duty" :holder="coordinator" :use-original-duty-name="coordinator.use_original_duty_name" />
        </p>
        <!-- Only worth saying when there is a choice of whom to ask. -->
        <p v-if="coordinators.length > 1 && coordinator.institutions?.length" class="line-clamp-2 text-xs text-muted-foreground">
          {{ $t('Kuruoja') }}: {{ coordinator.institutions.join(', ') }}
        </p>
      </template>
    </PeopleContactList>
  </OverviewSection>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { UserRound } from '@lucide/vue';

import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import PeopleContactList from '@/Components/Avatars/PeopleContactList.vue';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';

import type { HomeCoordinator } from './types';

defineProps<{
  /** The institution managers the rep asks when stuck (O22), one per padalinys; empty when none is configured. */
  coordinators: HomeCoordinator[];
  /** A quieter one-line form for screens where the coordinator is a footnote, not a panel. */
  compact?: boolean;
}>();
</script>
