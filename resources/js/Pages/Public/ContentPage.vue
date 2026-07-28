<template>
  <!-- Default layout: content canvas + sticky sidebar for the ToC.
       `.rc-shell` keeps the sidebar from clipping full-width blocks down to a second,
       narrower measure — the canvas inside it still gets the full page width. -->
  <div v-if="pageLayout === 'default'" class="rc-shell pt-8 last:pb-2">
    <div class="rc-canvas" style="--rc-measure: 44rem" :data-align="hasToc ? 'start' : undefined">
      <header v-if="showTitle" class="mb-2">
        <h1 class="text-3xl font-bold md:text-4xl">
          <span class="text-gray-900 dark:text-white">{{ page.title }}</span>
        </h1>
        <span v-if="lastUpdatedText" class="mt-1 inline-flex items-center gap-1 text-xs text-zinc-400 dark:text-zinc-500">
          <ClockIcon class="size-3" />
          {{ lastUpdatedText }}
        </span>
      </header>
      <RichContentParser :content="(page.content?.parts as unknown as models.ContentPart[]) ?? []" :resolved="resolvedParts" />
    </div>
    <aside v-if="hasToc" class="rc-aside hidden lg:block">
      <TableOfContents :links="anchorLinks" :offset="160" :show-mobile-button="false" />
    </aside>
  </div>

  <!-- Wide layout: full page width, great for pages with images/grids -->
  <div v-else-if="pageLayout === 'wide'" class="rc-canvas pt-8 last:pb-2" style="--rc-measure: 58rem">
    <header v-if="showTitle" class="mb-2">
      <h1 class="text-3xl font-bold text-gray-900 md:text-4xl lg:text-5xl dark:text-white">
        {{ page.title }}
      </h1>
      <span v-if="lastUpdatedText" class="mt-1 inline-flex items-center gap-1 text-xs text-zinc-400 dark:text-zinc-500">
        <ClockIcon class="size-3" />
        {{ lastUpdatedText }}
      </span>
    </header>
    <RichContentParser :content="(page.content?.parts as unknown as models.ContentPart[]) ?? []" :resolved="resolvedParts" />
  </div>

  <!-- Focused layout: centered, narrow reading width for long-form text -->
  <div v-else-if="pageLayout === 'focused'" class="rc-canvas pt-8 text-lg leading-8 last:pb-2" style="--rc-measure: 40rem">
    <!-- Optional featured image -->
    <div v-if="page.featured_image" class="mb-8 overflow-hidden rounded-xl">
      <img
        :src="page.featured_image"
        :alt="page.title"
        class="h-auto max-h-[400px] w-full object-cover"
      >
    </div>
    <header class="mb-8 text-center">
      <h1 v-if="showTitle" class="text-3xl font-bold text-gray-900 md:text-4xl lg:text-5xl dark:text-white">
        {{ page.title }}
      </h1>
      <div v-if="page.meta_description" class="mt-4 text-lg text-muted-foreground">
        {{ page.meta_description }}
      </div>
      <span v-if="showTitle && lastUpdatedText" class="mt-2 inline-flex items-center justify-center gap-1 text-xs text-zinc-400 dark:text-zinc-500">
        <ClockIcon class="size-3" />
        {{ lastUpdatedText }}
      </span>
    </header>
    <RichContentParser :content="(page.content?.parts as unknown as models.ContentPart[]) ?? []" :resolved="resolvedParts" />
  </div>

  <!-- Mobile ToC (shows for default layout with anchors) -->
  <TableOfContents
    v-if="pageLayout === 'default' && hasToc"
    :links="anchorLinks"
    :offset="160"
    mobile-only
    show-mobile-button
  />

  <!-- Highlights floating button -->
  <HighlightsFloatingButton :highlights="page.highlights" />

  <FeedbackPopover />
</template>

<script setup lang="ts">
import { computed, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ClockIcon } from 'lucide-vue-next';

import FeedbackPopover from '@/Components/Public/FeedbackPopover.vue';
import HighlightsFloatingButton from '@/Components/Public/HighlightsFloatingButton.vue';
import RichContentParser from '@/Components/RichContent/RichContentParser.vue';
import { extractAnchorLinks, type AnchorablePart } from '@/Components/RichContent/tocAnchors';
import TableOfContents from '@/Components/Public/TableOfContents.vue';
import { usePageBreadcrumbs, useBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { formatRelativeTime, formatStaticTime } from '@/Utils/IntlTime';
import { LocaleEnum } from '@/Types/enums';

type PageContentPart = AnchorablePart & { [key: string]: any };

interface Page {
  title: string;
  layout?: 'default' | 'wide' | 'focused';
  show_table_of_contents?: boolean;
  show_title?: boolean;
  show_breadcrumbs?: boolean;
  highlights?: string[] | null;
  meta_description?: string | null;
  featured_image?: string | null;
  last_edited_at?: string | null;
  updated_at?: string | null;
  content?: {
    parts: PageContentPart[];
  };
}

const props = defineProps<{
  navigationItemId: number;
  page: Page;
  /** Server-resolved dynamic blocks (link-list, event-list, …) keyed by content-part id. */
  resolvedParts?: Record<number, unknown>;
}>();

const inertiaPage = usePage();

// Compute layout with default fallback
const pageLayout = computed(() => props.page.layout || 'default');

// The sidebar ToC only applies to the `default` layout, requires at least one anchor,
// and can be turned off per-page (Advanced Settings in PageForm).
const hasToc = computed(() =>
  pageLayout.value === 'default' && props.page.show_table_of_contents !== false && anchorLinks.length > 0,
);

// An author can hide the page's own <h1>/last-updated header — e.g. when the page
// opens directly on a hero/section block that already carries a title of its own.
const showTitle = computed(() => props.page.show_title !== false);

// An author can hide the breadcrumb trail (Advanced Settings in PageForm).
const showBreadcrumbs = computed(() => props.page.show_breadcrumbs !== false);

// Compute locale for formatting
const locale = computed(() =>
  inertiaPage.props.app?.locale === 'en' ? LocaleEnum.EN : LocaleEnum.LT,
);

// Compute last updated text - relative for up to 7 days, absolute otherwise
const lastUpdatedText = computed(() => {
  const dateString = props.page.last_edited_at || props.page.updated_at;
  if (!dateString) return null;

  const date = new Date(dateString);
  const now = new Date();
  const diffInDays = Math.abs(Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24)));

  if (diffInDays <= 7) {
    return formatRelativeTime(date, { numeric: 'auto' }, locale.value);
  }

  return formatStaticTime(date, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }, locale.value);
});

// Set breadcrumbs for content page
usePageBreadcrumbs(() => {
  // Author disabled breadcrumbs for this page — return nothing so the setter skips.
  if (!showBreadcrumbs.value) return [];

  const mainNavigation = inertiaPage.props.mainNavigation || [];

  // Build breadcrumb items for the content page
  const navigationPath = BreadcrumbHelpers.buildNavigationPath(props.navigationItemId, mainNavigation);

  // If we have navigation path, use it for breadcrumbs
  if (navigationPath.length > 0) {
    return BreadcrumbHelpers.publicContent(navigationPath);
  }

  // Otherwise just show the current page title
  return BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(props.page.title),
  ]);
});

// usePageBreadcrumbs persists breadcrumbs across navigation and only sets when the
// getter is non-empty, so a suppressed page would otherwise inherit the *previous*
// page's trail. Explicitly clear the global state whenever breadcrumbs are off.
const { clear: clearBreadcrumbs } = useBreadcrumbs();
watchEffect(() => {
  if (!showBreadcrumbs.value) clearBreadcrumbs();
});

// Tiptap h2/h3 headings + titled section blocks (hero, accordion, card-stack, …) —
// see tocAnchors.ts. A page built entirely from section blocks used to get an empty
// ToC because only tiptap headings were ever indexed.
const anchorLinks = extractAnchorLinks(props.page.content?.parts as PageContentPart[] | undefined);
</script>
