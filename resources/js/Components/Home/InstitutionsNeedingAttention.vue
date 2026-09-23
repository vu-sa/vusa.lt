<template>
  <OverviewSection
    :title="title ?? $t('Tavo institucijos')"
    :icon="Landmark"
    variant="home"
    :empty="institutions.length === 0"
    :empty-text="$t('visos institucijos posėdžius fiksuoja laiku')"
  >
    <ul class="divide-y divide-border/60" data-slot="institutions-needing-attention">
      <li v-for="institution in institutions" :key="institution.id" class="flex flex-wrap items-center gap-x-4 gap-y-2 py-4">
        <span class="min-w-0 flex-1 basis-48">
          <Link
            :href="route('institutions.show', institution.id)"
            prefetch
            class="block text-pretty font-bold hover:text-brand"
          >
            {{ institution.name }}
          </Link>
          <span v-if="institution.effective_days_since_activity !== null" class="block text-xs text-muted-foreground">
            {{ $t('Paskutinė veikla prieš :days d.', { days: String(institution.effective_days_since_activity) }) }}
          </span>
        </span>
        <span class="flex items-center gap-3">
          <StatusBadge :status="institutionActivityStatuses[institution.status as InstitutionActivityStatus]" />
          <Button variant="outline" size="sm" class="pointer-coarse:h-11" @click="emit('record', institution)">
            {{ $t('Fiksuoti posėdį') }}
          </Button>
        </span>
      </li>
    </ul>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Landmark } from 'lucide-vue-next';

import type { InstitutionActivityInsight } from './types';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { institutionActivityStatuses } from '@/Constants/statuses';
import type { InstitutionActivityStatus } from '@/Types/enums';

defineProps<{
  institutions: InstitutionActivityInsight[];
  title?: string;
}>();

const emit = defineEmits<{
  record: [institution: InstitutionActivityInsight];
}>();
</script>
