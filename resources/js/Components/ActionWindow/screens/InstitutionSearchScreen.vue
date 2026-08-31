<template>
  <ActionWindowScreen
    :title="$t('action_window.institution.search_title')"
    :subtitle="$t('action_window.institution.search_subtitle')"
  >
    <div class="pb-3">
      <Input
        :model-value="controller.query.value"
        :placeholder="$t('action_window.institution.search')"
        @update:model-value="(value) => controller.search(String(value))"
      />
    </div>

    <div v-if="controller.isSearching.value && hits.length === 0" class="flex flex-col gap-2">
      <Skeleton v-for="n in 3" :key="n" class="h-16 w-full rounded-xl" />
    </div>

    <EmptyState
      v-else-if="hits.length === 0"
      :title="emptyTitle"
      :description="$t('action_window.institution.search_empty')"
    >
      <template #icon>
        <Landmark class="size-10 text-muted-foreground" />
      </template>
    </EmptyState>

    <template v-else>
      <ActionChoiceList>
        <ActionChoiceButton
          v-for="hit in hits"
          :key="hit.id"
          :title="hit.title"
          :description="hit.subtitle"
          :icon="Landmark"
          gradient="from-indigo-500/15 to-violet-500/15 dark:from-indigo-400/12 dark:to-violet-400/12"
          @click="pick(hit)"
        />
      </ActionChoiceList>

      <Button
        v-if="controller.hasMoreResults.value"
        variant="ghost"
        class="mt-2 w-full"
        :disabled="controller.isLoadingMore.value"
        @click="controller.loadMore"
      >
        {{ $t('Rodyti daugiau') }}
      </Button>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Landmark } from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useActionWindowData } from '@/Composables/useActionWindowData';
import { useAdminCollectionSearch } from '@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Skeleton } from '@/Components/ui/skeleton';

const { advance, setInstitution } = useActionWindow();
const { institutionSearch } = useActionWindowData();

/**
 * The tenants the caller may file a meeting under. All-scope callers send no filter —
 * the scoped Typesense key still bounds what they may read, and StoreMeetingRequest's
 * tenant-scope rule is what actually decides.
 */
const baseFilterBy = institutionSearch.value.tenant_ids.length > 0
  ? `tenant_ids:[${institutionSearch.value.tenant_ids.join(',')}]`
  : undefined;

// A plain list, not the admin search page's split view: this window is one question
// per screen, and facets/detail panes have no room in it.
const controller = useAdminCollectionSearch({
  collection: 'institutions',
  syncToUrl: false,
  loadFacetsOnMount: false,
  searchOnMount: true,
  perPage: 20,
  baseFilterBy,
});

const hits = computed<NormalizedSearchHit[]>(() =>
  controller.results.value.map(doc => normalizeHit('institutions', doc)),
);

const emptyTitle = computed(() =>
  controller.error.value ? $t('action_window.common.error') : $t('action_window.institution.empty'),
);

const pick = (hit: NormalizedSearchHit) => {
  // Governance scope is not in the search document, so `isInternal` stays unknown and
  // the review hides the "announce publicly" option rather than offering a refusal.
  setInstitution({ id: hit.recordId, name: hit.title });
  advance('meeting.type');
};
</script>
