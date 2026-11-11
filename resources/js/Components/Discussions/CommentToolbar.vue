<template>
  <div
    class="flex flex-wrap items-center gap-0.5 border-t border-border/70 px-1.5 py-1"
    role="toolbar"
    :aria-label="$t('rich-content.toolbar')"
    data-testid="comment-toolbar"
  >
    <TiptapFormattingButtons :editor :marks="['bold', 'italic']" />
    <TiptapLinkPopover ref="linkPopover" :editor />
    <TiptapListButtons :editor />
    <Popover>
      <PopoverTrigger as-child>
        <TiptapToolButton :label="$t('rich-content.toolbar_more')" data-testid="comment-formatting-more">
          <Ellipsis />
        </TiptapToolButton>
      </PopoverTrigger>
      <PopoverContent :aria-label="$t('rich-content.toolbar_more')" class="w-auto max-w-[calc(100vw-2rem)] p-2" align="end" @open-auto-focus.prevent>
        <div class="flex flex-wrap items-center gap-0.5">
          <TiptapFormattingButtons :editor :marks="['underline', 'strike']" />
          <TiptapToolButton
            v-if="editor.can().sinkListItem('listItem')"
            :label="$t('discussions.formatting.indent')"
            @click="editor.chain().focus().sinkListItem('listItem').run()"
          >
            <IndentIncrease />
          </TiptapToolButton>
          <TiptapToolButton
            v-if="editor.can().liftListItem('listItem')"
            :label="$t('discussions.formatting.outdent')"
            @click="editor.chain().focus().liftListItem('listItem').run()"
          >
            <IndentDecrease />
          </TiptapToolButton>
          <TiptapToolButton :label="$t('rich-content.undo')" :disabled="!editor.can().undo()" @click="editor.chain().focus().undo().run()">
            <Undo2 />
          </TiptapToolButton>
          <TiptapToolButton :label="$t('rich-content.redo')" :disabled="!editor.can().redo()" @click="editor.chain().focus().redo().run()">
            <Redo2 />
          </TiptapToolButton>
          <TiptapToolButton :label="$t('rich-content.clear_formatting')" @click="commands.clearFormatting()">
            <RemoveFormatting />
          </TiptapToolButton>
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { useTemplateRef } from 'vue';
import type { Editor } from '@tiptap/core';
import { Ellipsis, IndentDecrease, IndentIncrease, Redo2, RemoveFormatting, Undo2 } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import TiptapFormattingButtons from '@/Components/TipTap/TiptapFormattingButtons.vue';
import TiptapLinkPopover from '@/Components/TipTap/TiptapLinkPopover.vue';
import TiptapListButtons from '@/Components/TipTap/TiptapListButtons.vue';
import TiptapToolButton from '@/Components/TipTap/TiptapToolButton.vue';
import { useTiptapCommands } from '@/Components/TipTap/composables/useTiptapCommands';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

const props = defineProps<{ editor: Editor }>();
const commands = useTiptapCommands(() => props.editor);
const linkPopover = useTemplateRef<InstanceType<typeof TiptapLinkPopover>>('linkPopover');

defineExpose({ openLink: () => linkPopover.value?.open() });
</script>
