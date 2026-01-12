<template>
  <ThemeProvider>
    <Collapsible v-model:open="isOpen">
      <!-- Enhanced header with status indicator -->
      <div
        class="group mb-4 flex w-full items-center justify-between gap-3 py-2 transition-colors"
        :class="[
          hasError ? 'rounded-lg border border-red-200 bg-red-50/30 px-3 dark:border-red-900/50 dark:bg-red-950/20' : '',
        ]"
      >
        <div class="flex items-center gap-3">
          <!-- Section number/icon indicator -->
          <div
            v-if="sectionNumber || icon"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-medium transition-colors"
            :class="[
              isComplete
                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                : hasError
                  ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                  : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
            ]"
          >
            <IFluentCheckmark24Regular v-if="isComplete" class="h-4 w-4" />
            <NIcon v-else-if="icon" :component="icon" class="h-4 w-4" />
            <span v-else>{{ sectionNumber }}</span>
          </div>

          <div>
            <h4 class="mb-0 inline-flex items-center gap-2 text-base font-semibold tracking-normal">
              <slot name="title" />
              <span
                v-if="required"
                class="rounded border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[10px] font-normal text-amber-700 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-400"
              >
                {{ $t('Privaloma') }}
              </span>
            </h4>
            <p v-if="$slots.subtitle" class="mt-0.5 text-xs text-muted-foreground">
              <slot name="subtitle" />
            </p>
          </div>
        </div>

        <CollapsibleTrigger as-child>
          <Button size="sm" variant="ghost" class="h-8 w-8 p-0 shrink-0">
            <IFluentChevronDown24Regular
              class="h-4 w-4 transition-transform duration-200"
              :class="isOpen ? '' : '-rotate-90'"
            />
          </Button>
        </CollapsibleTrigger>
      </div>

      <CollapsibleContent class="grid gap-x-12 overflow-visible lg:grid-cols-6">
        <div v-if="!noSider" class="lg:col-span-2">
          <div class="mb-6 flex flex-col text-xs text-zinc-500 dark:text-zinc-400 [&_p]:mb-2">
            <slot name="description" />
          </div>
        </div>
        <div class="overflow-visible" :class="{ 'lg:col-span-4': !noSider, 'lg:col-span-6': noSider }">
          <slot />
        </div>
      </CollapsibleContent>

      <Separator v-if="!noDivider" class="my-4" />
    </Collapsible>
  </ThemeProvider>
</template>

<script setup lang="tsx">
import { NIcon } from "naive-ui";
import { ref } from "vue";
import type { Component } from "vue";

import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "../ui/collapsible";
import { Separator } from "../ui/separator";

import { Button } from "@/Components/ui/button";
import ThemeProvider from "@/Components/Providers/ThemeProvider.vue";

const props = defineProps<{
  noDivider?: boolean;
  noSider?: boolean;
  isClosed?: boolean;
  icon?: Component;
  sectionNumber?: number;
  required?: boolean;
  isComplete?: boolean;
  hasError?: boolean;
}>();

const isOpen = ref(!props.isClosed);
</script>
