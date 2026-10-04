<template>
  <ActionWindowScreen
    :title="$t('action_window.activity_request.institutions.title')"
    :subtitle="$t('action_window.activity_request.institutions.subtitle')"
  >
    <div class="space-y-3 pb-3">
      <Input :model-value="controller.query.value" :placeholder="$t('action_window.institution.search')" @update:model-value="controller.search(String($event))" />
      <select v-model="tenant" class="min-h-11 w-full border border-border bg-background px-3" :aria-label="$t('activity_requests.all_tenants')">
        <option value="">
          {{ $t('activity_requests.all_tenants') }}
        </option>
        <option v-for="item in tenants" :key="item.id" :value="String(item.id)">
          {{ item.name }}
        </option>
      </select>
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
        :disabled="selected.length >= 100 && !isSelected(institution.id)"
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
      <ActionWindowPrimaryButton :disabled="selected.length === 0 || selected.length > 100" @click="advance('activity.review')">
        {{ $t('action_window.activity_request.institutions.continue', { count: String(selected.length) }) }}
      </ActionWindowPrimaryButton>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Landmark } from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowPrimaryButton from '../ActionWindowPrimaryButton.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import { institutionStatusStyle } from '../institutionStatusStyle';
import { useWindowDates } from '../useWindowDates';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useApi } from '@/Composables/useApi';
import { describeInstitutionActivity } from '@/Components/Institutions/institutionActivity';
import { EmptyState } from '@/Components/Patterns';
import { useAdminCollectionSearch } from '@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Skeleton } from '@/Components/ui/skeleton';
import type { InstitutionActivityStatus } from '@/Types/InstitutionActivity';

interface Candidate {
  id: string;
  name: string;
  tenant_id: number;
  tenant_shortname: string | null;
  activity_status: InstitutionActivityStatus;
}

const { draft, advance, updateActivityRequest } = useActionWindow();
const dates = useWindowDates();
const { data: candidates, error, isFetching } = useApi<Candidate[]>(route('api.v1.admin.activityRequests.candidates'), { showErrorToast: false });
const selected = computed(() => draft.activityRequest.institutions);
const tenant = ref('');
const tenants = computed(() => Array.from(new Map((candidates.value ?? []).map(item => [item.tenant_id, { id: item.tenant_id, name: item.tenant_shortname }])).values()));
const baseFilterBy = computed(() => `id:[${(candidates.value ?? []).map(item => item.id).join(',') || '__none__'}]${tenant.value ? ` && tenant_ids:[${tenant.value}]` : ''}`);
const controller = useAdminCollectionSearch({ collection: 'institutions', syncToUrl: false, loadFacetsOnMount: false, searchOnMount: true, perPage: 20, baseFilterBy });
const visible = computed(() => {
  const byId = new Map((candidates.value ?? []).map(item => [item.id, item]));
  const pinned = draft.activityRequest.pinned.map(item => byId.get(item.id)).filter((item): item is Candidate => !!item);
  const pinnedIds = new Set(pinned.map(item => item.id));
  const hits = controller.results.value.map(item => byId.get(String(item.id))).filter((item): item is Candidate => !!item && !pinnedIds.has(item.id));
  return [...pinned, ...hits];
});

const isSelected = (id: string) => selected.value.some(institution => institution.id === id);

const toggle = (institution: Candidate) => {
  if (!isSelected(institution.id) && selected.value.length >= 100) return;
  updateActivityRequest({
    institutions: isSelected(institution.id)
      ? selected.value.filter(item => item.id !== institution.id)
      : [...selected.value, { id: institution.id, name: institution.name }],
  });
};

const contextLine = (institution: Candidate): string => [
  institution.tenant_shortname,
  describeInstitutionActivity(institution.activity_status, dates),
].filter(Boolean).join(' · ');
</script>
