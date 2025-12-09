<template>
  <div v-if="flatRelatedInstitutions.length > 0" class="grid grid-cols-ramFill gap-4">
    <InstitutionCard 
      v-for="relatedInst in flatRelatedInstitutions"
      :key="relatedInst.id" 
      :institution="relatedInst"
      @click="handleClick(relatedInst)"
    >
      <template #header-extra>
        <!-- Direction indicator: outgoing (→) or incoming (←) -->
        <component 
          :is="relatedInst.direction === 'outgoing' ? IFluentArrowExportLtr24Regular : IFluentArrowImportLtr24Regular"
          class="h-4 w-4"
          :class="relatedInst.direction === 'outgoing' ? 'text-blue-500' : 'text-green-500'"
          :title="relatedInst.direction === 'outgoing' ? $t('Išeinantis ryšys') : $t('Įeinantis ryšys')"
        />
        <!-- Type indicator: direct connection or through type -->
        <span 
          v-if="relatedInst.type === 'type-based'" 
          class="text-[10px] text-zinc-400 px-1 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800"
          :title="$t('Ryšys per institucijos tipą')"
        >
          {{ $t('Tipas') }}
        </span>
      </template>
    </InstitutionCard>
  </div>
  
  <!-- Empty state -->
  <div v-else class="text-center py-8 text-zinc-500 dark:text-zinc-400">
    <IFluentLink24Regular class="h-8 w-8 mx-auto mb-2 opacity-50" />
    <p>{{ $t('Nėra susijusių institucijų') }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import InstitutionCard from "../Cards/InstitutionCard.vue";
import IFluentArrowExportLtr24Regular from '~icons/fluent/arrow-export-ltr-24-regular';
import IFluentArrowImportLtr24Regular from '~icons/fluent/arrow-import-24-regular';
import IFluentLink24Regular from '~icons/fluent/link-24-regular';

interface RelatedInstitution extends App.Entities.Institution {
  direction: 'outgoing' | 'incoming';
  type: 'direct' | 'type-based';
}

const props = defineProps<{
  institution: App.Entities.Institution & {
    relatedInstitutionsFlat?: RelatedInstitution[];
  };
}>();

// Use the flat format if available, otherwise compute from legacy format
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
  return result.filter(inst => {
    if (seen.has(inst.id)) return false;
    seen.add(inst.id);
    return true;
  });
});

const handleClick = (institution: App.Entities.Institution) => {
  router.visit(route("institutions.show", institution.id));
};
</script>
