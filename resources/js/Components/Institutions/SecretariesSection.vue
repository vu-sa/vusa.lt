<template>
  <div data-slot="secretaries-section" class="space-y-4">
    <Alert class="py-2">
      <AlertDescription class="text-xs">
        {{ $t('secretaries.institution.effect_warning') }}
      </AlertDescription>
    </Alert>

    <EmptyState
      v-if="rosters.length === 0"
      :title="$t('secretaries.institution.no_cadences')"
      :description="$t('secretaries.institution.no_cadences_hint')"
    />

    <div v-else class="divide-y divide-border border-y border-border">
      <div
        v-for="roster in rosters"
        :key="roster.cadence_id"
        class="flex flex-col gap-2 p-3 sm:flex-row sm:items-start sm:justify-between"
      >
        <div class="flex shrink-0 items-center gap-2 sm:w-40">
          <span class="text-sm font-medium">{{ roster.label }}</span>
          <Badge v-if="roster.is_current" variant="secondary" class="text-[10px]">
            {{ $t('secretaries.institution.current_term') }}
          </Badge>
          <Badge v-else-if="roster.is_global" variant="outline" class="text-[10px]">
            {{ $t('secretaries.institution.inherited_term') }}
          </Badge>
        </div>

        <div class="flex grow flex-wrap items-center gap-1.5">
          <span
            v-for="secretary in roster.secretaries"
            :key="secretary.id"
            class="inline-flex items-center gap-1.5 border border-border bg-background py-0.5 pl-0.5 pr-1.5 text-xs"
          >
            <UserAvatar :user="(secretary as unknown as App.Entities.User)" :size="20" />
            <span class="max-w-40 truncate">{{ secretary.name }}</span>
            <button
              type="button"
              data-slot="remove-secretary"
              :data-user-id="secretary.id"
              class="text-muted-foreground transition-colors hover:text-destructive"
              :disabled="processingCadenceId !== null"
              :aria-label="$t('secretaries.actions.remove', { name: secretary.name })"
              @click="remove(roster, secretary)"
            >
              <X class="size-3" />
            </button>
          </span>

          <span v-if="roster.secretaries.length === 0" class="text-xs text-muted-foreground">
            {{ $t('secretaries.institution.none_yet') }}
          </span>

          <!-- One-tap chips for people already in the body: the usual pick, offered
               without making the editor search for a name they already know. -->
          <Button
            v-for="candidate in suggestionsFor(roster)"
            :key="`suggest-${roster.cadence_id}-${candidate.id}`"
            type="button"
            size="xs"
            variant="ghost"
            class="h-6 border border-dashed px-2 text-xs text-muted-foreground"
            :disabled="processingCadenceId !== null"
            @click="add(roster, candidate)"
          >
            <Plus class="mr-1 size-3" />
            {{ candidate.name }}
          </Button>
        </div>

        <MultiCollectionSelectDialog
          :open="pickerCadenceId === roster.cadence_id"
          multiple
          allow-empty
          :collections="['users']"
          :title="$t('secretaries.picker.title', { term: roster.label })"
          :confirm-label="$t('secretaries.picker.confirm')"
          :search-placeholder="$t('secretaries.picker.search')"
          :initial-hits="hitsFor(roster)"
          @update:open="open => { pickerCadenceId = open ? roster.cadence_id : null; if (open) { emit('engaged'); } }"
          @confirm="hits => onConfirm(roster, hits)"
        >
          <template #trigger>
            <Button type="button" size="xs" variant="outline" class="shrink-0" :disabled="processingCadenceId !== null">
              <UserPlus class="mr-1 size-3.5" />
              {{ $t('secretaries.actions.manage') }}
            </Button>
          </template>
        </MultiCollectionSelectDialog>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Plus, UserPlus, X } from 'lucide-vue-next';

import { useSecretaryRoster } from './useSecretaryRoster';
import type { SecretaryRoster, SecretaryUser } from './secretaryTypes';

import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { EmptyState } from '@/Components/Patterns';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import MultiCollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/MultiCollectionSelectDialog.vue';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

const props = defineProps<{
  institutionId: string;
  /** One roster per term that applies to this institution, newest first. */
  rosters: SecretaryRoster[];
  /** Current members of the body, offered as one-tap suggestions. */
  suggested: SecretaryUser[];
}>();

const emit = defineEmits<{
  /** The editor actually used the roster — enough to retire the spotlight. */
  engaged: [];
}>();

const { processingCadenceId, save } = useSecretaryRoster(props.institutionId);
const pickerCadenceId = ref<string | null>(null);

function getSecretaries(roster: SecretaryRoster): SecretaryUser[] {
  return roster.secretaries;
}

/** Members not already nominated for this term. Capped: this is a shortcut, not a list. */
function suggestionsFor(roster: SecretaryRoster): SecretaryUser[] {
  const taken = new Set(getSecretaries(roster).map(secretary => secretary.id));

  return props.suggested.filter(candidate => !taken.has(candidate.id)).slice(0, 4);
}

function hitsFor(roster: SecretaryRoster): NormalizedSearchHit[] {
  return getSecretaries(roster).map(secretary => normalizeHit('users', {
    id: secretary.id,
    name: secretary.name,
    email: secretary.email,
  }));
}

function add(roster: SecretaryRoster, user: SecretaryUser): void {
  emit('engaged');
  save(roster.cadence_id, [...getSecretaries(roster).map(a => a.id), user.id]);
}

function remove(roster: SecretaryRoster, user: SecretaryUser): void {
  emit('engaged');
  save(roster.cadence_id, getSecretaries(roster).filter(a => a.id !== user.id).map(a => a.id));
}

function onConfirm(roster: SecretaryRoster, hits: NormalizedSearchHit[]): void {
  pickerCadenceId.value = null;
  emit('engaged');
  save(roster.cadence_id, hits.map(hit => hit.recordId));
}
</script>
