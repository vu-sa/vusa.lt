<template>
  <Suspense @fallback="onFallback" @resolve="onResolve">
    <MdGetter v-bind="$attrs" @content-loaded="handleContentLoaded" />
    <template #fallback>
      <div class="text-muted-foreground animate-pulse text-sm">
        Loading...
      </div>
    </template>
  </Suspense>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import MdGetter from "./MdGetter.vue";

const emit = defineEmits<{
  'content-loaded': [boolean]; 
}>();

const isLoading = ref(true);

const onFallback = () => {
  isLoading.value = true;
};

const onResolve = () => {
  isLoading.value = false;
};

const handleContentLoaded = (success: boolean) => {
  isLoading.value = false;
  emit('content-loaded', success);
};
</script>
