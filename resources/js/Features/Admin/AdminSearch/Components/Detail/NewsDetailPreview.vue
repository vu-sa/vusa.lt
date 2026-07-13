<template>
  <DetailLayout
    :icon="NewsIcon"
    :kicker="$t('Naujiena')"
    :title="news.title || $t('Be pavadinimo')"
    :subtitle="stripHtml(news.short)"
  >
    <template #badges>
      <Badge v-if="news.lang" variant="outline" class="uppercase">
        {{ news.lang }}
      </Badge>
      <Badge v-if="news.tenant_name" variant="secondary">
        {{ news.tenant_name }}
      </Badge>
    </template>

    <template #media>
      <div v-if="news.image" class="size-20 shrink-0 overflow-hidden rounded-lg border">
        <img :src="news.image" :alt="news.title" class="size-full object-cover">
      </div>
    </template>

    <template #actions>
      <a v-if="publicUrl" :href="publicUrl" target="_blank" rel="noopener noreferrer">
        <Button size="sm">
          <ExternalLink class="mr-2 size-4" />
          {{ $t('Atidaryti viešąjį puslapį') }}
        </Button>
      </a>
      <Link :href="route('news.edit', news.id)">
        <Button size="sm" variant="outline">
          <Pencil class="mr-2 size-4" />
          {{ $t('Redaguoti') }}
        </Button>
      </Link>
    </template>

    <div class="divide-y rounded-lg border px-4">
      <DetailRow :label="$t('Pavadinimas')" :value="news.title || '—'" />
      <DetailRow v-if="news.short" :label="$t('Santrauka')" :value="stripHtml(news.short)" />
      <DetailRow :label="$t('Kalba')" :value="news.lang?.toUpperCase() || '—'" />
      <DetailRow v-if="news.tenant_name" :label="$t('Padalinys')" :value="news.tenant_name" />
      <DetailRow :label="$t('Publikavimo data')" :value="formatSearchDate(news.publish_time) || '—'" />
      <DetailRow v-if="news.permalink" :label="$t('Nuoroda')" :value="news.permalink" />
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ExternalLink, Pencil } from 'lucide-vue-next';

import { formatSearchDate } from '../../Utils/searchHitMappers';
import { stripHtml } from '../../Utils/html';

import DetailRow from './DetailRow.vue';
import DetailLayout from './DetailLayout.vue';

import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { NewsIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import type { NewsSearchResult } from '@/Shared/Search/types';

const props = defineProps<{
  news: NewsSearchResult;
}>();

const publicUrl = computed(() => {
  if (!props.news.permalink) {
    return undefined;
  }
  const lang = props.news.lang ?? 'lt';
  return route('news', {
    subdomain: resolveTenantSubdomain(props.news.tenant_id),
    lang,
    newsString: lang === 'lt' ? 'naujiena' : 'news',
    news: props.news.permalink,
  });
});
</script>
