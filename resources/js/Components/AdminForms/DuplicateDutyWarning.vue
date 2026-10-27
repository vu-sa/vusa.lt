<template>
  <div v-if="matches.same_institution.length || sameTenant.length" class="space-y-2" data-slot="duplicate-duty-warning">
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
            <Badge v-else-if="match.reason === 'former_name'" variant="outline" class="text-xs">
              {{ $t('forms.duty_duplicate.reason_former_name') }}
            </Badge>
            <Badge v-if="match.is_active === false" variant="outline" class="text-xs" data-testid="duplicate-inactive">
              {{ $t('Nebeveikianti') }}
            </Badge>

            <span class="ml-auto flex flex-wrap items-center gap-1">
              <Button
                v-if="match.can_merge && match.reason === 'same_institution_variant' && currentDutyId"
                size="sm" voice="sentence" variant="secondary" as="a"
                :href="route('duties.index', { merge: 1, source: currentDutyId, target: match.id })"
                target="_blank" rel="noopener noreferrer"
              >
                {{ $t('forms.duty_duplicate.merge_instead') }}
              </Button>
              <Button
                v-if="match.can_view || match.can_manage" size="sm" voice="sentence" variant="outline" as="a"
                :href="route('duties.show', match.id)" target="_blank" rel="noopener noreferrer"
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
        <p v-if="hasInactiveMatch" class="mt-2 border-t border-status-attention-border pt-2 text-status-attention">
          {{ $t('forms.duty_duplicate.inactive_hint') }}
        </p>
      </AlertDescription>
    </Alert>

    <div v-if="sameTenant.length" class="border border-border p-3 text-sm" data-testid="duplicate-same-tenant">
      <p class="text-xs text-muted-foreground">
        {{ $t('forms.duty_duplicate.same_tenant_title') }}
      </p>
      <ul class="mt-1.5 space-y-1">
        <li v-for="match in sameTenant" :key="match.id" class="flex flex-wrap items-center gap-x-2 gap-y-1">
          <a
            v-if="match.can_view || match.can_manage"
            :href="route('duties.show', match.id)"
            target="_blank"
            rel="noopener noreferrer"
            class="font-medium underline-offset-4 hover:text-brand hover:underline"
          >
            <InflectedDutyName :name="match.name" />
          </a>
          <span v-else class="font-medium"><InflectedDutyName :name="match.name" /></span>
          <span v-if="match.institution_name" class="text-muted-foreground">· {{ match.institution_name }}</span>
          <Badge v-if="match.reason === 'former_name'" variant="outline" class="text-xs">
            {{ $t('forms.duty_duplicate.reason_former_name') }}
          </Badge>
          <Badge v-if="match.is_active === false" variant="outline" class="text-xs">
            {{ $t('Nebeveikianti') }}
          </Badge>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { TriangleAlert } from '@lucide/vue';

import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

/** Advisory only: inflected duty names resemble duplicates, and names can legitimately repeat. */
export interface DutyMatch {
  id: string;
  name: string;
  reason: 'same_institution_exact' | 'same_institution_variant' | 'former_name' | 'same_tenant' | 'other_institution';
  institution_name: string | null;
  tenant_shortname: string | null;
  current_holder_names: string[];
  is_active?: boolean;
  can_view?: boolean;
  can_manage: boolean;
  /** Absent from responses cached before duty merging became super-admin only. */
  can_merge?: boolean;
}

export interface DutySimilarityMatches {
  same_institution: DutyMatch[];
  /** Absent from responses cached before the tier existed. */
  same_tenant?: DutyMatch[];
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
const hasInactiveMatch = computed(() => props.matches.same_institution.some(match => match.is_active === false));
const sameTenant = computed(() => props.matches.same_tenant ?? []);
</script>
