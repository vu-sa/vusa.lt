<template>
  <audio ref="startFM">
    <source src="https://eteris.startfm.lt/startfm.mp3" />
  </audio>
  <NButton text @click="toggleAudio">
    <template v-if="!audioPlaying" #icon
      ><NIcon :component="MusicNote2Play20Filled"></NIcon
    ></template>
    <template v-else #icon
      ><NIcon :component="Pause24Regular"></NIcon
    ></template>
  </NButton>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { MusicNote2Play20Filled, Pause24Regular } from "@vicons/fluent";
import { NButton, NIcon } from "naive-ui";
import { onBeforeUnmount, onMounted, ref } from "vue";

const startFM = ref<HTMLAudioElement>();
const audioPlaying = ref(false);

const toggleAudio = () => {
  if (startFM.value?.paused) {
    startFM.value?.play();
    audioPlaying.value = true;
  } else {
    startFM.value?.pause();
    audioPlaying.value = false;
  }
};

// onBeforeUnmount(() => {
//   Inertia.remember(audioPlaying.value, "StartFM");
// });

// onMounted(() => {
//   if (Inertia.restore("StartFM")) {
//     audioPlaying.value = true;
//   }
// });
</script>
