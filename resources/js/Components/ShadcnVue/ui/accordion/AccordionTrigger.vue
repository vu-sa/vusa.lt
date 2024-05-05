<script setup lang="ts">
import {
  AccordionHeader,
  AccordionTrigger,
  type AccordionTriggerProps,
} from 'radix-vue'
import { type HTMLAttributes, computed } from 'vue'
import { Icon } from '@iconify/vue';
import { cn } from '@/Utils/shadcn'

const props = defineProps<AccordionTriggerProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <AccordionHeader class="flex">
    <AccordionTrigger v-bind="delegatedProps" :class="cn(
      'flex flex-1 gap-6 items-center justify-between py-4 font-medium transition-all hover:underline [&[data-state=open]>svg]:rotate-180 text-left',
      props.class,
    )
      ">
      <slot />
      <slot name="icon">
        <Icon icon="lucide:chevron-down" class="size-4 shrink-0 transition-transform duration-200" />
      </slot>
    </AccordionTrigger>
  </AccordionHeader>
</template>

<style scoped>
h3 {
  font-size: 18px;
  font-weight: 500;
  margin: 1.2rem 0;
  line-height: 1.2rem;
}

button {
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  cursor: pointer;
  outline: inherit;
}
</style>
