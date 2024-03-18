<template>
  <NButton size="small" @click="handleOpenModal">
    <template #icon>
      <NIcon :component="Link20Regular" />
    </template>
  </NButton>
  <CardModal v-model:show="showModal" class="max-w-3xl" title="Įkelti nuorodą" @close="showModal = false">
    <NTabs>
      <NTabPane name="Įrašyti" title="Nuoroda">
        <div class="flex flex-col items-baseline gap-2">
          <NFormItem class="w-full" label="Nuoroda" :show-feedback="false">
            <NInput v-model:value="url" placeholder="https://..." />
          </NFormItem>
          <NButton type="primary" @click="addLink">
            Įkelti
          </NButton>
        </div>
      </NTabPane>
      <NTabPane name="Pasirinkti iš failų" title="Failas">
        <Suspense>
          <FileSelector v-if="showModal" @submit="addLink" />
          <div v-else class="h-32" />
          <template #fallback>
            <div class="flex h-32 items-center justify-center">
              <NSpin />
            </div>
          </template>
        </Suspense>
      </NTabPane>
    </NTabs>
  </CardModal>
</template>

<script setup lang="ts">
import { Link20Regular } from "@vicons/fluent";
import { NButton, NFormItem, NIcon, NInput, NSpin, NTabPane, NTabs } from "naive-ui";
import { ref } from "vue";

import CardModal from "../Modals/CardModal.vue";
import FileSelector from "@/Features/Admin/FileManager/FileSelector.vue";

const props = defineProps<{
  editor?: any;
}>();

const emit = defineEmits<{
  (e: 'submit', youtubeUrl: string): void
}>()

const showModal = ref(false);
const url = ref("");

function handleOpenModal() {
  url.value = props.editor?.getAttributes('link').href
  showModal.value = true;
}

function addLink(file: string | PointerEvent) {

  if (typeof file === 'string') {
    url.value = file;
  }

  if (url.value.startsWith("public")) {
    url.value = "/uploads/" + url.value.substring(url.value.indexOf("/") + 1);
  }

  emit('submit', url.value);
  showModal.value = false;
}
</script>
