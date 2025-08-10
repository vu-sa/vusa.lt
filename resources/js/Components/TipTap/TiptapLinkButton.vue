<template>
  <NButton size="small" @click="handleOpenModal">
    <template #icon>
      <IFluentLink20Regular />
    </template>
  </NButton>
  
  <Dialog :open="showModal" @update:open="showModal = $event">
  <DialogContent class="sm:max-w-4xl max-h-[90vh] !p-0 flex flex-col">
      <div class="px-8 pt-8">
        <DialogHeader>
          <DialogTitle>Įkelti nuorodą</DialogTitle>
          <DialogDescription>
            Pasirinkite nuorodos tipą ir nustatykite jos paskirtį.
          </DialogDescription>
        </DialogHeader>
      </div>

      <NTabs type="line" class="mt-4 px-8">
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
          <div class="pt-4 max-h-[60vh] overflow-y-auto pr-1">
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
          <div class="pt-4 max-h-[60vh] overflow-y-auto pr-1">
            <Suspense>
              <ArchiveDocumentSelector v-if="showModal" @submit="addArchiveDocumentLink" />
            </Suspense>
          </div>
        </NTabPane>
      </NTabs>

      <DialogFooter class="px-8 pb-6">
        <Button variant="outline" @click="showModal = false">
          Atšaukti
        </Button>
  <Button @click="addLink" :disabled="!(urlRef && urlRef.trim && urlRef.trim())">
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
// keep urlRef always a string to simplify template checks
const urlRef = ref<string>("");

function handleOpenModal() {
  // Optional chain on editor and attributes; default to empty string
  urlRef.value = props.editor?.getAttributes?.('link')?.href ?? "";
  showModal.value = true;
}

function addLink() {

  if (typeof urlRef.value === 'string') {
    emit('submit', urlRef.value);
  }

  showModal.value = false;
}

function addFileLink(file: string) {
  let finalUrl = file;
  if (typeof file === 'string' && file.startsWith("public/")) {
    // Map storage path to public uploads URL
    const firstSlash = file.indexOf("/");
    finalUrl = "/uploads/" + file.substring(firstSlash + 1);
  }
  emit('submit', finalUrl);
  showModal.value = false;
}

function addArchiveDocumentLink(url: string) {
  emit('document:submit', url);
  showModal.value = false;
}
</script>
