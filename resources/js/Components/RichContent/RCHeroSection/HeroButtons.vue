<template>
  <div v-if="buttons && buttons.length > 0" :class="['flex flex-col items-start gap-3 sm:flex-row sm:items-center 2xl:gap-4', props.class]">
    <template v-for="(button, index) in buttons" :key="index">
      <SmartLink :href="button.link" class="w-fit">
        <!-- No `mr-2` on the icon: Button's base already applies `gap-2`, and adding a margin
             on top is what made the icon-to-label spacing differ between controls. -->
        <Button
          :variant="button.variant === 'outline' ? (resolvedOnDark ? 'brand-outline-on-dark' : 'brand-outline') : 'brand'"
          size="public"
          class="w-full sm:w-auto"
        >
          <RCIcon v-if="button.icon" :name="button.icon" class="size-4" />
          {{ button.text }}
        </Button>
      </SmartLink>
    </template>
  </div>
</template>

<script setup lang="ts">
/**
 * A hero's call-to-action row.
 *
 * A call to action is the brand fill — VU SA red on the light canvas, amber on near-black — and
 * that is not an authorable choice. `variant` carries the only distinction that means something
 * to a reader: is this the thing to do, or the alternative beside it. Both looks live in
 * `buttonVariants` (`brand` / `brand-outline`) so every public control shares them. On fixed-dark
 * grounds (like the full-width hero), `onDark` chooses `brand-outline-on-dark`.
 */
import { computed } from 'vue';

import RCIcon from '../RCIcon.vue';

import SmartLink from '@/Components/Public/SmartLink.vue';
import { Button } from '@/Components/ui/button';
import type { Hero } from '@/Types/contentParts';

const props = defineProps<{
  buttons?: Hero['json_content']['buttons'];
  onDark?: boolean;
  class?: string;
}>();

const resolvedOnDark = computed(() => Boolean(props.onDark || props.class?.includes('dark')));
</script>
