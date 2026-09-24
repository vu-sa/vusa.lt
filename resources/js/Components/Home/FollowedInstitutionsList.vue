<template>
  <OverviewSection
    :title="$t('Sekamos institucijos')"
    :icon="Eye"
    variant="home"
    :href="route('institutions.index', { followed: 1 })"
    :href-label="$t('Visos sekamos (:count)', { count: String(followed.total) })"
    data-slot="followed-institutions"
  >
    <ul class="divide-y divide-border/60">
      <li v-for="institution in followed.items" :key="institution.id" class="flex items-center gap-3 py-3">
        <Link
          :href="route('institutions.show', institution.id)"
          prefetch
          class="min-w-0 flex-1 truncate font-bold hover:text-brand"
        >
          {{ institution.name }}
        </Link>
        <BellOff
          v-if="institution.is_muted"
          class="size-3.5 shrink-0 text-muted-foreground"
          :aria-label="$t('Pranešimai nutildyti')"
        />
        <StatusBadge :status="institutionActivityStatuses[institution.activity_status as InstitutionActivityStatus]" />
      </li>
    </ul>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { BellOff, Eye } from 'lucide-vue-next';

import type { HomeFollowedInstitutions } from './types';

import { StatusBadge } from '@/Components/Patterns';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { institutionActivityStatuses } from '@/Constants/statuses';
import type { InstitutionActivityStatus } from '@/Types/enums';

/** Only rendered when something is followed; following is discovered on the institution pages. */
defineProps<{
  followed: HomeFollowedInstitutions;
}>();
</script>
