<template>
  <section
    :class="cn('relative mb-10 overflow-hidden rounded-lg bg-gradient-to-br from-primary/10 to-primary/5 px-8 py-7 shadow-sm dark:from-primary/20 dark:to-background', props.class)"
    data-slot="page-hero">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl text-primary dark:text-primary-foreground/90">
          {{ title }}
        </h1>
        <p v-if="subtitle" class="mt-3 max-w-xl text-muted-foreground">
          {{ subtitle }}
        </p>
      </div>
      <div class="shrink-0">
        <slot name="actions" />
      </div>
    </div>
    <slot />
  </section>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue'

import { cn } from '@/Utils/Shadcn/utils'

const props = withDefaults(defineProps<{
  title: string
  subtitle?: string
  class?: HTMLAttributes['class']
}>(), {
  subtitle: undefined,
})
</script>
