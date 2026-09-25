<template>
  <!-- type="button": editors sit inside FormPage's <form>, where a bare button submits it. -->
  <Button
    type="button"
    size="icon-sm"
    voice="plain"
    :variant="active ? 'brand' : 'ghost'"
    :title="label"
    :aria-label="label"
    :aria-pressed="toggle ? active : undefined"
    data-slot="tiptap-tool"
  >
    <slot />
  </Button>
</template>

<script setup lang="ts">
import { Button } from '@/Components/ui/button';

withDefaults(defineProps<{
  label: string;
  active?: boolean;
  /** Announce pressed state; off for one-shot actions like undo or insert. */
  toggle?: boolean;
}>(), {
  active: false,
  toggle: false,
});
</script>
