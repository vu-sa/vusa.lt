<template>
  <div data-slot="secretaries-section" class="space-y-4">
    <p class="text-xs text-muted-foreground">
      {{ $t('secretaries.institution.effect_warning') }}
    </p>

    <p v-if="editable.length === 0" class="text-sm text-muted-foreground">
      {{ $t('secretaries.institution.no_cadences_hint') }}
    </p>

    <ul v-if="editable.length" class="space-y-3">
      <li
        v-for="roster in editable"
        :key="roster.cadence_id"
        data-slot="secretary-roster"
        :data-cadence-id="roster.cadence_id"
        class="flex flex-col gap-2 sm:flex-row sm:items-center"
      >
        <div class="flex shrink-0 items-baseline gap-2 sm:w-36">
          <span class="text-sm font-medium">{{ roster.label }}</span>
          <span class="text-xs text-muted-foreground">
            {{ roster.is_current ? $t('secretaries.institution.current_term') : $t('secretaries.institution.next_term') }}
          </span>
        </div>

        <div class="flex grow flex-wrap items-center gap-1.5">
          <span
            v-for="secretary in roster.secretaries"
            :key="secretary.id"
            class="inline-flex items-center gap-1.5 border border-border bg-background py-0.5 pl-0.5 pr-1 text-xs"
          >
            <UserAvatar :user="(secretary as unknown as App.Entities.User)" size="xxs" />
            <span class="max-w-40 truncate">{{ secretary.name }}</span>
            <button
              type="button"
              data-slot="remove-secretary"
              :data-user-id="secretary.id"
              class="inline-flex size-5 items-center justify-center text-muted-foreground transition-colors hover:text-destructive pointer-coarse:size-8"
              :disabled="processingCadenceId !== null"
              :aria-label="$t('secretaries.actions.remove', { name: secretary.name })"
              @click="remove(roster, secretary)"
            >
              <X class="size-3" />
            </button>
          </span>

          <MultiCollectionSelectDialog
            :open="pickerCadenceId === roster.cadence_id"
            multiple
            allow-empty
            :collections="['users']"
            :title="$t('secretaries.picker.title', { term: roster.label })"
            :confirm-label="$t('secretaries.picker.confirm')"
            :search-placeholder="$t('secretaries.picker.search')"
            :initial-hits="hitsFor(roster)"
            @update:open="open => { pickerCadenceId = open ? roster.cadence_id : null }"
            @confirm="hits => onConfirm(roster, hits)"
          >
            <template #trigger>
              <Button type="button" size="xs" variant="ghost" voice="sentence" :disabled="processingCadenceId !== null">
                <UserPlus class="size-3.5" />
                {{ roster.secretaries.length ? $t('secretaries.actions.manage') : $t('secretaries.actions.add') }}
              </Button>
            </template>
          </MultiCollectionSelectDialog>
        </div>
      </li>
    </ul>

    <!-- Past terms are history: who did it then, not something to staff now. -->
    <div v-if="previous.length" data-slot="previous-secretaries" class="space-y-1.5 pt-2">
      <h4 class="text-xs font-medium text-muted-foreground">
        {{ $t('secretaries.institution.previous') }}
      </h4>
      <dl class="space-y-1 text-sm">
        <div v-for="roster in previous" :key="roster.cadence_id" class="flex gap-3">
          <dt class="w-20 shrink-0 text-muted-foreground tabular-nums">
            {{ roster.label }}
          </dt>
          <dd class="min-w-0">
            {{ roster.secretaries.map(secretary => secretary.name).join(', ') }}
          </dd>
        </div>
      </dl>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { UserPlus, X } from 'lucide-vue-next';

import { useSecretaryRoster } from './useSecretaryRoster';
import type { SecretaryRoster, SecretaryUser } from './secretaryTypes';

import { Button } from '@/Components/ui/button';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import MultiCollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/MultiCollectionSelectDialog.vue';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

const props = defineProps<{
  institutionId: string;
  /** One roster per term that applies to this institution, newest first. */
  rosters: SecretaryRoster[];
}>();

const { processingCadenceId, save } = useSecretaryRoster(props.institutionId);
const pickerCadenceId = ref<string | null>(null);

/** The current term and the one after it — staffing further ahead is guesswork. */
const editable = computed(() => {
  const current = props.rosters.find(roster => roster.is_current);
  const next = props.rosters.filter(roster => !roster.is_current && !roster.is_past).at(-1);

  return [current, next].filter((roster): roster is SecretaryRoster => Boolean(roster));
});

const previous = computed(() => props.rosters.filter(roster => roster.is_past && roster.secretaries.length > 0));

function hitsFor(roster: SecretaryRoster): NormalizedSearchHit[] {
  return roster.secretaries.map(secretary => normalizeHit('users', {
    id: secretary.id,
    name: secretary.name,
    email: secretary.email,
  }));
}

function remove(roster: SecretaryRoster, user: SecretaryUser): void {
  save(roster.cadence_id, roster.secretaries.filter(secretary => secretary.id !== user.id).map(secretary => secretary.id));
}

function onConfirm(roster: SecretaryRoster, hits: NormalizedSearchHit[]): void {
  pickerCadenceId.value = null;
  save(roster.cadence_id, hits.map(hit => hit.recordId));
}
</script>
