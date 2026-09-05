<template>
  <!-- Title band — the same masthead the news article opens with, so a page and an article read
       as the same kind of document. Suppressed entirely when the author has hidden the title
       (a page that opens directly on a hero block already carries one of its own); the trail
       then falls back to PublicLayout's own bar. See `.band-masthead` for the grounds.

       The `-mt-*` pull-up mirrors PublicLayout's content wrapper (`pt-4 md:pt-6 lg:pt-8`) so the
       band sits flush against the fixed header instead of below a strip of page background. -->
  <header v-if="showTitle" class="band-masthead rc-viewport -mt-4 border-b border-border md:-mt-6 lg:-mt-8">
    <div
      class="mx-auto grid max-w-7xl gap-10 px-5 py-14 sm:px-6 lg:px-8 lg:py-20"
      :class="page.featured_image && 'lg:grid-cols-2 lg:gap-16'"
    >
      <div class="flex flex-col justify-center">
        <PublicBreadcrumbs v-if="showBreadcrumbs" variant="inline" class="mb-8" />

        <div class="border-l-2 border-brand pl-5 sm:pl-7">
          <EyebrowLabel v-if="categoryName">
            {{ categoryName }}
          </EyebrowLabel>
          <h1 :class="['u-display text-4xl sm:text-6xl', categoryName && 'mt-3']">
            {{ page.title }}
          </h1>
        </div>

        <p
          v-if="page.meta_description"
          class="mt-7 max-w-xl text-pretty pl-5 text-lg leading-relaxed text-muted-foreground sm:pl-7"
        >
          {{ page.meta_description }}
        </p>
      </div>

      <MediaFrame
        v-if="page.featured_image"
        :src="page.featured_image"
        :alt="page.title"
        ratio="4/3"
        eager
        class="lg:aspect-auto lg:h-full"
      />
    </div>
  </header>

  <!-- Default layout: content canvas + sticky sidebar for the ToC.
       `.rc-shell` keeps the sidebar from clipping full-width blocks down to a second,
       narrower measure — the canvas inside it still gets the full page width. -->
  <div v-if="pageLayout === 'default'" class="rc-shell pt-12 pb-16 md:pb-24">
    <!-- `text-base md:text-lg` matches NewsArticleLayout. Without it this layout fell
         through to the 16px browser default across a 44rem measure — ~90 characters
         per line, well past the comfortable 60-75. Line-height comes from `.rc-prose`. -->
    <div class="rc-canvas text-base md:text-[1.0625rem]" style="--rc-measure: 44rem" :data-align="hasToc ? 'start' : undefined">
      <RichContentParser :content="(page.content?.parts as unknown as models.ContentPart[]) ?? []" :resolved="resolvedParts" />
      <LastUpdatedFooter v-if="showTitle" :date="lastUpdatedDate" />
    </div>
    <aside v-if="hasToc" class="rc-aside hidden lg:block">
      <TableOfContents :links="anchorLinks" :offset="160" />
    </aside>
  </div>

  <!-- Wide layout: full page width, great for pages with images/grids -->
  <div v-else-if="pageLayout === 'wide'" class="rc-canvas pt-12 pb-16 text-base md:pb-24 md:text-[1.0625rem]" style="--rc-measure: 58rem">
    <RichContentParser :content="(page.content?.parts as unknown as models.ContentPart[]) ?? []" :resolved="resolvedParts" />
    <LastUpdatedFooter v-if="showTitle" :date="lastUpdatedDate" />
  </div>

  <!-- Focused layout: centered, narrow reading width for long-form text -->
  <div v-else-if="pageLayout === 'focused'" class="rc-canvas pt-12 pb-16 text-[1.0625rem] leading-8 md:pb-24 md:text-lg" style="--rc-measure: 40rem">
    <RichContentParser :content="(page.content?.parts as unknown as models.ContentPart[]) ?? []" :resolved="resolvedParts" />
    <LastUpdatedFooter v-if="showTitle" :date="lastUpdatedDate" />
  </div>

  <!-- Highlights floating button -->
  <HighlightsFloatingButton :highlights="page.highlights" />

  <FeedbackPopover />
</template>

<script setup lang="ts">
import { computed, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';

import FeedbackPopover from '@/Components/Public/FeedbackPopover.vue';
import HighlightsFloatingButton from '@/Components/Public/HighlightsFloatingButton.vue';
import LastUpdatedFooter from '@/Components/Public/LastUpdatedFooter.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import RichContentParser from '@/Components/RichContent/RichContentParser.vue';
import { extractAnchorLinks, type AnchorablePart } from '@/Components/RichContent/tocAnchors';
import TableOfContents from '@/Components/Public/TableOfContents.vue';
import { EyebrowLabel, MediaFrame } from '@/Components/Public/Base';
import { usePageBreadcrumbs, useBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

type PageContentPart = AnchorablePart & { [key: string]: any };

interface Page {
  title: string;
  layout?: 'default' | 'wide' | 'focused';
  show_table_of_contents?: boolean;
  show_title?: boolean;
  show_breadcrumbs?: boolean;
  highlights?: string[] | null;
  /** The whole Category relation — the band shows its name as the eyebrow. */
  category?: { name?: string | null } | null;
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

// `category` arrives as the whole relation (the controller's `only()` resolves it).
const categoryName = computed(() => props.page.category?.name ?? undefined);

// The sidebar ToC only applies to the `default` layout, requires at least one anchor,
// and can be turned off per-page (Advanced Settings in PageForm).
const hasToc = computed(() =>
  pageLayout.value === 'default' && props.page.show_table_of_contents !== false && anchorLinks.length > 0,
);

// An author can hide the page's own title — e.g. when the page opens directly on a
// hero/section block that already carries one. The whole masthead goes with it.
const showTitle = computed(() => props.page.show_title !== false);

// An author can hide the breadcrumb trail (Advanced Settings in PageForm).
const showBreadcrumbs = computed(() => props.page.show_breadcrumbs !== false);

// Raw date, formatted by LastUpdatedFooter itself (always static/absolute — a
// relative "3 days ago" reads ambiguously once it's tucked into a page-bottom footer
// rather than sitting right under the title, and it demanded re-rendering to stay
// accurate as time passed).
const lastUpdatedDate = computed(() => props.page.last_edited_at || props.page.updated_at || null);

// Set breadcrumbs for content page.
//
// The trail lives inside the masthead when there is one; without a title band there is nowhere
// to put it, so it falls back to the bar PublicLayout draws above page content.
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
}, { placement: showTitle.value ? 'band' : 'layout' });

// usePageBreadcrumbs persists breadcrumbs across navigation and only sets when the
// getter is non-empty, so a suppressed page would otherwise inherit the *previous*
// page's trail. Explicitly clear the global state whenever breadcrumbs are off.
const { clear: clearBreadcrumbs } = useBreadcrumbs();
watchEffect(() => {
  if (!showBreadcrumbs.value) clearBreadcrumbs();
});

// Tiptap h2/h3(/h4) headings + titled section blocks (hero, accordion, card-stack, …) —
// see tocAnchors.ts. A page built entirely from section blocks used to get an empty
// ToC because only tiptap headings were ever indexed.
const anchorLinks = extractAnchorLinks(props.page.content?.parts as PageContentPart[] | undefined);
</script>
