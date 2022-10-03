<template>
  <p v-if="comments.length === 0">Komentarų nėra</p>
  <TransitionGroup name="list">
    <template v-for="comment in comments" :key="comment.id">
      <SingleComment :comment="comment" />
    </template>
  </TransitionGroup>
</template>

<script setup lang="ts">
import { ref } from "vue";
import SingleComment from "./SingleComment.vue";

const props = defineProps<{
  comments: Record<string, any>[];
}>();

const comments = ref(props.comments);
</script>

<style>
.list-move, /* apply transition to moving elements */
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* ensure leaving items are taken out of layout flow so that moving
   animations can be calculated correctly. */
.list-leave-active {
  position: absolute;
}
</style>
