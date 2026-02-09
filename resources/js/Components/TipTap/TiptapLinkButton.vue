<template>
  <div @click="handleOpenModal">
    <slot />
  </div>

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

      <Tabs default-value="url" class="mt-4 px-8">
        <TabsList class="grid w-full grid-cols-3">
          <TabsTrigger value="url">
            Paprasta nuoroda
          </TabsTrigger>
          <TabsTrigger value="file">
            Failas iš vusa.lt failų
          </TabsTrigger>
          <TabsTrigger value="archiveDocument">
            Archyvo dokumentas
          </TabsTrigger>
        </TabsList>

        <TabsContent value="url" class="space-y-4 pt-4">
          <div class="space-y-4">
            <div class="space-y-2">
              <Label for="url-input">Nuoroda</Label>
              <Input
                id="url-input"
                v-model="urlRef"
                placeholder="https://..."
                type="url"
              />
            </div>
            <div class="space-y-2">
              <Label for="link-text-input">Nuorodos tekstas</Label>
              <Input
                id="link-text-input"
                v-model="linkTextRef"
                :placeholder="urlRef || 'Nuorodos tekstas...'"
              />
              <p class="text-xs text-muted-foreground">
                {{ hasSelectedText ? 'Redaguojama esamo teksto nuoroda' : 'Jei paliksite tuščią, bus naudojamas URL' }}
              </p>
            </div>
          </div>
        </TabsContent>

        <TabsContent value="file" class="pt-4 max-h-[60vh] overflow-y-auto pr-1 space-y-4">
          <div class="space-y-2">
            <Label for="file-link-text">Nuorodos tekstas</Label>
            <Input
              id="file-link-text"
              v-model="linkTextRef"
              placeholder="Nuorodos tekstas..."
            />
            <p class="text-xs text-muted-foreground">
              {{ hasSelectedText ? 'Redaguojama esamo teksto nuoroda' : 'Jei paliksite tuščią, bus naudojamas failo pavadinimas' }}
            </p>
          </div>
          <Suspense>
            <FileSelector v-if="showModal" @submit="addFileLink" />
            <template #fallback>
              <div class="flex h-32 items-center justify-center">
                <Spinner size="sm" />
              </div>
            </template>
          </Suspense>
        </TabsContent>

        <TabsContent value="archiveDocument" class="pt-4 max-h-[60vh] overflow-y-auto pr-1 space-y-4">
          <div class="space-y-2">
            <Label for="archive-link-text">Nuorodos tekstas</Label>
            <Input
              id="archive-link-text"
              v-model="linkTextRef"
              placeholder="Nuorodos tekstas..."
            />
            <p class="text-xs text-muted-foreground">
              {{ hasSelectedText ? 'Redaguojama esamo teksto nuoroda' : 'Jei paliksite tuščią, bus naudojamas dokumento pavadinimas' }}
            </p>
          </div>
          <Suspense>
            <ArchiveDocumentSelector v-if="showModal" @submit="addArchiveDocumentLink" />
          </Suspense>
        </TabsContent>
      </Tabs>

      <DialogFooter class="px-8 pb-6">
        <Button variant="outline" @click="showModal = false">
          Atšaukti
        </Button>
        <Button :disabled="!(urlRef && urlRef.trim && urlRef.trim())" @click="addLink">
          Įkelti
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import FileSelector from '@/Features/Admin/FileManager/FileSelector.vue';
import ArchiveDocumentSelector from '@/Features/Admin/ArchiveDocumentSelector.vue';
import { Spinner } from '@/Components/ui/spinner';

const props = defineProps<{
  editor?: any;
}>();

const emit = defineEmits<{
  (e: 'submit', url: string, text?: string): void;
  (e: 'document:submit', url: string, text?: string): void;
}>();

const showModal = ref(false);
// keep urlRef always a string to simplify template checks
const urlRef = ref<string>('');
const linkTextRef = ref<string>('');

const hasSelectedText = computed(() => {
  if (!props.editor) return false;
  const { from, to } = props.editor.state.selection;
  return from !== to;
});

function handleOpenModal() {
  // Optional chain on editor and attributes; default to empty string
  const linkAttrs = props.editor?.getAttributes?.('link');
  urlRef.value = linkAttrs?.href ?? '';

  // Get selected text or current link text
  const { from, to } = props.editor?.state.selection || { from: 0, to: 0 };
  if (from !== to) {
    // Has selected text
    linkTextRef.value = props.editor?.state.doc.textBetween(from, to) || '';
  }
  else if (linkAttrs?.href) {
    // Editing existing link - try to get the link text
    linkTextRef.value = props.editor?.state.doc.textBetween(from, to) || '';
  }
  else {
    // No selection, clear text
    linkTextRef.value = '';
  }

  showModal.value = true;
}

function addLink() {
  if (typeof urlRef.value === 'string') {
    const linkText = linkTextRef.value.trim() || urlRef.value;
    emit('submit', urlRef.value, linkText);
  }

  showModal.value = false;
}

function addFileLink(file: string) {
  let finalUrl = file;
  if (typeof file === 'string' && file.startsWith('public/')) {
    // Map storage path to public uploads URL
    const firstSlash = file.indexOf('/');
    finalUrl = `/uploads/${file.substring(firstSlash + 1)}`;
  }
  const linkText = linkTextRef.value.trim() || finalUrl;
  emit('submit', finalUrl, linkText);
  showModal.value = false;
}

function addArchiveDocumentLink(url: string) {
  const linkText = linkTextRef.value.trim() || url;
  emit('document:submit', url, linkText);
  showModal.value = false;
}
</script>
