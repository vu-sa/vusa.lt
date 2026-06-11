<template>
  <SectionCard :title="$t('Skyrimas')" :icon="Gavel" content-size="compact">
    <dl class="space-y-3 text-sm">
      <div v-if="methodLabel" class="flex items-start justify-between gap-3">
        <dt class="text-muted-foreground">
          {{ $t('Būdas') }}
        </dt>
        <dd class="text-right font-medium text-foreground">
          {{ methodLabel }}
        </dd>
      </div>
      <div v-if="appointment.appointed_by" class="flex items-start justify-between gap-3">
        <dt class="text-muted-foreground">
          {{ $t('Skiria') }}
        </dt>
        <dd class="text-right font-medium text-foreground">
          {{ appointment.appointed_by }}
        </dd>
      </div>
      <div v-if="appointment.term_length" class="flex items-start justify-between gap-3">
        <dt class="text-muted-foreground">
          {{ $t('Kadencija') }}
        </dt>
        <dd class="text-right font-medium text-foreground">
          {{ appointment.term_length }}
        </dd>
      </div>
    </dl>
  </SectionCard>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Gavel } from 'lucide-vue-next';

import { SectionCard } from '@/Components/ui/section-card';

export interface DutyAppointment {
  selection_method?: string | null;
  appointed_by?: string | null;
  term_length?: string | null;
}

const props = defineProps<{
  appointment: DutyAppointment;
}>();

const METHOD_LABELS: Record<string, string> = {
  elected: 'Renkama',
  delegated: 'Deleguojama',
  appointed: 'Skiriama',
};

const methodLabel = computed(() => {
  const method = props.appointment.selection_method;
  if (!method) {
    return null;
  }
  return $t(METHOD_LABELS[method] ?? method);
});
</script>
