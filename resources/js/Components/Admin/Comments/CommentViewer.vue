<template>
  <TransitionGroup name="list">
    <div v-for="comment in comments" :key="comment.id">
      <SingleComment :comment="comment" />
    </div>
  </TransitionGroup>
</template>

<script setup lang="ts">
import { computed } from "vue";
import SingleComment from "./SingleComment.vue";

const props = defineProps<{
  comments: Record<string, any>[];
}>();

const comments = computed(() => props.comments);
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 1s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(30px);
}
</style>
