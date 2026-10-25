<template>
  <PreviewPane
    entity-type="news"
    :eyebrow="$t('Naujiena')"
    :title="news.title || $t('Be pavadinimo')"
    :href="trashed ? undefined : editHref"
  >
    <template #status>
      <slot name="status">
        <StatusBadge :status="newsStatus(news)" />
      </slot>
    </template>

    <template v-if="news.image" #media>
      <div class="size-20 overflow-hidden border border-border bg-muted">
        <img :src="news.image" :alt="news.title" class="size-full object-cover">
      </div>
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="editHref">
            {{ $t('Redaguoti naujieną') }}
          </Link>
        </Button>
        <Button v-if="publicUrl" as-child variant="outline" size="sm">
          <a :href="publicUrl" target="_blank" rel="noopener noreferrer">
            <ExternalLink aria-hidden="true" />
            {{ $t('Atidaryti viešąjį puslapį') }}
          </a>
        </Button>
      </slot>
    </template>

    <p v-if="summary" class="text-sm text-muted-foreground">
      {{ summary }}
    </p>

    <PreviewFacts>
      <PreviewFact :label="$t('Padalinys')" :value="news.tenant_shortname ?? news.tenant_name" />
      <PreviewFact :label="$t('Kalba')" :value="news.lang === 'en' ? 'English' : 'Lietuvių'" />
      <PreviewFact :label="dateLabel ?? $t('Paskelbta')" :value="date ?? publishedAt" tabular />
    </PreviewFacts>
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ExternalLink } from '@lucide/vue';
import { computed } from 'vue';

import { newsStatus } from './newsStatus';

import { PreviewFact, PreviewFacts, PreviewPane, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { stripHtml } from '@/Features/Admin/AdminSearch/Utils/html';
import type { NewsSearchResult } from '@/Shared/Search/types';
import { formatDate } from '@/Utils/dateTime';
import { localizedRoute } from '@/Utils/LocalizedRoutes';

const props = defineProps<{
  news: NewsSearchResult;
  /** A trashed article has no editor to open. */
  trashed?: boolean;
  /** The trash shows when it was deleted instead. */
  dateLabel?: string;
  date?: string;
}>();

const editHref = computed(() => route('news.edit', props.news.id));
const summary = computed(() => stripHtml(props.news.short));
// Search documents store Unix seconds.
const publishedAt = computed(() => (props.news.publish_time ? formatDate(props.news.publish_time * 1000) : null));

const publicUrl = computed(() => {
  if (!props.news.permalink || props.news.draft) {
    return undefined;
  }

  return localizedRoute('news', {
    subdomain: resolveTenantSubdomain(props.news.tenant_id),
    news: props.news.permalink,
  }, props.news.lang ?? 'lt');
});
</script>
