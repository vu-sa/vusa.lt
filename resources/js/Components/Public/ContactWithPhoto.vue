<template>
  <div
    class="text-neutral-50 shadow-lg rounded-lg gap-2 flex flex-col min-w-[10em] w-auto bg-white relative h-min"
  >
    <slot name="image"></slot>
    <div class="text-gray-800 px-4 py-2">
      <h2><slot name="name"></slot></h2>
      <p class="mb-2">
        <slot name="duty"></slot
        ><NPopover :show-arrow="false" :style="{ maxWidth: '300px' }"
          ><template #trigger>
            <NIcon><QuestionCircle24Regular /></NIcon> </template
          ><slot name="description"></slot
        ></NPopover>
      </p>
      <div class="mb-2 flex flex-row items-center gap-2 flex-wrap">
        <slot name="phone"></slot>
        <slot name="email"></slot>
      </div>
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
import { NPopover, NIcon } from "naive-ui";
import { QuestionCircle24Regular } from "@vicons/fluent";
</script>
