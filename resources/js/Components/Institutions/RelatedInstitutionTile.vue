<template>
  <Link
    :href="route('institutions.show', item.id)"
    :class="[
      'flex min-h-11 items-center gap-3 px-2 py-2.5 text-left transition-colors hover:bg-accent sm:px-3',
      { 'opacity-60': item.authorized === false },
    ]"
  >
    <EntityTypeMark type="institution" size="md" />

    <span class="min-w-0 flex-1">
      <span class="block truncate text-sm font-medium text-foreground">{{ item.name }}</span>
      <span v-if="typeLabel" class="block truncate text-xs text-muted-foreground">{{ typeLabel }}</span>
    </span>

    <Lock v-if="item.authorized === false" class="size-3.5 shrink-0 text-muted-foreground" :aria-label="$t('relationships.not_authorized')" />
  </Link>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Lock } from 'lucide-vue-next';

import EntityTypeMark from '@/Components/EntityTypeMark.vue';

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

const typeLabel = computed(() => {
  switch (props.item.type) {
    case 'within-type': return $t('Tos pačios rūšies');
    case 'cross-tenant-sibling': return $t('Centrinis');
    case 'type-based': return $t('Per tipą');
    default: return null;
  }
});
</script>
