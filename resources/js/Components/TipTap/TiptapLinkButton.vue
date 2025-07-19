<template>
  <NButton size="small" @click="handleOpenModal">
    <template #icon>
      <IFluentLink20Regular />
    </template>
  </NButton>
  
  <Dialog :open="showModal" @update:open="showModal = $event">
    <DialogContent class="sm:max-w-3xl">
      <DialogHeader>
        <DialogTitle>Įkelti nuorodą</DialogTitle>
        <DialogDescription>
          Pasirinkite nuorodos tipą ir nustatykite jos paskirtį.
        </DialogDescription>
      </DialogHeader>

      <NTabs type="line" class="mt-4">
        <NTabPane name="url" tab="Paprasta nuoroda">
          <div class="space-y-4 pt-4">
            <div class="space-y-2">
              <Label for="url-input">Nuoroda</Label>
              <Input 
                id="url-input"
                v-model="urlRef" 
                placeholder="https://..." 
                type="url"
              />
            </div>
          </div>
        </NTabPane>
        <NTabPane name="file" tab="Failas iš vusa.lt failų">
          <div class="pt-4">
            <Suspense>
              <FileSelector v-if="showModal" @submit="addFileLink" />
              <template #fallback>
                <div class="flex h-32 items-center justify-center">
                  <Spinner size="sm" />
                </div>
              </template>
            </Suspense>
          </div>
        </NTabPane>
        <NTabPane name="archiveDocument" tab="Archyvo dokumentas">
          <div class="pt-4">
            <Suspense>
              <ArchiveDocumentSelector v-if="showModal" @submit="addArchiveDocumentLink" />
            </Suspense>
          </div>
        </NTabPane>
      </NTabs>

      <DialogFooter>
        <Button variant="outline" @click="showModal = false">
          Atšaukti
        </Button>
        <Button @click="addLink" :disabled="!urlRef.trim()">
          Įkelti
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref } from "vue";

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import FileSelector from "@/Features/Admin/FileManager/FileSelector.vue";
import ArchiveDocumentSelector from "@/Features/Admin/ArchiveDocumentSelector.vue";
import { Spinner } from "@/Components/ui/spinner";

const props = defineProps<{
  editor?: any;
}>();

const emit = defineEmits<{
  (e: 'submit', url: string): void
  (e: 'document:submit', url: string): void
}>()

const showModal = ref(false);
const urlRef = ref("");

function handleOpenModal() {
  urlRef.value = props.editor?.getAttributes('link').href
  showModal.value = true;
}

function addLink() {

  if (typeof urlRef.value === 'string') {
    emit('submit', urlRef.value);
  }

  showModal.value = false;
}

function addFileLink(file: string) {
  if (file.startsWith("public")) {
    emit('submit', "/uploads/" + file.substring(file.indexOf("/") + 1));
  }
  emit('submit', file);
  showModal.value = false;
}

function addArchiveDocumentLink(url: string) {
  emit('document:submit', url);
  showModal.value = false;
}
</script>
