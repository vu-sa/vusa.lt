<template>
  <Link
    :href="route('institutions.show', item.id)"
    :class="[
      'flex items-center gap-3 rounded-lg border border-border bg-card p-3 text-left',
      'transition-colors hover:bg-accent/50',
      { 'opacity-60': item.authorized === false },
    ]"
  >
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent">
      <InstitutionIconFilled class="h-5 w-5 text-accent-foreground" />
    </div>

    <div class="min-w-0 flex-1">
      <p class="truncate text-sm font-medium text-foreground">
        {{ item.name }}
      </p>
      <div class="mt-0.5 flex flex-wrap items-center gap-1">
        <Badge variant="secondary" class="text-[10px]">
          {{ directionLabel }}
        </Badge>
        <Badge v-if="typeLabel" variant="outline" class="text-[10px]">
          {{ typeLabel }}
        </Badge>
        <Lock v-if="item.authorized === false" class="h-3 w-3 text-amber-500" />
      </div>
    </div>
  </Link>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Lock } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { InstitutionIconFilled } from '@/Components/icons';

export interface RelatedInstitutionItem {
  id: string | number;
  name: string;
  direction?: 'outgoing' | 'incoming' | 'sibling';
  type?: 'direct' | 'type-based' | 'within-type' | 'cross-tenant-sibling';
  authorized?: boolean;
}

const props = defineProps<{
  item: RelatedInstitutionItem;
}>();

const directionLabel = computed(() => {
  switch (props.item.direction) {
    case 'outgoing': return $t('relationships.direction_outgoing');
    case 'incoming': return $t('relationships.direction_incoming');
    case 'sibling': return $t('relationships.direction_sibling');
    default: return $t('relationships.related');
  }
});

const typeLabel = computed(() => {
  switch (props.item.type) {
    case 'within-type': return $t('Tipas ↔');
    case 'cross-tenant-sibling': return $t('Centrinis');
    case 'type-based': return $t('Tipas');
    default: return null;
  }
});
</script>
