<template>
  <!-- Over the page and the top bar (z-40); dialogs (z-50) stay above. Named for view transitions, or page visits
       would lift the named chrome over it. Faded by hand, not with <Transition>: a leaving component is unmounted
       at once, and the flakes would stop mid-fade. -->
  <div
    v-if="shown"
    :class="[
      'pointer-events-none fixed inset-0 z-45 transition-opacity duration-[2500ms] ease-in [view-transition-name:season-snow]',
      gusting ? 'opacity-100' : 'opacity-0',
    ]"
    aria-hidden="true"
    data-slot="season-snow-gust"
    @transitionend.self="shown = gusting"
  >
    <SnowFall :intensity="3" :wind="5" from-top />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

import { useSeasonSnow } from '../useSeasonSnow';
import SnowFall from '../weather/SnowFall.vue';

const { gusting } = useSeasonSnow();

/** Outlives `gusting` by the fade, so the flakes keep falling while they disappear. */
const shown = ref(gusting.value);
watch(gusting, (snowing) => {
  if (snowing) {
    shown.value = true;
  }
});
</script>
