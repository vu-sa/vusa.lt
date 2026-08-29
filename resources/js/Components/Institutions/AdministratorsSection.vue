<template>
  <div data-slot="administrators-section" class="space-y-4">
    <Alert class="py-2">
      <AlertDescription class="text-xs">
        {{ $t('administrators.institution.effect_warning') }}
      </AlertDescription>
    </Alert>

    <EmptyState
      v-if="rosters.length === 0"
      :title="$t('administrators.institution.no_cadences')"
      :description="$t('administrators.institution.no_cadences_hint')"
    />

    <div v-else class="divide-y rounded-lg border bg-card">
      <div
        v-for="roster in rosters"
        :key="roster.cadence_id"
        class="flex flex-col gap-2 p-3 sm:flex-row sm:items-start sm:justify-between"
      >
        <div class="flex shrink-0 items-center gap-2 sm:w-40">
          <span class="text-sm font-medium">{{ roster.label }}</span>
          <Badge v-if="roster.is_current" variant="secondary" class="text-[10px]">
            {{ $t('administrators.institution.current_term') }}
          </Badge>
          <Badge v-else-if="roster.is_global" variant="outline" class="text-[10px]">
            {{ $t('administrators.institution.inherited_term') }}
          </Badge>
        </div>

        <div class="flex grow flex-wrap items-center gap-1.5">
          <span
            v-for="administrator in roster.administrators"
            :key="administrator.id"
            class="inline-flex items-center gap-1.5 rounded-full border bg-background py-0.5 pl-0.5 pr-1.5 text-xs"
          >
            <UserAvatar :user="(administrator as unknown as App.Entities.User)" :size="20" />
            <span class="max-w-40 truncate">{{ administrator.name }}</span>
            <button
              type="button"
              data-slot="remove-administrator"
              :data-user-id="administrator.id"
              class="rounded-full text-muted-foreground transition-colors hover:text-destructive"
              :disabled="processingCadenceId !== null"
              :aria-label="$t('administrators.actions.remove', { name: administrator.name })"
              @click="remove(roster, administrator)"
            >
              <X class="size-3" />
            </button>
          </span>

          <span v-if="roster.administrators.length === 0" class="text-xs text-muted-foreground">
            {{ $t('administrators.institution.none_yet') }}
          </span>

          <!-- One-tap chips for people already in the body: the usual pick, offered
               without making the editor search for a name they already know. -->
          <Button
            v-for="candidate in suggestionsFor(roster)"
            :key="`suggest-${roster.cadence_id}-${candidate.id}`"
            type="button"
            size="xs"
            variant="ghost"
            class="h-6 rounded-full border border-dashed px-2 text-xs text-muted-foreground"
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
          :title="$t('administrators.picker.title', { term: roster.label })"
          :confirm-label="$t('administrators.picker.confirm')"
          :search-placeholder="$t('administrators.picker.search')"
          :initial-hits="hitsFor(roster)"
          @update:open="open => { pickerCadenceId = open ? roster.cadence_id : null; if (open) { emit('engaged'); } }"
          @confirm="hits => onConfirm(roster, hits)"
        >
          <template #trigger>
            <Button type="button" size="xs" variant="outline" class="shrink-0" :disabled="processingCadenceId !== null">
              <UserPlus class="mr-1 size-3.5" />
              {{ $t('administrators.actions.manage') }}
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

import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { EmptyState } from '@/Components/Patterns';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import MultiCollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/MultiCollectionSelectDialog.vue';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

import { useAdministratorRoster } from './useAdministratorRoster';
import type { AdministratorRoster, AdministratorUser } from './administratorTypes';

const props = defineProps<{
  institutionId: string;
  /** One roster per term that applies to this institution, newest first. */
  rosters: AdministratorRoster[];
  /** Current members of the body, offered as one-tap suggestions. */
  suggested: AdministratorUser[];
}>();

const emit = defineEmits<{
  /** The editor actually used the roster — enough to retire the spotlight. */
  engaged: [];
}>();

const { processingCadenceId, save } = useAdministratorRoster(props.institutionId);
const pickerCadenceId = ref<string | null>(null);

/** Members not already nominated for this term. Capped: this is a shortcut, not a list. */
function suggestionsFor(roster: AdministratorRoster): AdministratorUser[] {
  const taken = new Set(roster.administrators.map(administrator => administrator.id));

  return props.suggested.filter(candidate => !taken.has(candidate.id)).slice(0, 4);
}

function hitsFor(roster: AdministratorRoster): NormalizedSearchHit[] {
  return roster.administrators.map(administrator => normalizeHit('users', {
    id: administrator.id,
    name: administrator.name,
    email: administrator.email,
  }));
}

function add(roster: AdministratorRoster, user: AdministratorUser): void {
  emit('engaged');
  save(roster.cadence_id, [...roster.administrators.map(a => a.id), user.id]);
}

function remove(roster: AdministratorRoster, user: AdministratorUser): void {
  emit('engaged');
  save(roster.cadence_id, roster.administrators.filter(a => a.id !== user.id).map(a => a.id));
}

function onConfirm(roster: AdministratorRoster, hits: NormalizedSearchHit[]): void {
  pickerCadenceId.value = null;
  emit('engaged');
  save(roster.cadence_id, hits.map(hit => hit.recordId));
}
</script>
