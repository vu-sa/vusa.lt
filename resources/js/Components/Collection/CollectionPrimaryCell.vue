<template>
  <div class="min-w-0" data-slot="collection-primary-cell">
    <Link
      v-if="href"
      :href
      prefetch
      data-collection-open
      class="block truncate font-bold text-foreground hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
    >
      <slot>{{ title }}</slot>
    </Link>
    <button
      v-else-if="clickable"
      type="button"
      class="block max-w-full truncate text-left font-bold text-foreground hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
      @click="emit('open')"
    >
      <slot>{{ title }}</slot>
    </button>
    <p v-else class="truncate font-bold text-foreground">
      <slot>{{ title }}</slot>
    </p>

    <p
      v-if="sub || $slots.sub"
      :class="['mt-0.5 truncate text-xs text-muted-foreground', mono && 'font-mono']"
    >
      <slot name="sub">{{ sub }}</slot>
    </p>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

/**
 * The first cell of every collection row: a bold title that opens the record, over one quiet
 * line that tells two similar rows apart (a path, an email, a unit). Used by both the table and
 * the rows view so the two read as the same list.
 */
withDefaults(defineProps<{
  title?: string;
  /** Resolved URL of the record; the link doubles as the preview opener (`data-collection-open`). */
  href?: string;
  /** Renders a button emitting `open` instead of a link — for records edited in a sheet. */
  clickable?: boolean;
  sub?: string | null;
  /** Set when the sub-line is a path, slug or code. */
  mono?: boolean;
}>(), {
  title: undefined,
  href: undefined,
  clickable: false,
  sub: undefined,
  mono: false,
});

const emit = defineEmits<{
  open: [];
}>();
</script>
