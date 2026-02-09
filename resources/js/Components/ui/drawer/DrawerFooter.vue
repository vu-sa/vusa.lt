<template>
  <div
    data-slot="drawer-footer"
    :class="cn('mt-auto flex flex-col gap-2 p-4', props.class)"
  >
    <slot />
  </div>
</template>

<script lang="ts" setup>
import type { HtmlHTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  class?: HtmlHTMLAttributes['class'];
}>();
</script>
