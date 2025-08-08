<template>
  <NPopover :show-arrow="false" raw :show="showPopover" :x="coordinates.x" :y="coordinates.y" trigger="manual">
    <NButton :type="showPopover ? 'error' : 'default'" @click="handleFeedbackClick">
      <template #icon>
        <IFluentPersonFeedback24Filled />
      </template>
    </NButton>
  </NPopover>
  <CardModal :show="showModal" title="Pranešk apie klaidą!" @close="handleModalClose">
    <template #footer>
      <NButton type="primary" :loading @click="handleSend">
        {{ $t("Siųsti") }}
        <template #icon>
          <IFluentSend24Filled />
        </template>
      </NButton>
    </template>
    <div>
      <p class="mb-4 text-xs opacity-80">
        {{ textInQuestion }}
      </p>
      <NInput v-model:value="feedback" type="textarea" rows="4" placeholder="Jūsų atsiliepimas, pastaba..." />
    </div>
  </CardModal>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useToasts } from '@/Composables/useToasts';
import { useMousePressed, useTextSelection } from "@vueuse/core";

import CardModal from "../Modals/CardModal.vue";

const showPopover = ref(false);
const showModal = ref(false);
const loading = ref(false);

const selectionState = useTextSelection();
const mousePressed = useMousePressed();

const textInQuestion = ref("");
const feedback = ref("");

const toasts = useToasts();

watch(
  mousePressed.pressed,
  (value) => {
    if (selectionState.text.value !== "" && value === false) {
      // Sometimes the feedback button jumps to the top left corner, so we need to check if it's not there
      if (selectionState.rects.value[0].x !== 0 && selectionState.rects.value[0].y !== 0 && showModal.value === false) {
        coordinates.value = {
          x: selectionState.rects.value[0].x + selectionState.rects.value[0].width / 2
          , y: selectionState.rects.value[0].y
        };
        showPopover.value = true;
        // Dynamically add transition animation, so it doesn't animate on initial show (dropdown from top)
        document.querySelector(".v-binder-follower-content")?.classList.add("transition-transform", "duration-500");
      }
    } else if (selectionState.text.value === "") {
      document.querySelector(".v-binder-follower-content")?.classList.remove("transition-transform", "duration-500");
      showPopover.value = false;
    }
  }
);

function handleFeedbackClick() {
  textInQuestion.value = selectionState.text.value;

  showModal.value = true;
  showPopover.value = false;
}

function handleModalClose() {
  showModal.value = false;

  setTimeout(() => {
    textInQuestion.value = "";
  }, 300);
}

function handleSend() {
  loading.value = true;

  router.post(route("feedback.send"), {
    selectedText: textInQuestion.value,
    feedback: feedback.value,
    href: window.location.href
  }, {
    preserveScroll: true,
    onSuccess: () => {
      loading.value = false;
      showModal.value = false;
      feedback.value = "";
      // Flash message will be handled automatically by the layout's useToasts
    }
  });
}

const coordinates = ref({ x: window.innerWidth / 2, y: window.innerHeight / 2 });
</script>
