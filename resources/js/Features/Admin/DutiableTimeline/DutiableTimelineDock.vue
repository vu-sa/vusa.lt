<template>
  <!--
    Sticky rather than merely last: on a tall institution chart the save controls used to
    scroll out of view, so "you have unsaved changes" was invisible exactly when it
    mattered. On a short one nothing scrolls, so it simply sits under the chart rather
    than being pushed to the bottom of an empty page.

    A fixed height, not `max-h`: each column then scrolls inside its own box and its
    action button stays pinned, instead of the longest column pushing the others' buttons
    past the fold.
  -->
  <div
    data-slot="dutiable-timeline-dock"
    class="sticky bottom-0 z-10 grid h-56 shrink-0 grid-cols-1 divide-y divide-border rounded-md border border-border bg-card lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:divide-x lg:divide-y-0"
  >
    <slot name="selection" />
    <slot name="suggestions" />
    <slot name="save" />
  </div>
</template>
