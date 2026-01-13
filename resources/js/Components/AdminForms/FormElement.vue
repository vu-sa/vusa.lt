<template>
  <ThemeProvider>
    <section
      class="group/section relative py-6 first:pt-0"
      :class="[
        variant === 'highlighted' && 'my-2 rounded-xl border border-primary/20 bg-primary/[0.02] px-6 dark:border-primary/15 dark:bg-primary/[0.03]',
        variant === 'subtle' && 'opacity-75 hover:opacity-100 transition-opacity duration-300',
      ]"
    >
      <!-- Header row -->
      <div class="flex items-start gap-4">
        <!-- Section indicator -->
        <div
          v-if="sectionNumber || icon"
          class="relative flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold transition-all duration-300"
          :class="[
            isComplete
              ? 'bg-emerald-500 text-white shadow-sm shadow-emerald-500/25 dark:bg-emerald-500 dark:shadow-emerald-500/20'
              : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400'
          ]"
        >
          <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="scale-0 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-0 opacity-0"
            mode="out-in"
          >
            <IFluentCheckmark16Filled v-if="isComplete" class="size-4" />
            <component :is="icon" v-else-if="icon" class="size-4" />
            <span v-else>{{ sectionNumber }}</span>
          </Transition>
        </div>

        <!-- Title block -->
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <h3 class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
              <slot name="title" />
            </h3>
            <span
              v-if="required"
              class="inline-flex items-center rounded-full bg-amber-400/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-amber-600 dark:bg-amber-500/15 dark:text-amber-400"
            >
              {{ $t('Privaloma') }}
            </span>
          </div>
          <p v-if="$slots.subtitle" class="mt-0.5 text-[13px] leading-relaxed text-zinc-500 dark:text-zinc-400">
            <slot name="subtitle" />
          </p>
        </div>
      </div>

      <!-- Content area -->
      <div
        class="mt-4 grid gap-6 lg:grid-cols-12"
        :class="[(sectionNumber || icon) && 'lg:pl-[52px]']"
      >
        <!-- Description sidebar -->
        <aside
          v-if="!noSider && $slots.description"
          class="lg:col-span-4 xl:col-span-3"
        >
          <div class="text-[13px] leading-[1.7] text-zinc-500 dark:text-zinc-400 [&_p]:mb-3 [&_p:last-child]:mb-0 [&_a]:text-primary [&_a]:underline [&_a]:decoration-primary/30 [&_a]:underline-offset-2 hover:[&_a]:decoration-primary/60 [&_strong]:font-medium [&_strong]:text-zinc-700 dark:[&_strong]:text-zinc-300">
            <slot name="description" />
          </div>
        </aside>

        <!-- Main content -->
        <div
          :class="[
            noSider || !$slots.description
              ? 'lg:col-span-12'
              : 'lg:col-span-8 xl:col-span-9'
          ]"
        >
          <slot />
        </div>
      </div>

      <!-- Separator -->
      <Separator v-if="!noDivider" class="mt-8 opacity-50" />
    </section>
  </ThemeProvider>
</template>

<script setup lang="ts">
import type { Component } from "vue";

import { Separator } from "../ui/separator";

import ThemeProvider from "@/Components/Providers/ThemeProvider.vue";

withDefaults(defineProps<{
  noDivider?: boolean;
  noSider?: boolean;
  icon?: Component;
  sectionNumber?: number;
  required?: boolean;
  isComplete?: boolean;
  variant?: 'default' | 'highlighted' | 'subtle';
}>(), {
  variant: 'default',
});
</script>
