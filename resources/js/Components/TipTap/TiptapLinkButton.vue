<template>
  <!-- The slotted trigger is the real button; this only catches its click. -->
  <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/no-static-element-interactions -->
  <div v-if="$slots.default" @click="open">
    <slot />
  </div>

  <Dialog :open="showModal" @update:open="showModal = $event">
    <DialogContent class="sm:max-w-4xl max-h-[90vh] !p-0 flex flex-col">
      <div class="px-8 pt-8">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.link_dialog_title') }}</DialogTitle>
          <DialogDescription>
            {{ $t('rich-content.link_dialog_description') }}
          </DialogDescription>
        </DialogHeader>
      </div>

      <Tabs default-value="url" class="mt-4 px-8">
        <TabsList class="grid w-full grid-cols-3">
          <TabsTrigger value="url">
            {{ $t('rich-content.link_tab_url') }}
          </TabsTrigger>
          <TabsTrigger value="file">
            {{ $t('rich-content.link_tab_file') }}
          </TabsTrigger>
          <TabsTrigger value="archiveDocument">
            {{ $t('rich-content.link_tab_document') }}
          </TabsTrigger>
        </TabsList>

        <TabsContent value="url" class="space-y-4 pt-4">
          <div class="space-y-4">
            <div class="space-y-2">
              <Label for="url-input">{{ $t('rich-content.link_url') }}</Label>
              <Input
                id="url-input"
                v-model="urlRef"
                placeholder="https://..."
                type="url"
                @keydown.enter.prevent="addLink"
              />
            </div>
            <div class="space-y-2">
              <Label for="link-text-input">{{ $t('rich-content.link_text') }}</Label>
              <Input
                id="link-text-input"
                v-model="linkTextRef"
                :placeholder="urlRef || $t('rich-content.link_text')"
              />
              <p class="text-xs text-muted-foreground">
                {{ hasSelectedText ? $t('rich-content.link_text_editing') : $t('rich-content.link_text_fallback_url') }}
              </p>
            </div>
          </div>
        </TabsContent>

        <TabsContent value="file" class="pt-4 max-h-[60vh] overflow-y-auto pr-1 space-y-4">
          <div class="space-y-2">
            <Label for="file-link-text">{{ $t('rich-content.link_text') }}</Label>
            <Input
              id="file-link-text"
              v-model="linkTextRef"
              :placeholder="$t('rich-content.link_text')"
            />
            <p class="text-xs text-muted-foreground">
              {{ hasSelectedText ? $t('rich-content.link_text_editing') : $t('rich-content.link_text_fallback_file') }}
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
            <Label for="archive-link-text">{{ $t('rich-content.link_text') }}</Label>
            <Input
              id="archive-link-text"
              v-model="linkTextRef"
              :placeholder="$t('rich-content.link_text')"
            />
            <p class="text-xs text-muted-foreground">
              {{ hasSelectedText ? $t('rich-content.link_text_editing') : $t('rich-content.link_text_fallback_document') }}
            </p>
          </div>
          <Suspense>
            <InlineCollectionSelect
              v-if="showModal"
              collection="documents"
              :confirm-label="$t('Pridėti')"
              :empty-message="$t('Dokumentų nerasta')"
              :search-placeholder="$t('Ieškoti dokumento...')"
              @confirm="onDocumentConfirm"
            />
            <template #fallback>
              <div class="flex h-32 items-center justify-center">
                <Spinner size="sm" />
              </div>
            </template>
          </Suspense>
        </TabsContent>
      </Tabs>

      <DialogFooter class="px-8 pb-6">
        <Button type="button" variant="outline" @click="showModal = false">
          {{ $t('Atšaukti') }}
        </Button>
        <Button type="button" :disabled="!urlRef.trim()" @click="addLink">
          {{ $t('rich-content.link_apply') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Editor } from '@tiptap/core';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import FileSelector from '@/Features/Admin/FileManager/FileSelector.vue';
import { InlineCollectionSelect } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { Spinner } from '@/Components/ui/spinner';

const props = defineProps<{
  editor?: Editor | null;
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

function open() {
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
    // Collapsed cursor inside a link — extend the selection to the link mark so
    // we can read its text. This also leaves the link text selected, which makes
    // the submit path treat it as an edit (extendMarkRange('link').setLink(...))
    // rather than inserting a duplicate <a> node.
    props.editor?.chain().focus().extendMarkRange('link').run();
    const sel = props.editor?.state.selection;
    linkTextRef.value = sel ? (props.editor?.state.doc.textBetween(sel.from, sel.to) || '') : '';
  }
  else {
    // No selection, clear text
    linkTextRef.value = '';
  }

  showModal.value = true;
}

function addLink() {
  const url = urlRef.value.trim();
  if (url) {
    emit('submit', url, linkTextRef.value.trim() || url);
  }

  showModal.value = false;
}

// Lets a link bubble or a keyboard shortcut open the dialog without a slotted trigger.
defineExpose({ open });

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

function onDocumentConfirm(hits: NormalizedSearchHit[]) {
  const hit = hits[0];
  // The `documents` mapper normalizes the document's `anonymous_url` into
  // `href`, and `title` is the document title — used as the default link text.
  const url = hit?.href;
  if (!url) return;

  const linkText = linkTextRef.value.trim() || hit.title || url;
  emit('document:submit', url, linkText);
  showModal.value = false;
}
</script>
