<template>
  <div v-for="n in 4" id="#skeleton"><CardSkeleton></CardSkeleton></div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import CardSkeleton from "@/Components/Public/CardSkeleton.vue";

// let options = {
//   root: "app"
//   rootMargin: "0px",
//   threshold: 1.0,
// };

// let callback = (entries, observer) => {
//   entries.forEach((entry) => {
//     // Each entry describes an intersection change for one observed
//     // target element:
//     //   entry.boundingClientRect
//     //   entry.intersectionRatio
//     //   entry.intersectionRect
//     //   entry.isIntersecting
//     //   entry.rootBounds
//     //   entry.target
//     //   entry.time
//   });
// };

// let observer = new IntersectionObserver(callback, options);

// onMounted(() => {
//   console.log([options, callback, observer]);
//   let skeleton = document.querySelector("#skeleton");
//   observer.observe(skeleton);
// });
</script>
