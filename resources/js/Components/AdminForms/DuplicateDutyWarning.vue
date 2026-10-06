<template>
  <Alert v-if="matches.same_institution.length" class="border-status-attention-border bg-status-attention-surface text-status-attention">
    <TriangleAlert class="size-4 shrink-0" aria-hidden="true" />
    <AlertTitle>{{ $t('forms.duty_duplicate.warning_title') }}</AlertTitle>

    <AlertDescription class="text-foreground">
      <ul class="space-y-2">
        <li v-for="match in matches.same_institution" :key="match.id" class="flex flex-wrap items-center gap-x-2 gap-y-1">
          <span class="font-medium"><InflectedDutyName :name="match.name" /></span>

          <span v-if="match.current_holder_names.length" class="text-muted-foreground">
            {{ match.current_holder_names.join(', ') }}
          </span>
          <span v-else class="text-muted-foreground">
            {{ $t('forms.duty_duplicate.no_holder') }}
          </span>

          <Badge v-if="match.reason === 'same_institution_exact'" variant="destructive" class="text-xs">
            {{ $t('forms.duty_duplicate.reason_exact') }}
          </Badge>

          <span class="ml-auto flex flex-wrap items-center gap-1">
            <Button
              v-if="match.can_manage && match.reason === 'same_institution_variant' && currentDutyId"
              size="sm" voice="sentence" variant="secondary" as="a"
              :href="route('duties.index', { merge: 1, source: currentDutyId, target: match.id })"
              target="_blank" rel="noopener noreferrer"
            >
              {{ $t('forms.duty_duplicate.merge_instead') }}
            </Button>
            <Button
              v-if="match.can_manage" size="sm" voice="sentence" variant="outline" as="a"
              :href="route('duties.edit', match.id)" target="_blank" rel="noopener noreferrer"
            >
              {{ $t('forms.duty_duplicate.open_duty') }}
            </Button>
            <span v-else class="text-muted-foreground">
              {{ $t('forms.duty_duplicate.contact_admins') }}
            </span>
          </span>
        </li>
      </ul>

      <p v-if="hasVariantMatch" class="mt-2 border-t border-status-attention-border pt-2 text-status-attention">
        {{ $t('forms.duty_duplicate.variant_hint') }}
      </p>
    </AlertDescription>
  </Alert>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { TriangleAlert } from 'lucide-vue-next';

import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

/** Advisory only: inflected duty names resemble duplicates, and names can legitimately repeat. */
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

const hasVariantMatch = computed(() =>
  props.matches.same_institution.some(match => match.reason === 'same_institution_variant'));
</script>
