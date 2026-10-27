<template>
  <Popover :open @update:open="setOpen">
    <PopoverTrigger as-child>
      <TiptapToolButton toggle :active="editor.isActive('link')" :label="$t('rich-content.link')" data-testid="tiptap-link" @mousedown.prevent>
        <Link class="size-4" />
      </TiptapToolButton>
    </PopoverTrigger>
    <PopoverContent :aria-label="$t('rich-content.link_dialog_title')" class="w-80 max-w-[calc(100vw-2rem)]" @close-auto-focus.prevent>
      <TiptapLinkForm v-model:url="url" v-model:text="text" :has-selected-text @submit="apply" @cancel="close" />
      <div class="mt-3 flex flex-wrap justify-end gap-2">
        <Button v-if="editor.isActive('link')" type="button" size="sm" variant="ghost" @click="remove">
          {{ $t('rich-content.remove_link') }}
        </Button>
        <Button type="button" size="sm" variant="outline" @click="close">
          {{ $t('Atšaukti') }}
        </Button>
        <Button type="button" size="sm" :disabled="!isValidTiptapLink(url)" @click="apply">
          {{ $t('rich-content.link_apply') }}
        </Button>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { Editor } from '@tiptap/core';
import { Link } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import TiptapLinkForm from './TiptapLinkForm.vue';
import TiptapToolButton from './TiptapToolButton.vue';
import { useTiptapCommands } from './composables/useTiptapCommands';
import { isValidTiptapLink } from './linkUrl';

import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

const props = defineProps<{ editor: Editor }>();
const emit = defineEmits<{ engage: [] }>();
const open = defineModel<boolean>('open', { default: false });
const url = ref('');
const text = ref('');
const hasSelectedText = ref(false);
const originalText = ref('');
const commands = useTiptapCommands(() => props.editor);

function setOpen(value: boolean) {
  if (value) {
    const { editor } = props;
    url.value = editor.getAttributes('link').href ?? '';
    if (editor.isActive('link')) editor.chain().focus().extendMarkRange('link').run();
    const { from, to } = editor.state.selection;
    hasSelectedText.value = from !== to;
    text.value = editor.state.doc.textBetween(from, to);
    originalText.value = text.value;
    emit('engage');
  }
  open.value = value;
}

function close() {
  open.value = false;
  props.editor.commands.focus();
}

function apply() {
  if (!isValidTiptapLink(url.value)) return;
  commands.insertLink(url.value.trim(), text.value, { replaceSelectionText: text.value !== originalText.value });
  close();
}

function remove() {
  commands.unsetLink();
  close();
}

defineExpose({ open: () => setOpen(true) });
</script>
