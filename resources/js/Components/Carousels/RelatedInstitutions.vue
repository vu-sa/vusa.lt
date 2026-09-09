<template>
  <div v-if="items.length > 0" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
    <RelatedInstitutionTile
      v-for="relatedInst in items"
      :key="relatedInst.id"
      :item="relatedInst"
    />
  </div>

  <!-- Empty state -->
  <div v-else class="py-8 text-center text-zinc-500 dark:text-zinc-400">
    <Link2 class="mx-auto mb-2 h-8 w-8 opacity-50" />
    <p>{{ $t('Nėra susijusių institucijų') }}</p>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Link2 } from 'lucide-vue-next';

import RelatedInstitutionTile, { type RelatedInstitutionItem } from '@/Components/Institutions/RelatedInstitutionTile.vue';

defineProps<{
  items: RelatedInstitutionItem[];
}>();
</script>
