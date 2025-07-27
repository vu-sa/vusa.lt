<template>
  <Collapsible v-model:open="isOpen">
    <div class="mb-4 flex w-full items-center justify-between gap-4">
      <h4 class="mb-0 mt-4 inline-flex items-center gap-2 tracking-normal">
        <NIcon v-if="icon" :component="icon" />
        <slot name="title" />
      </h4>
      <CollapsibleTrigger as-child>
        <NButton size="tiny">
          <span v-if="isOpen">{{ $t('Paslėpti') }}</span>
          <span v-else>{{ $t('Rodyti') }}</span>
          <template #icon>
            <IFluentChevronDown24Regular v-if="isOpen" />
            <IFluentChevronRight24Regular v-else />
          </template>
        </NButton>
      </CollapsibleTrigger>
    </div>
    <CollapsibleContent class="grid gap-x-12 lg:grid-cols-6">
      <div v-if="!noSider" class="lg:col-span-2">
        <div class="mb-6 flex flex-col text-xs text-zinc-500 dark:text-zinc-400 [&_p]:mb-2">
          <slot name="description" />
        </div>
      </div>
      <div :class="{ 'lg:col-span-4': !noSider, 'lg:col-span-6': noSider }">
        <slot />
      </div>
    </CollapsibleContent>
    <div class="lg:col-span-6">
      <Separator v-if="!noDivider" />
    </div>
  </Collapsible>
</template>

<script setup lang="tsx">
import { NButton, NIcon } from "naive-ui";
import { ref } from "vue";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "../ui/collapsible";
import type { Component } from "vue";
import { Separator } from "../ui/separator";

const props = defineProps<{
  noDivider?: boolean;
  noSider?: boolean;
  isClosed?: boolean;
  icon?: Component;
}>();

const isOpen = ref(!props.isClosed);
</script>
