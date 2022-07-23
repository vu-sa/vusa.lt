<template>
  <div class="flex h-auto min-h-fit flex-col rounded-lg bg-white lg:flex-row">
    <div
      v-if="imageSrc"
      class="relative h-60 w-auto flex-none lg:h-auto lg:w-40"
    >
      <img
        :src="imageSrc"
        class="absolute inset-0 h-full w-full rounded-t-lg object-cover lg:rounded-t-none lg:rounded-l-lg"
        style="object-position: 50% 25%"
      />
    </div>
    <div class="flex flex-auto flex-col justify-between gap-4 p-4">
      <div class="flex flex-col flex-wrap">
        <h2 class="flex-auto px-2 text-slate-900">
          <slot name="name"></slot>
        </h2>
        <div class="w-fit p-2 text-sm font-medium text-gray-500">
          <slot name="duty"></slot>
        </div>
      </div>
      <div class="flex flex-col gap-2 text-sm text-neutral-500">
        <slot name="contactInfo"></slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  imageSrc: string | null;
}>();
</script>
