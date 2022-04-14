<template>
  <article class="relative">
    <div
      class="absolute -top-2 -right-1 bg-white rounded-xl border border-gray-100 px-2 shadow-sm hover:shadow-md duration-200 text-gray-500 line-clamp-1"
    >
      <slot name="mini"></slot>
    </div>
    <slot name="image"></slot>
    <div class="flex items-center mt-2 text-gray-600 text-sm">
      <slot name="below-card"></slot>
    </div>
    <h2 class="text-gray-800 mt-1 line-clamp-2 leading-tight"><slot></slot></h2>
  </article>
</template>

<script setup>
import { NIcon } from "naive-ui";
import { CalendarLtr20Regular } from "@vicons/fluent";
</script>
