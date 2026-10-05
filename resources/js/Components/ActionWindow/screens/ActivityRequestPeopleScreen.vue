<template>
  <ActionWindowScreen
    :title="$t('action_window.activity_request.people.title')"
    :subtitle="$t('action_window.activity_request.people.subtitle')"
  >
    <div class="space-y-3 pb-3">
      <Input v-model="query" :placeholder="$t('action_window.activity_request.people.search')" />
      <p class="text-xs text-muted-foreground" role="status">
        {{ $t('activity_requests.selection_limit') }} ({{ selectedInstitutionIds.size }}/100)
      </p>
    </div>

    <div v-if="isFetching && !people" class="flex flex-col gap-2">
      <Skeleton v-for="n in 3" :key="n" class="h-16 w-full" />
    </div>

    <EmptyState v-else-if="shown.length === 0" :title="error ? $t('action_window.common.error') : $t('action_window.activity_request.people.empty')">
      <template #icon>
        <Users class="size-10 text-muted-foreground" />
      </template>
    </EmptyState>

    <ActionChoiceList v-else>
      <ActionChoiceButton
        v-for="person in shown"
        :key="person.id"
        :title="person.name"
        :icon="UserRound"
        :selected="isSelected(person.id)"
        :disabled="!isSelected(person.id) && (rowState(person)?.blocked || exceedsLimit(person))"
        @click="toggle(person)"
      >
        <template #description>
          {{ contextLine(person) }}
        </template>
      </ActionChoiceButton>
    </ActionChoiceList>

    <Button v-if="filtered.length > shown.length" variant="ghost" voice="sentence" class="mt-2 w-full" @click="limit += PAGE">
      {{ $t('Rodyti daugiau') }}
    </Button>
    <template #footer>
      <ActionWindowPrimaryButton :disabled="selected.length === 0 || selectedInstitutionIds.size > 100" @click="advance('activity.review')">
        {{ $t('action_window.activity_request.people.continue', { count: String(selected.length) }) }}
      </ActionWindowPrimaryButton>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { UserRound, Users } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowPrimaryButton from '../ActionWindowPrimaryButton.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import { skipLabel, useActivityRequestAskability } from '../useActivityRequestAskability';

import { useActionWindow, type ActionWindowPersonRef } from '@/Composables/useActionWindow';
import { useApi } from '@/Composables/useApi';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Skeleton } from '@/Components/ui/skeleton';

const PAGE = 20;

const { draft, advance, updateActivityRequest } = useActionWindow();
// The list is already scoped to the caller's institutions, and coordinators may not search members in Typesense.
const { data: people, error, isFetching } = useApi<ActionWindowPersonRef[]>(route('api.v1.admin.activityRequests.people'), { showErrorToast: false });
const query = ref('');
const limit = ref(PAGE);
const selected = computed(() => draft.activityRequest.people);
const selectedInstitutionIds = computed(() => new Set(selected.value.flatMap(person => person.institutions.map(institution => institution.id))));

const normalize = (value: string) => value.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();
const filtered = computed(() => {
  const needle = normalize(query.value.trim());
  return (people.value ?? []).filter(person => !needle
    || normalize(person.name).includes(needle)
    || person.institutions.some(institution => normalize(institution.name).includes(needle)));
});
watch(query, () => { limit.value = PAGE; });

const pairsByInstitution = computed(() => {
  const pairs = new Map<string, Array<{ institution_id: string; user_id: string }>>();
  (people.value ?? []).forEach(person => person.institutions.forEach((institution) => {
    pairs.set(institution.id, [...(pairs.get(institution.id) ?? []), { institution_id: institution.id, user_id: person.id }]);
  }));
  return pairs;
});
const askability = useActivityRequestAskability(computed(() => draft.activityRequest.campaignType), id => pairsByInstitution.value.get(id) ?? []);

const shown = computed(() => filtered.value.slice(0, limit.value));
watch(() => shown.value.flatMap(person => person.institutions.map(institution => institution.id)), ids => askability.ensure([...new Set(ids)]), { immediate: true });

/** Why each of the person's institutions would be left out, once known. */
function rowState(person: ActionWindowPersonRef): { blocked: boolean; note: string | null } | null {
  const entries = person.institutions.map(institution => askability.entries.get(institution.id));
  if (entries.some(entry => !entry)) return null;
  const reasons = entries.map((entry) => {
    if (entry!.recipients.some(recipient => recipient.id === person.id)) return null;
    return entry!.excluded_recipients?.find(recipient => recipient.id === person.id)?.skip_reason ?? entry!.skip_reason ?? 'no_recipients';
  });
  const asked = reasons.filter(reason => reason === null).length;
  if (asked === 0) return { blocked: true, note: skipLabel(reasons[0]!) };
  if (asked === reasons.length) return null;
  return { blocked: false, note: $t('activity_requests.partial_institutions', { asked: String(asked), total: String(reasons.length) }) };
}

const institutionNames = (person: ActionWindowPersonRef) => {
  const names = person.institutions.map(institution => institution.name);
  return names.length > 3 ? `${names.slice(0, 3).join(', ')} ${$t('activity_requests.and_more', { count: String(names.length - 3) })}` : names.join(', ');
};
const contextLine = (person: ActionWindowPersonRef) => [institutionNames(person), rowState(person)?.note].filter(Boolean).join(' · ');

const isSelected = (id: string) => selected.value.some(person => person.id === id);
const exceedsLimit = (person: ActionWindowPersonRef) => new Set([...selectedInstitutionIds.value, ...person.institutions.map(institution => institution.id)]).size > 100;
const toggle = (person: ActionWindowPersonRef) => {
  if (!isSelected(person.id) && exceedsLimit(person)) return;
  updateActivityRequest({
    people: isSelected(person.id) ? selected.value.filter(item => item.id !== person.id) : [...selected.value, person],
  });
};
</script>
