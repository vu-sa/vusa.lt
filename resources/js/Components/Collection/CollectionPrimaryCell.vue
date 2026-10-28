<template>
  <div class="min-w-0" data-slot="collection-primary-cell">
    <Link
      v-if="href"
      :href
      prefetch
      data-collection-open
      :title="title"
      :class="[
        'block font-bold text-foreground hover:text-brand',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
        titleLines === 2 ? 'line-clamp-2' : 'truncate',
      ]"
    >
      <slot><SearchMatch :match="titleMatch" :title /></slot>
    </Link>
    <button
      v-else-if="clickable"
      type="button"
      data-collection-open
      :title="title"
      :class="[
        'block max-w-full text-left font-bold text-foreground hover:text-brand',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
        titleLines === 2 ? 'line-clamp-2' : 'truncate',
      ]"
      @click="emit('open')"
    >
      <slot><SearchMatch :match="titleMatch" :title /></slot>
    </button>
    <p v-else :title="title" :class="['font-bold text-foreground', titleLines === 2 ? 'line-clamp-2' : 'truncate']">
      <slot><SearchMatch :match="titleMatch" :title /></slot>
    </p>

    <!-- A search excerpt stands in for the sub-line, so matching never makes a row taller. -->
    <p
      v-if="sub || $slots.sub"
      :class="['mt-0.5 text-xs text-muted-foreground', subLines === 3 ? 'line-clamp-3 text-pretty' : 'truncate', mono && !subMatch && 'font-mono']"
    >
      <SearchMatch v-if="subMatch" :match="subMatch" />
      <slot v-else name="sub">
        {{ sub }}
      </slot>
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

import SearchMatch from '@/Components/ui/SearchMatch.vue';
import { matchTitle, type SearchMatch as SearchMatchData } from '@/Shared/Search/matches';

/**
 * The first cell of every collection row: a bold title that opens the record, over one quiet
 * line that tells two similar rows apart (a path, an email, a unit). Used by both the table and
 * the rows view so the two read as the same list.
 */
const props = withDefaults(defineProps<{
  match?: SearchMatchData;
  titleMatches?: SearchMatchData[];
  title?: string;
  titleLines?: 1 | 2;
  /** Resolved URL of the record; the link doubles as the preview opener (`data-collection-open`). */
  href?: string;
  /** Renders a button emitting `open` instead of a link — for records edited in a sheet. */
  clickable?: boolean;
  sub?: string | null;
  /** Set when the sub-line is a path, slug or code. */
  mono?: boolean;
  /** A description rather than a distinguishing detail: wraps up to three lines. */
  subLines?: 1 | 3;
}>(), {
  title: undefined,
  titleLines: 1,
  href: undefined,
  clickable: false,
  sub: undefined,
  mono: false,
  subLines: 1,
});

const titleMatch = computed(() => matchTitle(props.title ?? '', props.titleMatches));
const subMatch = computed(() => {
  const text = props.match?.segments.map(segment => segment.text).join('');
  return text && text !== props.title ? props.match : undefined;
});

const emit = defineEmits<{
  open: [];
}>();
</script>
