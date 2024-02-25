<template>
  <NButton size="small" @click="showModal = true">
    <template #icon>
      <NIcon :component="Link20Regular" />
    </template>
  </NButton>
  <CardModal v-model:show="showModal" class="max-w-xl" title="Įkelti nuorodą" @close="showModal = false">
    <div class="flex flex-col items-baseline gap-2">
      <NFormItem class="w-full" label="Nuoroda" :show-feedback="false">
        <NInput v-model:value="url" placeholder="https://..." />
      </NFormItem>
      <NButton type="primary" @click="addLink">
        Įkelti
      </NButton>
    </div>
  </CardModal>
</template>

<script setup lang="ts">
import { Link20Regular } from "@vicons/fluent";
import { NButton, NFormItem, NIcon, NInput } from "naive-ui";
import { ref } from "vue";

import CardModal from "../Modals/CardModal.vue";

const emit = defineEmits<{
  (e: 'submit', youtubeUrl: string): void
}>()

const showModal = ref(false);
const url = ref("");

function addLink() {
  emit('submit', url.value);
  showModal.value = false;
}
</script>
