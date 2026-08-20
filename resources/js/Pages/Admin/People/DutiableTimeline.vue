<template>
  <PageContent :title="$t('dutiables.timeline.page.title')" :heading-icon="CalendarRange">
    <template #after-heading>
      <CollectionSelectDialog
        v-model:open="pickerOpen"
        collection="institutions"
        :title="$t('dutiables.timeline.page.pick_institution')"
        :confirm-label="$t('Pasirinkti')"
        @confirm="onInstitutionSelected"
      >
        <template #trigger>
          <Button type="button" size="sm" variant="outline">
            <Building2 class="size-3.5" />
            {{ institution ? $t('dutiables.timeline.page.change_institution') : $t('dutiables.timeline.page.pick_institution') }}
          </Button>
        </template>
      </CollectionSelectDialog>
    </template>

    <p class="mb-4 text-sm text-muted-foreground">
      {{ $t('dutiables.timeline.page.description') }}
    </p>

    <EmptyState
      v-if="!institution"
      :title="$t('dutiables.timeline.page.pick_institution')"
      :description="$t('dutiables.timeline.page.no_scope')"
    />

    <!--
      `max-h`, not `h`: a four-row institution should end after four rows rather than
      reserve a screen, while a forty-row one caps here and scrolls inside the chart.
      Keyed on the institution so switching scope remounts rather than leaving the
      previous chart's staged edits attached to the new one.
    -->
    <div v-else class="flex max-h-[calc(100vh-13rem)] flex-col">
      <DutiableTimelineEditor
        class="min-h-0 flex-auto"
        :key="institution.id"
        scope-type="institution"
        :scope-id="institution.id"
      />
    </div>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, CalendarRange } from 'lucide-vue-next';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { DutiableTimelineEditor } from '@/Features/Admin/DutiableTimeline';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { InstitutionIconFilled } from '@/Components/icons';

interface ScopeInstitution {
  id: string;
  name: string | null;
  alias?: string | null;
}

const props = defineProps<{
  initialInstitution: ScopeInstitution | null;
}>();

const institution = ref<ScopeInstitution | null>(props.initialInstitution);
const pickerOpen = ref(false);

function onInstitutionSelected(hits: NormalizedSearchHit[]): void {
  const hit = hits[0];
  if (!hit) return;

  // `recordId` is the institution's own id; `id` is the collection-prefixed row key.
  institution.value = { id: hit.recordId, name: hit.title };
  pickerOpen.value = false;

  // Keeps the scope in the URL so the view is shareable and survives a reload.
  router.visit(route('dutiables.timeline', { institution: hit.recordId }), {
    preserveState: true,
    preserveScroll: true,
    only: [],
  });
}

usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createBreadcrumbItem($t('dutiables.timeline.page.title'), undefined, InstitutionIconFilled),
]);
</script>
