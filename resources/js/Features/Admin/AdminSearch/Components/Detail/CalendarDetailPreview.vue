<template>
  <DetailLayout
    :icon="CalendarIcon"
    :kicker="$t('Kalendoriaus įvykis')"
    :title="displayTitle"
    :subtitle="dateRange"
  >
    <template #badges>
      <Badge v-if="event.lang" variant="outline" class="uppercase">
        {{ event.lang }}
      </Badge>
      <Badge v-if="event.tenant_name" variant="secondary">
        {{ event.tenant_name }}
      </Badge>
    </template>

    <template #actions>
      <Link :href="route('calendar.edit', event.id)">
        <Button size="sm">
          <Pencil class="mr-2 size-4" />
          {{ $t('Redaguoti') }}
        </Button>
      </Link>
    </template>

    <div class="divide-y rounded-lg border px-4">
      <DetailRow :label="$t('Pavadinimas (LT)')" :value="event.title_lt || event.title || '—'" />
      <DetailRow :label="$t('Pavadinimas (EN)')" :value="event.title_en || '—'" />
      <DetailRow :label="$t('Pradžia')" :value="formatDate(event.date) || '—'" />
      <DetailRow v-if="event.end_date" :label="$t('Pabaiga')" :value="formatDate(event.end_date)" />
      <DetailRow v-if="event.tenant_name" :label="$t('Padalinys')" :value="event.tenant_name" />
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t, getActiveLanguage } from 'laravel-vue-i18n';
import { Pencil } from 'lucide-vue-next';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';

import { CalendarIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import type { CalendarSearchResult } from '@/Shared/Search/types';

const props = defineProps<{
  event: CalendarSearchResult;
}>();

const displayTitle = computed(() => {
  const lang = getActiveLanguage();
  if (lang === 'en' && props.event.title_en) return props.event.title_en;
  return props.event.title_lt || props.event.title || $t('Be pavadinimo');
});

const formatDate = (timestamp?: number): string | undefined => {
  if (!timestamp) return undefined;
  return new Date(timestamp * 1000).toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const dateRange = computed(() => {
  const start = formatDate(props.event.date);
  const end = formatDate(props.event.end_date);
  if (!start) return undefined;
  if (end) return `${start} — ${end}`;
  return start;
});
</script>
