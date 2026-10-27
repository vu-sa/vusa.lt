<template>
  <ActionWindowScreen
    :title="$t('action_window.activity_request.institutions.title')"
    :subtitle="$t('action_window.activity_request.institutions.subtitle')"
  >
    <div class="space-y-3 pb-3">
      <Input :model-value="controller.query.value" :placeholder="$t('action_window.institution.search')" @update:model-value="controller.search(String($event), true)" />
      <ActivityRequestFilters :institutions="candidates ?? []" />
      <SpotlightPopover
        :title="$t('activity_requests.bulk_title')"
        :description="$t('activity_requests.bulk_hint')"
        :is-dismissed="spotlight.isDismissed.value"
        @dismiss="spotlight.dismiss()"
      >
        <div class="flex flex-wrap gap-2">
          <Button variant="outline" voice="sentence" class="min-h-11" data-slot="activity-select-all"
            :disabled="bulkSelecting || isFetching || controller.status.value !== 'idle' || selected.length >= 100" @click="selectAll">
            {{ $t(bulkSelecting ? 'activity_requests.selecting' : 'activity_requests.select_matching') }}
          </Button>
          <Button variant="ghost" voice="sentence" class="min-h-11" data-slot="activity-deselect-all" :disabled="selected.length === 0 && !bulkSelecting" @click="deselectAll">
            {{ $t('activity_requests.deselect_all') }}
          </Button>
        </div>
      </SpotlightPopover>
      <p v-if="draft.activityRequest.selectionLimited || selected.length >= 100" class="text-xs text-muted-foreground" role="status">
        {{ $t('activity_requests.limit_reached') }}
      </p>
      <p v-if="bulkError" class="text-sm text-destructive" role="alert">
        {{ $t('action_window.common.error') }}
      </p>
      <p class="text-xs text-muted-foreground" role="status">
        {{ $t('activity_requests.selection_limit') }} ({{ selected.length }}/100)
      </p>
    </div>

    <div v-if="isFetching && !candidates" class="flex flex-col gap-2">
      <Skeleton v-for="n in 3" :key="n" class="h-16 w-full" />
    </div>

    <EmptyState
      v-else-if="visible.length === 0"
      :title="error ? $t('action_window.common.error') : $t('action_window.institution.empty')"
    >
      <template #icon>
        <Landmark class="size-10 text-muted-foreground" />
      </template>
    </EmptyState>

    <ActionChoiceList v-else>
      <ActionChoiceButton
        v-for="institution in visible"
        :key="institution.id"
        :title="institution.name"
        :icon="institutionStatusStyle(institution.activity_status.status).icon"
        :tone="institutionStatusStyle(institution.activity_status.status).tone"
        :selected="isSelected(institution.id)"
        :disabled="bulkSelecting || (!isSelected(institution.id) && (selected.length >= 100 || !!rowState(institution.id)?.blocked))"
        @click="toggle(institution)"
      >
        <template #description>
          {{ contextLine(institution) }}
        </template>
      </ActionChoiceButton>
    </ActionChoiceList>

    <Button v-if="controller.hasMoreResults.value" variant="ghost" voice="sentence" class="mt-2 w-full" @click="controller.loadMore">
      {{ $t('Rodyti daugiau') }}
    </Button>
    <template #footer>
      <ActionWindowPrimaryButton :disabled="bulkSelecting || selected.length === 0 || selected.length > 100" @click="advance('activity.review')">
        {{ $t('action_window.activity_request.institutions.continue', { count: String(selected.length) }) }}
      </ActionWindowPrimaryButton>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Landmark } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import ActivityRequestFilters from '../ActivityRequestFilters.vue';
import { useActivityRequestScope } from '../useActivityRequestScope';
import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowPrimaryButton from '../ActionWindowPrimaryButton.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import { institutionStatusStyle } from '../institutionStatusStyle';
import { skipLabel, useActivityRequestAskability } from '../useActivityRequestAskability';
import { useWindowDates } from '../useWindowDates';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { useActionWindow, type ActionWindowInstitutionRef } from '@/Composables/useActionWindow';
import { useApi } from '@/Composables/useApi';
import { describeInstitutionActivity } from '@/Components/Institutions/institutionActivity';
import { EmptyState } from '@/Components/Patterns';
import { useAdminCollectionSearch } from '@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Skeleton } from '@/Components/ui/skeleton';
import type { InstitutionActivityStatus } from '@/Types/InstitutionActivity';

interface Candidate extends ActionWindowInstitutionRef {
  id: string;
  name: string;
  tenant_id: number;
  tenant_shortname: string | null;
  activity_status: InstitutionActivityStatus;
}

const { draft, current, advance, updateActivityRequest } = useActionWindow();
const dates = useWindowDates();
const { data: candidates, error, isFetching } = useApi<Candidate[]>(route('api.v1.admin.activityRequests.candidates'), { showErrorToast: false });
const selected = computed(() => draft.activityRequest.institutions);
const { scopeKey, matchesScope } = useActivityRequestScope();
const spotlight = useFeatureSpotlight('activity-request-selection-v1');
const scopedCandidates = computed(() => (candidates.value ?? []).filter(matchesScope));
const baseFilterBy = computed(() => {
  const { tenantIds } = draft.activityRequest.filters;
  return `id:[${scopedCandidates.value.map(item => item.id).join(',') || '__none__'}]${tenantIds.length ? ` && tenant_ids:[${tenantIds.join(',')}]` : ''}`;
});
watch([candidates, scopeKey], () => {
  if (!candidates.value) return;
  const allowed = new Set(scopedCandidates.value.map(item => item.id));
  const institutions = selected.value.filter(item => allowed.has(item.id));
  if (institutions.length !== selected.value.length || institutions.length > 100) {
    updateActivityRequest({ institutions: institutions.slice(0, 100), selectionLimited: institutions.length > 100 });
  }
}, { immediate: true });
const controller = useAdminCollectionSearch({ collection: 'institutions', syncToUrl: false, loadFacetsOnMount: false, searchOnMount: true, perPage: 20, baseFilterBy });
const visible = computed(() => {
  const byId = new Map(scopedCandidates.value.map(item => [item.id, item]));
  const hitIds = new Set(controller.results.value.map(item => String(item.id)));
  const pinned = draft.activityRequest.pinned
    .filter(item => !controller.query.value.trim() || hitIds.has(item.id))
    .map(item => byId.get(item.id))
    .filter((item): item is Candidate => !!item);
  const pinnedIds = new Set(pinned.map(item => item.id));
  const hits = controller.results.value.map(item => byId.get(String(item.id))).filter((item): item is Candidate => !!item && !pinnedIds.has(item.id));
  return [...pinned, ...hits];
});

const askability = useActivityRequestAskability(computed(() => draft.activityRequest.campaignType));
watch([() => visible.value.map(item => item.id), () => draft.activityRequest.campaignType], ([ids]) => {
  void askability.ensure(ids);
}, { immediate: true });
// Institutions handed in by the caller (e.g. Reikia dėmesio) are unpicked once we know nobody there can be asked.
watch(() => selected.value.filter(item => askability.entries.get(item.id)?.skip_reason).map(item => item.id), (blocked) => {
  if (blocked.length > 0) {
    updateActivityRequest({ institutions: selected.value.filter(item => !blocked.includes(item.id)) });
  }
});

const rowState = (id: string): { blocked: boolean; note: string } | null => {
  const entry = askability.entries.get(id);
  if (!entry) return null;
  if (entry.skip_reason) return { blocked: true, note: skipLabel(entry.skip_reason) };
  const left = (entry.excluded_recipients ?? []).filter(recipient => recipient.skip_reason).length;
  if (left === 0) return null;
  const asked = entry.recipients.length;
  return { blocked: false, note: $t('activity_requests.partial_recipients', { asked: String(asked), total: String(asked + left) }) };
};

const isSelected = (id: string) => selected.value.some(institution => institution.id === id);

const toggle = (institution: Candidate) => {
  if (!isSelected(institution.id) && (selected.value.length >= 100 || rowState(institution.id)?.blocked)) return;
  updateActivityRequest({
    institutions: isSelected(institution.id)
      ? selected.value.filter(item => item.id !== institution.id)
      : [...selected.value, { id: institution.id, name: institution.name }],
  });
};

const bulkSelecting = ref(false);
const bulkError = ref(false);
let bulkGeneration = 0;
const cancelBulk = () => {
  bulkGeneration++;
  bulkSelecting.value = false;
  bulkError.value = false;
};
watch([scopeKey, controller.query, () => draft.activityRequest.campaignType, () => current.value.id], cancelBulk, { flush: 'sync' });
onBeforeUnmount(cancelBulk);

const deselectAll = () => {
  cancelBulk();
  spotlight.dismiss();
  updateActivityRequest({ institutions: [], selectionLimited: false });
};

function selectEligible(matches: Candidate[]): number {
  const institutions = new Map(selected.value.map(item => [item.id, item]));
  for (const item of matches) {
    const entry = askability.entries.get(item.id)!;
    if (institutions.size >= 100) break;
    if (!entry.skip_reason && entry.recipients.length > 0) institutions.set(item.id, { id: item.id, name: item.name });
  }
  updateActivityRequest({ institutions: [...institutions.values()], selectionLimited: institutions.size >= 100 });
  return institutions.size;
}

async function selectAll() {
  if (bulkSelecting.value || controller.status.value !== 'idle') return;
  spotlight.dismiss();
  const generation = ++bulkGeneration;
  bulkSelecting.value = true;
  bulkError.value = false;
  try {
    while (generation === bulkGeneration) {
      const matches = visible.value;
      await askability.ensure(matches.map(item => item.id));
      if (generation !== bulkGeneration) return;
      if (matches.some(item => !askability.entries.has(item.id))) {
        bulkError.value = true;
        return;
      }
      const count = selectEligible(matches);
      if (count >= 100 || !controller.hasMoreResults.value) return;
      const previousCount = controller.results.value.length;
      await controller.loadMore();
      if (generation !== bulkGeneration) return;
      if (controller.error.value || controller.results.value.length <= previousCount) {
        bulkError.value = true;
        return;
      }
    }
  }
  catch {
    if (generation === bulkGeneration) bulkError.value = true;
  }
  finally {
    if (generation === bulkGeneration) bulkSelecting.value = false;
  }
}

const contextLine = (institution: Candidate): string => {
  const state = rowState(institution.id);
  return [
    institution.tenant_shortname,
    state?.blocked ? null : describeInstitutionActivity(institution.activity_status, dates),
    state?.note,
  ].filter(Boolean).join(' · ');
};
</script>
