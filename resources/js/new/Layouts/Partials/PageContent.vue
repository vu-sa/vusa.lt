<template>
  <div
    class="col-span-8 mt-6 mx-8 grid grid-flow-row auto-rows-max grid-cols-6"
  >
    <header class="mt-4 mb-5 col-span-4 flex flex-row items-center">
      <h1
        class="
          font-black
          text-3xl text-gray-800
          leading-tight
          inline-block
          mr-2
        "
      >
        <slot name="header"></slot>
      </h1>
      <Link v-if="isIndex" class="mr-auto"><PlusCircleIcon class="w-6 h-6 hover:stroke-red-900 duration-200" /></Link>
      <aside class="inline-block font-bold ml-2 duration-200">
        <slot name="aside-header"></slot>
      </aside>
    </header>
    <slot></slot>
  </div>
</template>

<script setup>
import { PlusCircleIcon } from '@heroicons/vue/outline'
import { Link } from '@inertiajs/inertia-vue3'
import { computed } from 'vue'

const isIndex = computed(() => {
  return route().current('*.index')
})
</script>