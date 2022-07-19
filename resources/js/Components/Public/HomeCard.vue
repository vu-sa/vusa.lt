<template>
  <article class="relative">
    <div
      v-if="hasMiniContent"
      class="absolute -top-2 -right-1 bg-white rounded-xl border border-gray-100 px-2 shadow-sm hover:shadow-md duration-200 text-gray-500 line-clamp-1"
    >
      <slot name="mini"></slot>
    </div>
    <slot name="image"></slot>
    <div
      v-if="hasBelowCard"
      class="flex items-center mt-1 text-gray-600 text-sm"
    >
      <slot name="below-card"></slot>
    </div>
    <h3 class="text-gray-800 mt-1 line-clamp-2 leading-tight"><slot></slot></h3>
  </article>
</template>

<script setup lang="ts">
import { CalendarLtr20Regular } from "@vicons/fluent";
import { NIcon } from "naive-ui";
import { ref } from "vue";

const props = defineProps({
  hasMiniContent: {
    type: Boolean,
    default: false,
  },
  hasBelowCard: {
    type: Boolean,
    default: false,
  },
});
</script>
