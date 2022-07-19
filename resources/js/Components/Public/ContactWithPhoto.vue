<template>
  <div class="flex flex-col bg-white lg:flex-row h-auto min-h-fit rounded-lg">
    <div
      v-if="props.imageSrc"
      class="flex-none h-60 lg:h-auto w-auto lg:w-40 relative"
    >
      <img
        :src="props.imageSrc"
        class="absolute inset-0 w-full h-full object-cover rounded-t-lg lg:rounded-t-none lg:rounded-l-lg"
        style="object-position: 50% 25%"
      />
    </div>
    <div class="flex flex-col flex-auto p-4 justify-between gap-4">
      <div class="flex flex-wrap flex-col">
        <h2 class="flex-auto text-slate-900 px-2">
          <slot name="name"></slot>
        </h2>
        <div class="text-sm font-medium text-gray-500 w-fit p-2">
          <slot name="duty"></slot>
        </div>
      </div>
      <div class="text-sm text-neutral-500 flex flex-col gap-2">
        <slot name="contactInfo"></slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { NIcon, NPopover } from "naive-ui";
import { QuestionCircle24Regular } from "@vicons/fluent";

const props = defineProps({
  imageSrc: String,
});
</script>
