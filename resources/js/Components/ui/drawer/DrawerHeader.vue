<template>
  <div
    data-slot="drawer-header"
    :class="cn('flex flex-col gap-1.5 p-4', props.class)"
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
