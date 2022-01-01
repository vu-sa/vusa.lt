<template>
  <Link
    :href="route(menuButtonRoute)"
    class="block p-3 hover:bg-gray-100 last:hover:rounded-b-xl"
    :class="
      route().current(menuButtonRoute)
        ? ['stroke-red-800', 'text-red-800', 'hover:text-red-900']
        : [
            'stroke-gray-600',
            'hover:stroke-gray-800',
            'text-gray-600',
            'hover:text-gray-800',
          ]
    "
  >
    <slot name="icon"></slot>
    <div class="w-full"><slot></slot></div>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/inertia-vue3'
import { computed } from 'vue'

const props = defineProps(['menuButtonRoute'])

</script>