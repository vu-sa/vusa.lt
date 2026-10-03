<template>
  <div v-if="items.length > 0" class="space-y-6" data-slot="related-institutions">
    <section v-for="group in groups" :key="group.key" :data-group="group.key">
      <h3 class="border-b border-border pb-2 text-base font-semibold text-foreground">
        {{ group.label }}
        <span class="ml-1 text-sm font-normal text-muted-foreground">{{ group.items.length }}</span>
      </h3>
      <div class="divide-y divide-border">
        <RelatedInstitutionTile v-for="related in group.items" :key="related.id" :item="related" />
      </div>
    </section>
  </div>

  <EmptyState v-else :title="$t('Nėra susijusių institucijų')" :icon="Link2" />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Link2 } from 'lucide-vue-next';

import RelatedInstitutionTile, { type RelatedInstitutionItem } from '@/Components/Institutions/RelatedInstitutionTile.vue';
import { EmptyState } from '@/Components/Patterns';

const props = defineProps<{
  items: RelatedInstitutionItem[];
}>();

const ORDER = ['outgoing', 'incoming', 'sibling', 'other'] as const;

/** Who this body oversees, who oversees it, then peers — the question a reader arrives with. */
const groups = computed(() => ORDER
  .map(key => ({
    key,
    label: $t(`relationships.group_${key}`),
    items: props.items.filter(item => (item.direction ?? 'other') === key),
  }))
  .filter(group => group.items.length > 0));
</script>
