<template>
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
        <span
          v-if="institution.tenant_name || institution.effective_days_since_activity !== null"
          class="block text-xs text-muted-foreground"
        >
          <template v-if="institution.tenant_name">{{ institution.tenant_name }}</template>
          <template v-if="institution.tenant_name && institution.effective_days_since_activity !== null"> · </template>
          <template v-if="institution.effective_days_since_activity !== null">
            {{ $t('Paskutinė veikla prieš :days d.', { days: String(institution.effective_days_since_activity) }) }}
          </template>
        </span>
      </span>
      <span class="flex items-center gap-3">
        <StatusBadge :status="institutionActivityStatuses[institution.status as InstitutionActivityStatus]" />
        <Button variant="outline" size="sm" class="pointer-coarse:h-11" @click="emit('record', institution)">
          {{ $t('Fiksuoti veiklą') }}
        </Button>
      </span>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import type { InstitutionActivityInsight } from './types';

import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { institutionActivityStatuses } from '@/Constants/statuses';
import type { InstitutionActivityStatus } from '@/Types/enums';

defineProps<{
  institutions: InstitutionActivityInsight[];
}>();

const emit = defineEmits<{
  record: [institution: InstitutionActivityInsight];
}>();
</script>
