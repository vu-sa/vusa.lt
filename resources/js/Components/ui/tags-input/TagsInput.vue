<template>
  <TagsInputRoot v-bind="forwarded" :class="cn('flex flex-wrap gap-2 items-center rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950', props.class)">
    <slot />
  </TagsInputRoot>
</template>

<script setup lang="ts">
import type { TagsInputRootEmits, TagsInputRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { TagsInputRoot, useForwardPropsEmits } from 'reka-ui';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<TagsInputRootProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<TagsInputRootEmits>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>
