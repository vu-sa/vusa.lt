<template>
    <div class="flex flex-col items-center">
        <div class="text-6xl font-bold mb-1 text-transparent bg-clip-text" :class="[colorClass]">
            <NumberCounter :end-number />
        </div>
        <div class="w-60 text-center">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
import NumberCounter from "./RCNumberCounter.vue";

defineProps<{
    endNumber: number;
    colorClass: string
}>();
</script>
