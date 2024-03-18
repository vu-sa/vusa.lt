<template>
  <NButton size="small" @click="showModal = true">
    <template #icon>
      <NIcon :component="Youtube" />
    </template>
  </NButton>
  <CardModal v-model:show="showModal" class="max-w-xl" title="Įkelti Youtube filmuką" @close="showModal = false">
    <div class="flex flex-col gap-2 items-baseline">
      <NFormItem class="w-full" label="Youtube nuoroda" :show-feedback="false">
        <NInput v-model:value="youtubeUrl" />
      </NFormItem>
      <NButton type="primary" @click="addYoutubeVideo">
        Įkelti
      </NButton>
    </div>
  </CardModal>
</template>

<script setup lang="ts">
import { NButton, NFormItem, NIcon, NInput } from "naive-ui";
import { Youtube } from "@vicons/fa";
import { ref } from "vue";

import CardModal from "../Modals/CardModal.vue";

const emit = defineEmits<{
  (e: 'submit', youtubeUrl: string): void
}>()

const showModal = ref(false);
const youtubeUrl = ref("");

function addYoutubeVideo() {
  emit('submit', youtubeUrl.value);
  showModal.value = false;
}
</script>
