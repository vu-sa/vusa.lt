<template>
  <div v-if="hasAnyMatch" class="space-y-2">
    <div v-if="matches.same_institution.length" class="rounded-md border border-amber-300 bg-amber-50 p-3 text-xs dark:border-amber-800 dark:bg-amber-950">
      <p class="mb-2 flex items-center gap-1.5 font-medium text-amber-900 dark:text-amber-200">
        <TriangleAlert class="size-3.5 shrink-0" />
        {{ $t('forms.duty_duplicate.warning_title') }}
      </p>

      <ul class="space-y-1.5">
        <li v-for="match in matches.same_institution" :key="match.id" class="flex flex-wrap items-center gap-x-2 gap-y-1">
          <span class="font-medium text-amber-900 dark:text-amber-100">{{ match.name }}</span>

          <span v-if="match.current_holder_names.length" class="text-amber-700 dark:text-amber-300">
            {{ match.current_holder_names.join(', ') }}
          </span>
          <span v-else class="text-amber-700 dark:text-amber-300">
            {{ $t('forms.duty_duplicate.no_holder') }}
          </span>

          <Badge v-if="match.reason === 'same_institution_exact'" variant="destructive" class="text-[10px]">
            {{ $t('forms.duty_duplicate.reason_exact') }}
          </Badge>

          <span class="ml-auto flex items-center gap-1">
            <Button v-if="match.can_manage && match.reason === 'same_institution_variant' && currentDutyId" size="xs" variant="secondary" as="a"
              :href="route('duties.merge', { target_duty_id: match.id })" target="_blank" rel="noopener noreferrer">
              {{ $t('forms.duty_duplicate.merge_instead') }}
            </Button>
            <Button v-if="match.can_manage" size="xs" variant="outline" as="a"
              :href="route('duties.edit', match.id)" target="_blank" rel="noopener noreferrer">
              {{ $t('forms.duty_duplicate.open_duty') }}
            </Button>
            <span v-else class="text-amber-700 dark:text-amber-300">
              {{ $t('forms.duty_duplicate.contact_admins') }}
            </span>
          </span>
        </li>
      </ul>

      <p v-if="hasVariantMatch" class="mt-2 border-t border-amber-200 pt-2 text-amber-800 dark:border-amber-800 dark:text-amber-300">
        {{ $t('forms.duty_duplicate.variant_hint') }}
      </p>
    </div>

    <!-- <p v-if="matches.other_institution_count > 0" class="text-xs text-muted-foreground"> -->
    <!--   {{ $t('forms.duty_duplicate.other_institutions', { count: matches.other_institution_count }) }} -->
    <!-- </p> -->
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { TriangleAlert } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

/**
 * Warns that a duty being created/renamed looks like one that already exists.
 * The dominant real-world case: the admin doesn't know duty names are inflected
 * automatically per holder's pronouns, so they're about to create a feminine
 * twin of an existing duty (`reason: 'same_institution_variant'`).
 *
 * Advisory only — duty names legitimately repeat across institutions (e.g.
 * "Studentų atstovas" exists in 50+ of them), so this never blocks a submit.
 */
export interface DutyMatch {
  id: string;
  name: string;
  reason: 'same_institution_exact' | 'same_institution_variant' | 'other_institution';
  institution_name: string | null;
  tenant_shortname: string | null;
  current_holder_names: string[];
  places_to_occupy: number | null;
  can_manage: boolean;
}

export interface DutySimilarityMatches {
  same_institution: DutyMatch[];
  other_institution: DutyMatch[];
  other_institution_count: number;
}

const props = defineProps<{
  matches: DutySimilarityMatches;
  /** The duty currently being edited — enables "merge instead" (create has nothing to merge yet). */
  currentDutyId?: string | null;
}>();

const hasAnyMatch = computed(() =>
  props.matches.same_institution.length > 0 || props.matches.other_institution_count > 0);

const hasVariantMatch = computed(() =>
  props.matches.same_institution.some(match => match.reason === 'same_institution_variant'));
</script>
