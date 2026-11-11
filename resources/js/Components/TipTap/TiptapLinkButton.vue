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

      <Tabs v-model="tab" class="mt-4 px-8">
        <TabsList :class="['grid w-full', targets ? 'grid-cols-4' : 'grid-cols-3']">
          <TabsTrigger v-if="targets" value="wiki" data-testid="link-tab-wiki">
            {{ $t('rich-content.link_tab_wiki') }}
          </TabsTrigger>
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

        <TabsContent v-if="targets" value="wiki" class="space-y-4 pt-4" data-testid="link-wiki-panel">
          <div class="space-y-2">
            <Label for="wiki-link-text">{{ $t('rich-content.link_text') }}</Label>
            <Input id="wiki-link-text" v-model="linkTextRef" :placeholder="target?.title || $t('rich-content.link_text')" data-testid="wiki-link-text" />
            <p class="text-xs text-muted-foreground">{{ $t('rich-content.link_wiki_text_hint') }}</p>
          </div>
          <div class="space-y-2">
            <Label for="wiki-link-search">{{ $t('rich-content.link_wiki_target') }}</Label>
            <Input id="wiki-link-search" v-model="wikiQuery" type="search" :placeholder="$t('rich-content.link_wiki_search')" data-testid="wiki-link-search" />
            <ul class="max-h-64 divide-y divide-border overflow-y-auto border border-border" role="listbox" :aria-label="$t('rich-content.link_wiki_target')">
              <li v-for="option in wikiOptions" :key="option.id">
                <button
                  type="button"
                  role="option"
                  :aria-selected="option.id === target?.id"
                  :class="['flex min-h-11 w-full items-baseline gap-2 px-3 py-2 text-left text-sm hover:bg-accent', option.id === target?.id && 'bg-accent font-semibold']"
                  :data-testid="`wiki-link-option-${option.id}`"
                  @click="target = option"
                >
                  <span class="min-w-0 flex-1 truncate">{{ option.title }}</span>
                  <span v-if="option.hint" class="shrink-0 text-xs font-bold tracking-wide text-muted-foreground">{{ option.hint }}</span>
                </button>
              </li>
              <li v-if="!wikiOptions.length" class="px-3 py-2 text-sm text-muted-foreground">{{ $t('rich-content.link_wiki_empty') }}</li>
            </ul>
          </div>
        </TabsContent>

        <TabsContent value="url" class="space-y-4 pt-4">
          <TiptapLinkForm v-model:url="urlRef" v-model:text="linkTextRef" :has-selected-text @submit="addLink" @cancel="showModal = false" />
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
        <Button type="button" :disabled="tab === 'wiki' ? !target : !isValidTiptapLink(urlRef)" data-testid="link-apply" @click="tab === 'wiki' ? addWikiLink() : addLink()">
          {{ $t('rich-content.link_apply') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, shallowRef } from 'vue';
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

import { linkTargetProvider, type LinkTarget } from './linkTargets';
import { isValidTiptapLink } from './linkUrl';
import TiptapLinkForm from './TiptapLinkForm.vue';

const props = defineProps<{
  editor?: Editor | null;
}>();

export interface LinkSubmitOptions {
  /** The mark attributes beyond `href`: a wiki link's article id, or `null` to drop it. */
  attrs: { knowledgeId: string | null };
  /** True when the text differs from the link's current text, so the dialog may rename it. */
  replaceSelectionText: boolean;
}

const emit = defineEmits<{
  (e: 'submit', url: string, text: string | undefined, options: LinkSubmitOptions): void;
  (e: 'document:submit', url: string, text: string | undefined, options: LinkSubmitOptions): void;
}>();

const showModal = ref(false);
const urlRef = ref<string>('');
const linkTextRef = ref<string>('');
const originalText = ref('');
const tab = ref<string>('url');

const targets = computed(() => linkTargetProvider(props.editor));
const target = shallowRef<LinkTarget | null>(null);
const wikiQuery = ref('');
const loadedTargets = shallowRef<LinkTarget[]>([]);
const wikiOptions = computed(() => (wikiQuery.value.trim() ? targets.value?.search(wikiQuery.value) ?? [] : loadedTargets.value).slice(0, 30));

const submitOptions = (knowledgeId: string | null): LinkSubmitOptions => ({
  attrs: { knowledgeId },
  replaceSelectionText: linkTextRef.value.trim() !== '' && linkTextRef.value !== originalText.value,
});

const hasSelectedText = computed(() => {
  if (!props.editor) return false;
  const { from, to } = props.editor.state.selection;
  return from !== to;
});

function open() {
  const linkAttrs = props.editor?.getAttributes?.('link');
  urlRef.value = linkAttrs?.href ?? '';

  const { from, to } = props.editor?.state.selection || { from: 0, to: 0 };
  if (from !== to) {
    linkTextRef.value = props.editor?.state.doc.textBetween(from, to) || '';
  }
  else if (linkAttrs?.href) {
    // Extend a collapsed cursor so applying a URL edits rather than duplicates the link.
    props.editor?.chain().focus().extendMarkRange('link').run();
    const sel = props.editor?.state.selection;
    linkTextRef.value = sel ? (props.editor?.state.doc.textBetween(sel.from, sel.to) || '') : '';
  }
  else {
    linkTextRef.value = '';
  }
  originalText.value = linkTextRef.value;

  const knowledgeId = (linkAttrs?.knowledgeId as string | null | undefined) ?? null;
  tab.value = knowledgeId && targets.value ? 'wiki' : 'url';
  target.value = null;
  wikiQuery.value = '';
  showModal.value = true;
  void targets.value?.load().then((all) => {
    loadedTargets.value = all;
    target.value = knowledgeId ? all.find(option => option.id === knowledgeId) ?? null : null;
  });
}

function addLink() {
  const url = urlRef.value.trim();
  if (isValidTiptapLink(url)) {
    emit('submit', url, linkTextRef.value.trim() || url, submitOptions(null));
  }

  showModal.value = false;
}

function addWikiLink() {
  if (!target.value) return;
  emit('submit', target.value.href, linkTextRef.value.trim() || target.value.title, submitOptions(target.value.id));
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
  emit('submit', finalUrl, linkText, submitOptions(null));
  showModal.value = false;
}

function onDocumentConfirm(hits: NormalizedSearchHit[]) {
  const hit = hits[0];
  // The `documents` mapper normalizes the document's `anonymous_url` into
  // `href`, and `title` is the document title — used as the default link text.
  const url = hit?.href;
  if (!url) return;

  const linkText = linkTextRef.value.trim() || hit.title || url;
  emit('document:submit', url, linkText, submitOptions(null));
  showModal.value = false;
}
</script>
