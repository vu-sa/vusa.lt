<template>
  <div class="topic-page">
    <Head>
      <title>{{ topic.name }}</title>
      <meta v-if="topic.description" name="description" :content="topic.description">
    </Head>

    <PageTitleBand
      :title="topic.name"
      :lead="topic.description || undefined"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>
    </PageTitleBand>

    <SectionBand v-if="pages.length > 0" divider="bottom" spacing="tight">
      <EyebrowLabel as="h2" class="mb-6 inline-flex items-center gap-1.5">
        <IFluentPage class="size-4" /> {{ $t('topics.pages_heading') }}
      </EyebrowLabel>

      <HairlineList as="ul">
        <HairlineRow v-for="pageItem in pages" :key="pageItem.id" as="li" :title="pageItem.title"
          :meta="pageItem.tenant_name" :href="pageItem.public_url ?? undefined">
          <template #trailing>
            <IFluentChevronRight16Regular class="size-4" />
          </template>
        </HairlineRow>
      </HairlineList>
    </SectionBand>

    <SectionBand v-if="news.length > 0" divider="bottom" spacing="tight">
      <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <EyebrowLabel as="h2" class="inline-flex items-center gap-1.5">
          <IFluentNews class="size-4" /> {{ $t('topics.news_heading') }}
        </EyebrowLabel>
        <SmartLink
          :href="newsArchiveHref"
          prefetch
          class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-foreground transition-colors hover:text-brand"
        >
          {{ $t('Žiūrėti visas') }}
          <IFluentChevronRight16Regular class="size-4" />
        </SmartLink>
      </div>

      <div class="grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
        <NewsCard v-for="article in news" :key="article.id" :news="article" size="sm" show-excerpt />
      </div>
    </SectionBand>

    <SectionBand v-if="events.length > 0" spacing="tight">
      <EyebrowLabel as="h2" class="mb-6 inline-flex items-center gap-1.5">
        <IFluentCalendar class="size-4" /> {{ $t('topics.events_heading') }}
      </EyebrowLabel>

      <HairlineList as="ul">
        <HairlineRow v-for="event in events" :key="event.id" as="li" :title="event.title"
          :meta="formatEventMeta(event)" :href="event.public_url ?? undefined">
          <template #trailing>
            <IFluentChevronRight16Regular class="size-4" />
          </template>
        </HairlineRow>
      </HairlineList>
    </SectionBand>

    <section v-if="pages.length === 0 && news.length === 0 && events.length === 0"
      class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <div class="border border-border bg-card p-12 text-center">
        <p class="text-sm text-muted-foreground">
          {{ $t('topics.empty') }}
        </p>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import EyebrowLabel from '@/Components/Brand/EyebrowLabel.vue';
import HairlineList from '@/Components/Public/Base/HairlineList.vue';
import HairlineRow from '@/Components/Public/Base/HairlineRow.vue';
import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import SectionBand from '@/Components/Public/Base/SectionBand.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import NewsCard from '@/Components/Public/News/NewsCard.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { formatEventDate } from '@/Utils/IntlTime';
import { LocaleEnum } from '@/Types/enums';
import type { NewsItem } from '@/Types/contentParts';
import IFluentChevronRight16Regular from '~icons/fluent/chevron-right-16-regular';
import IFluentPage from '~icons/fluent/document-text20-regular';
import IFluentNews from '~icons/fluent/news20-regular';
import IFluentCalendar from '~icons/fluent/calendar20-regular';

interface TopicListItem {
  id: number;
  title: string;
  tenant_name: string | null;
  public_url: string | null;
}

interface TopicEventItem extends TopicListItem {
  date: string;
}

const props = defineProps<{
  topic: {
    id: number;
    name: string;
    description?: string | null;
    alias: string;
  };
  news: NewsItem[];
  pages: TopicListItem[];
  events: TopicEventItem[];
}>();

const page = usePage();
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);
const subdomain = computed(() => page.props.tenant?.subdomain ?? 'www');

// Pre-filters the archive to this topic's tag. The `?tag=` param is read two different
// ways depending on where: NewsController::newsArchive() resolves the initial SSR list
// by alias, but the page hydrates to Typesense within moments (useNewsSearch's
// `parseUrlParams()` feeds the raw param straight into a `tag_names:=[...]` filter,
// and `tag_names` in the search index holds each tag's *name*, not its alias) — so the
// name is what has to survive past that hydration.
const newsArchiveHref = computed(() => route('newsArchive', {
  lang: locale.value,
  subdomain: subdomain.value,
  tag: props.topic.name,
}));

function formatEventMeta(event: TopicEventItem): string {
  const date = formatEventDate(new Date(event.date), locale.value);

  return event.tenant_name ? `${date} · ${event.tenant_name}` : date;
}

usePageBreadcrumbs(() => {
  return BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      props.topic.name || $t('topics.pages_heading'),
    ),
  ]);
}, { placement: 'band' });
</script>
