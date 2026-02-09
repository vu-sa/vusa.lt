<template>
  <!-- Floating feedback button positioned at text selection -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showPopover"
        class="fixed z-50 -translate-x-1/2 -translate-y-full"
        :style="{ left: `${coordinates.x}px`, top: `${coordinates.y}px` }"
      >
        <Button variant="destructive" size="icon" @click="handleFeedbackClick">
          <IFluentPersonFeedback24Filled />
        </Button>
      </div>
    </Transition>
  </Teleport>

  <Dialog :open="showModal" @update:open="(val) => !val && handleModalClose()">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>Pranešk apie klaidą!</DialogTitle>
      </DialogHeader>
      <div>
        <p class="mb-4 text-xs opacity-80">
          {{ textInQuestion }}
        </p>
        <Textarea v-model="feedback" rows="4" placeholder="Jūsų atsiliepimas, pastaba..." />
      </div>
      <DialogFooter>
        <Button :disabled="loading" @click="handleSend">
          <Spinner v-if="loading" />
          <IFluentSend24Filled v-else />
          {{ $t("Siųsti") }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useMousePressed, useTextSelection } from '@vueuse/core';

import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';

const showPopover = ref(false);
const showModal = ref(false);
const loading = ref(false);

const selectionState = useTextSelection();
const mousePressed = useMousePressed();

const textInQuestion = ref('');
const feedback = ref('');

const coordinates = ref({ x: window.innerWidth / 2, y: window.innerHeight / 2 });

watch(
  mousePressed.pressed,
  (value) => {
    if (selectionState.text.value !== '' && value === false) {
      const rect = selectionState.rects.value?.[0];
      // Sometimes the feedback button jumps to the top left corner, so we need to check if it's not there
      if (rect && rect.x !== 0 && rect.y !== 0 && showModal.value === false) {
        coordinates.value = {
          x: rect.x + rect.width / 2,
          y: rect.y,
        };
        showPopover.value = true;
      }
    }
    else if (selectionState.text.value === '') {
      showPopover.value = false;
    }
  },
);

function handleFeedbackClick() {
  textInQuestion.value = selectionState.text.value;

  showModal.value = true;
  showPopover.value = false;
}

function handleModalClose() {
  showModal.value = false;

  setTimeout(() => {
    textInQuestion.value = '';
  }, 300);
}

function handleSend() {
  loading.value = true;

  router.post(route('feedback.send'), {
    selectedText: textInQuestion.value,
    feedback: feedback.value,
    href: window.location.href,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      loading.value = false;
      showModal.value = false;
      feedback.value = '';
      // Flash message will be handled automatically by the layout's useToasts
    },
  });
}
</script>
