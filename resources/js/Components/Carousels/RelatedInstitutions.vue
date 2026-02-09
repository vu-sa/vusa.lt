<template>
  <div v-if="flatRelatedInstitutions.length > 0" class="grid grid-cols-ramFill gap-4">
    <InstitutionCard
      v-for="relatedInst in flatRelatedInstitutions"
      :key="relatedInst.id"
      :institution="relatedInst"
      :class="{ 'opacity-60': relatedInst.authorized === false }"
      @click="handleClick(relatedInst)"
    >
      <template #header-extra>
        <!-- Direction indicator: outgoing (→), incoming (←), or sibling (↔) -->
        <component
          :is="getDirectionIcon(relatedInst.direction)"
          class="h-4 w-4"
          :class="getDirectionClass(relatedInst.direction)"
          :title="getDirectionTitle(relatedInst.direction)"
        />
        <!-- Authorization indicator for incoming (no access) -->
        <IFluentLockClosed24Regular
          v-if="relatedInst.authorized === false"
          class="h-3.5 w-3.5 text-amber-500"
          :title="$t('relationships.not_authorized')"
        />
        <!-- Type indicator: direct connection, through type, within-type, or cross-tenant -->
        <span
          v-if="relatedInst.type === 'type-based' || relatedInst.type === 'within-type' || relatedInst.type === 'cross-tenant-sibling'"
          class="text-[10px] text-zinc-400 px-1 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800"
          :title="getTypeTitle(relatedInst.type)"
        >
          {{ getTypeLabel(relatedInst.type) }}
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
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import InstitutionCard from '../Cards/InstitutionCard.vue';

import IFluentArrowExportLtr24Regular from '~icons/fluent/arrow-export-ltr-24-regular';
import IFluentArrowImportLtr24Regular from '~icons/fluent/arrow-import-24-regular';
import IFluentArrowSwap24Regular from '~icons/fluent/arrow-swap-24-regular';
import IFluentLink24Regular from '~icons/fluent/link-24-regular';
import IFluentLockClosed24Regular from '~icons/fluent/lock-closed-24-regular';

interface RelatedInstitution extends App.Entities.Institution {
  direction: 'outgoing' | 'incoming' | 'sibling';
  type: 'direct' | 'type-based' | 'within-type' | 'cross-tenant-sibling';
  authorized?: boolean;
}

const props = defineProps<{
  institution: App.Entities.Institution & {
    relatedInstitutionsFlat?: RelatedInstitution[];
  };
}>();

// Helper functions for direction-based styling
const getDirectionIcon = (direction: RelatedInstitution['direction']) => {
  switch (direction) {
    case 'outgoing': return IFluentArrowExportLtr24Regular;
    case 'incoming': return IFluentArrowImportLtr24Regular;
    case 'sibling': return IFluentArrowSwap24Regular;
    default: return IFluentLink24Regular;
  }
};

const getDirectionClass = (direction: RelatedInstitution['direction']) => {
  switch (direction) {
    case 'outgoing': return 'text-blue-500';
    case 'incoming': return 'text-amber-500';
    case 'sibling': return 'text-purple-500';
    default: return 'text-zinc-500';
  }
};

const getDirectionTitle = (direction: RelatedInstitution['direction']) => {
  switch (direction) {
    case 'outgoing': return $t('relationships.direction_outgoing');
    case 'incoming': return $t('relationships.direction_incoming');
    case 'sibling': return $t('relationships.direction_sibling');
    default: return '';
  }
};

const getTypeLabel = (type: RelatedInstitution['type']) => {
  switch (type) {
    case 'within-type': return $t('Tipas ↔');
    case 'cross-tenant-sibling': return $t('Centrinis');
    case 'type-based': return $t('Tipas');
    default: return '';
  }
};

const getTypeTitle = (type: RelatedInstitution['type']) => {
  switch (type) {
    case 'within-type': return $t('Ryšys tarp to paties tipo institucijų');
    case 'cross-tenant-sibling': return $t('Ryšys su centriniu padaliniu');
    case 'type-based': return $t('Ryšys per institucijos tipą');
    default: return '';
  }
};

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
  return result.filter((inst) => {
    if (seen.has(inst.id)) return false;
    seen.add(inst.id);
    return true;
  });
});

const handleClick = (institution: App.Entities.Institution) => {
  router.visit(route('institutions.show', institution.id));
};
</script>
