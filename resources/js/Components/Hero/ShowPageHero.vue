<template>
  <section
    :class="cn(
      'relative overflow-hidden rounded-lg px-4 sm:px-6 py-4 sm:py-5 shadow-sm',
      'bg-gradient-to-br from-primary/10 via-primary/5 to-background',
      'dark:from-zinc-800 dark:via-zinc-800/50 dark:to-zinc-900 dark:shadow-zinc-900/20',
      props.class
    )"
    :style="viewTransitionStyle"
    data-slot="show-page-hero"
  >
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
      <!-- Left: Icon, Title, Subtitle -->
      <div class="flex items-start gap-3 sm:gap-4 min-w-0 flex-1">
        <!-- Icon -->
        <div
          v-if="icon || $slots.icon"
          class="shrink-0 h-12 w-12 sm:h-14 sm:w-14 bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-700 dark:to-zinc-800 rounded-lg flex items-center justify-center border border-zinc-200 dark:border-zinc-600"
        >
          <slot name="icon">
            <component
              :is="icon"
              class="h-6 w-6 sm:h-7 sm:w-7 text-zinc-600 dark:text-zinc-300"
            />
          </slot>
        </div>

        <div class="min-w-0 flex-1 space-y-1">
          <!-- Badge row -->
          <div v-if="badge || $slots.badge" class="flex items-center gap-2 flex-wrap">
            <slot name="badge">
              <Badge v-if="badge" :variant="badge.variant || 'secondary'" class="gap-1">
                <component v-if="badge.icon" :is="badge.icon" class="h-3 w-3" />
                {{ badge.label }}
              </Badge>
            </slot>
          </div>

          <!-- Title -->
          <h1 class="text-xl sm:text-2xl md:text-3xl font-bold tracking-tight text-foreground line-clamp-2 sm:truncate">
            <slot name="title">{{ title }}</slot>
          </h1>

          <!-- Subtitle -->
          <div v-if="subtitle || $slots.subtitle" class="flex items-center gap-2 text-sm text-muted-foreground font-semibold">
            <slot name="subtitle">
              <span class="truncate">{{ subtitle }}</span>
            </slot>
          </div>

          <!-- Extra info row -->
          <div v-if="$slots.info" class="flex flex-wrap items-center gap-2 sm:gap-3 pt-1">
            <slot name="info" />
          </div>
        </div>
      </div>

      <!-- Right: Actions -->
      <div v-if="$slots.actions" class="shrink-0 flex items-center gap-2 self-end sm:self-start">
        <slot name="actions" />
      </div>
    </div>

    <!-- Navigation (optional) -->
    <div v-if="$slots.navigation" class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
      <slot name="navigation" />
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue'
import { computed } from 'vue'

import { cn } from '@/Utils/Shadcn/utils'
import { Badge } from '@/Components/ui/badge'

interface BadgeConfig {
  label: string
  variant?: 'default' | 'secondary' | 'outline' | 'destructive'
  icon?: Component
}

const props = withDefaults(defineProps<{
  title?: string
  subtitle?: string
  icon?: Component
  badge?: BadgeConfig
  viewTransitionName?: string
  class?: HTMLAttributes['class']
}>(), {
  title: undefined,
  subtitle: undefined,
  icon: undefined,
  badge: undefined,
  viewTransitionName: undefined,
})

const viewTransitionStyle = computed(() => {
  if (!props.viewTransitionName) return undefined
  return { viewTransitionName: props.viewTransitionName }
})
</script>
