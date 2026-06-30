<template>
  <div class="h-full">
    <!-- Empty state -->
    <div v-if="!hit" class="flex h-full flex-col items-center justify-center px-6 text-center">
      <div class="mb-4 flex size-12 items-center justify-center rounded-full bg-muted/50">
        <MousePointerClick class="size-6 text-muted-foreground/50" />
      </div>
      <p class="text-sm font-medium text-foreground">{{ $t('Pasirinkite rezultatą') }}</p>
      <p class="mt-1 text-xs text-muted-foreground">{{ $t('Peržiūra bus rodoma čia') }}</p>
    </div>

    <!-- Rich previews -->
    <MeetingDetailPreview
      v-else-if="hit.collection === 'meetings'"
      :key="hit.id"
      :meeting="hit.raw as MeetingSearchResult"
    />
    <AgendaItemDetailPreview
      v-else-if="hit.collection === 'agendaItems'"
      :key="hit.id"
      :item="hit.raw as AgendaItemSearchResult"
    />
    <InstitutionDetailPreview
      v-else-if="hit.collection === 'institutions'"
      :key="hit.id"
      :institution="hit.raw as InstitutionSearchResult"
    />
    <ResourceDetailPreview
      v-else-if="hit.collection === 'resources'"
      :key="hit.id"
      :resource="hit.raw as ResourceSearchResult"
    />
    <DutyDetailPreview
      v-else-if="hit.collection === 'duties'"
      :key="hit.id"
      :duty="hit.raw as DutySearchResult"
      :is-external="hit.isExternal"
    />
    <DocumentDetailPreview
      v-else-if="hit.collection === 'documents'"
      :key="hit.id"
      :document="hit.raw as DocumentSearchResult"
    />
    <NewsDetailPreview
      v-else-if="hit.collection === 'news'"
      :key="hit.id"
      :news="hit.raw as NewsSearchResult"
    />
    <PageDetailPreview
      v-else-if="hit.collection === 'pages'"
      :key="hit.id"
      :page="hit.raw as PageSearchResult"
    />
    <CalendarDetailPreview
      v-else-if="hit.collection === 'calendar'"
      :key="hit.id"
      :event="hit.raw as CalendarSearchResult"
    />
    <UserDetailPreview
      v-else-if="hit.collection === 'users'"
      :key="hit.id"
      :user="hit.raw as UserSearchResult"
    />

    <!-- Fallback for collections without a dedicated pane -->
    <GenericDetailPreview v-else :key="hit.id" :hit="hit" />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { MousePointerClick } from 'lucide-vue-next';

import MeetingDetailPreview from './Detail/MeetingDetailPreview.vue';
import AgendaItemDetailPreview from './Detail/AgendaItemDetailPreview.vue';
import InstitutionDetailPreview from './Detail/InstitutionDetailPreview.vue';
import ResourceDetailPreview from './Detail/ResourceDetailPreview.vue';
import DutyDetailPreview from './Detail/DutyDetailPreview.vue';
import DocumentDetailPreview from './Detail/DocumentDetailPreview.vue';
import NewsDetailPreview from './Detail/NewsDetailPreview.vue';
import PageDetailPreview from './Detail/PageDetailPreview.vue';
import CalendarDetailPreview from './Detail/CalendarDetailPreview.vue';
import UserDetailPreview from './Detail/UserDetailPreview.vue';
import GenericDetailPreview from './Detail/GenericDetailPreview.vue';
import type { NormalizedSearchHit } from '../Utils/searchHitMappers';

import type {
  AgendaItemSearchResult,
  CalendarSearchResult,
  DocumentSearchResult,
  DutySearchResult,
  InstitutionSearchResult,
  MeetingSearchResult,
  NewsSearchResult,
  PageSearchResult,
  ResourceSearchResult,
  UserSearchResult,
} from '@/Shared/Search/types';

defineProps<{
  hit: NormalizedSearchHit | null;
}>();
</script>
