<template>
  <section
    :class="cn('relative mb-10 overflow-hidden rounded-lg bg-gradient-to-br from-primary/10 via-primary/5 to-background px-8 py-7 shadow-sm dark:from-zinc-800 dark:via-zinc-800/50 dark:to-zinc-900 dark:shadow-zinc-900/20', props.class)"
    data-slot="page-hero">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl text-foreground">
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
