<template>
  <div v-if="flatRelatedInstitutions.length > 0" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
    <RelatedInstitutionTile
      v-for="relatedInst in flatRelatedInstitutions"
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
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Link2 } from 'lucide-vue-next';

import RelatedInstitutionTile, { type RelatedInstitutionItem } from '@/Components/Institutions/RelatedInstitutionTile.vue';

type RelatedInstitution = App.Entities.Institution & RelatedInstitutionItem;

const props = defineProps<{
  institution: App.Entities.Institution & {
    relatedInstitutionsFlat?: RelatedInstitution[];
  };
}>();

// Use the flat format if available, otherwise compute from the legacy format.
const flatRelatedInstitutions = computed<RelatedInstitution[]>(() => {
  // Prefer the new flat format
  if (props.institution.relatedInstitutionsFlat?.length) {
    return props.institution.relatedInstitutionsFlat;
  }

  // Fallback: Convert from legacy 4-way format
  const legacy = (props.institution as any).relatedInstitutions;
  if (!legacy) return [];

  const result: RelatedInstitution[] = [];

  // Outgoing direct
  legacy.outgoingDirect?.forEach((rel: any) => {
    if (rel.pivot?.related_model) {
      result.push({ ...rel.pivot.related_model, direction: 'outgoing', type: 'direct' });
    }
  });

  // Outgoing by type
  legacy.outgoingByType?.forEach((rel: any) => {
    rel.pivot?.related_model?.institutions?.forEach((inst: any) => {
      result.push({ ...inst, direction: 'outgoing', type: 'type-based' });
    });
  });

  // Incoming direct
  legacy.incomingDirect?.forEach((rel: any) => {
    if (rel.pivot?.relationshipable) {
      result.push({ ...rel.pivot.relationshipable, direction: 'incoming', type: 'direct' });
    }
  });

  // Incoming by type
  legacy.incomingByType?.forEach((rel: any) => {
    rel.pivot?.relationshipable?.institutions?.forEach((inst: any) => {
      result.push({ ...inst, direction: 'incoming', type: 'type-based' });
    });
  });

  // Dedupe by id
  const seen = new Set<string>();
  return result.filter((inst) => {
    if (seen.has(inst.id)) return false;
    seen.add(inst.id);
    return true;
  });
});
</script>
