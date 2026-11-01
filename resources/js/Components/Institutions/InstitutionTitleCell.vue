<template>
  <CollectionPrimaryCell :match="institution._searchMatch" :title-matches="institution._searchTitleMatches" :title :title-lines="2" :href>
    <template v-if="inactive" #default>
      <span class="inline-flex items-baseline gap-1.5">
        <component :is="operatingStatuses.inactive.icon" class="size-4 shrink-0 self-center" :title="$t(operatingStatuses.inactive.label)" aria-hidden="true" data-slot="institution-inactive-mark" />
        <span class="sr-only">{{ $t(operatingStatuses.inactive.label) }}:</span>
        <span>{{ title }}</span>
      </span>
    </template>
    <template v-if="$slots.sub" #sub>
      <slot name="sub" />
    </template>
  </CollectionPrimaryCell>
</template>

<script setup lang="ts">
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import { operatingStatuses } from '@/Constants/statuses';
import type { InstitutionSearchResult } from '@/Shared/Search/types';

/** An institution's name in a collection; one that stopped working says so with a mark before it. */
defineProps<{
  institution: InstitutionSearchResult;
  title: string;
  href?: string;
  inactive?: boolean;
}>();
</script>
