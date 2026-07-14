<template>
  <SectionCard
    :title="$t('Susijusios institucijos')"
    :icon="Link2"
    :count="related.length || undefined"
    :action-label="$t('Visos')"
    @action="$emit('view-all')"
  >
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <RelatedInstitutionTile
        v-for="item in previewRelated"
        :key="item.id"
        :item
      />
    </div>
  </SectionCard>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Link2 } from 'lucide-vue-next';

import RelatedInstitutionTile, { type RelatedInstitutionItem } from './RelatedInstitutionTile.vue';

import { SectionCard } from '@/Components/ui/section-card';

const props = defineProps<{
  related: RelatedInstitutionItem[];
}>();

defineEmits<{
  'view-all': [];
}>();

const previewRelated = computed(() => props.related.slice(0, 6));
</script>
