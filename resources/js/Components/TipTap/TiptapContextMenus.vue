<template>
  <!-- Selection: marks and link, next to the text being formatted. -->
  <BubbleMenu
    v-if="textBubble"
    :class="bubbleClass"
    :editor
    plugin-key="textBubbleMenu"
    :should-show="shouldShowTextBubbleMenu"
    :options="{ placement: 'top', offset: 8 }"
    data-testid="tiptap-text-bubble"
    @mousedown.prevent
  >
    <TiptapFormattingButtons :editor :show-bold />
    <template v-if="linksEnabled">
      <Separator orientation="vertical" class="mx-0.5 h-5" />
      <TiptapToolButton toggle :active="editor.isActive('link')" :label="$t('rich-content.link')" @click="openLinkDialog">
        <IFluentLink24Regular />
      </TiptapToolButton>
    </template>
  </BubbleMenu>

  <!-- Cursor in a link: the only place "remove link" is offered, so it is never a dead button. -->
  <BubbleMenu
    v-if="linksEnabled"
    :class="bubbleClass"
    :editor
    plugin-key="linkBubbleMenu"
    :should-show="shouldShowLinkBubbleMenu"
    :options="{ placement: 'bottom', offset: 8 }"
    data-testid="tiptap-link-bubble"
    @mousedown.prevent
  >
    <a
      :href="currentHref"
      target="_blank"
      rel="noopener noreferrer"
      class="flex max-w-56 items-center gap-1 truncate px-2 text-xs text-brand underline-offset-2 hover:underline"
      :title="$t('rich-content.link_open')"
    >
      <span class="truncate">{{ currentHref }}</span>
      <IFluentOpen16Regular class="size-3.5 shrink-0" />
    </a>
    <Separator orientation="vertical" class="mx-0.5 h-5" />
    <TiptapToolButton :label="$t('rich-content.edit_link')" data-testid="tiptap-link-edit" @click="openLinkDialog">
      <IFluentEdit20Regular />
    </TiptapToolButton>
    <TiptapToolButton :label="$t('rich-content.remove_link')" data-testid="tiptap-link-remove" @click="commands.unsetLink()">
      <IFluentLinkDismiss20Regular />
    </TiptapToolButton>
  </BubbleMenu>

  <!-- Cursor in a table: its tools, anchored above the table rather than the caret. -->
  <BubbleMenu
    v-if="editor.schema.nodes.table"
    :class="bubbleClass"
    :editor
    plugin-key="tableBubbleMenu"
    :should-show="shouldShowTableBubbleMenu"
    :get-referenced-virtual-element="tableReference"
    :options="{ placement: 'top-start', offset: 8 }"
    data-testid="tiptap-table-bubble"
    @mousedown.prevent
  >
    <TiptapToolButton :label="$t('rich-content.table_toggle_header')" @click="editor.chain().focus().toggleHeaderRow().run()">
      <IFluentTableFreezeRow24Regular />
    </TiptapToolButton>
    <TiptapToolButton :label="$t('rich-content.table_add_row')" @click="editor.chain().focus().addRowAfter().run()">
      <IFluentTableInsertRow24Regular />
    </TiptapToolButton>
    <TiptapToolButton :label="$t('rich-content.table_add_column')" @click="editor.chain().focus().addColumnAfter().run()">
      <IFluentTableInsertColumn24Regular />
    </TiptapToolButton>
    <TiptapToolButton v-if="editor.can().mergeCells()" :label="$t('rich-content.table_merge_cells')" @click="editor.chain().focus().mergeCells().run()">
      <IFluentTableCellsMerge24Regular />
    </TiptapToolButton>
    <TiptapToolButton v-if="editor.can().splitCell()" :label="$t('rich-content.table_split_cell')" @click="editor.chain().focus().splitCell().run()">
      <IFluentTableCellsSplit24Regular />
    </TiptapToolButton>
    <Separator orientation="vertical" class="mx-0.5 h-5" />
    <TiptapToolButton :label="$t('rich-content.table_delete_row')" @click="editor.chain().focus().deleteRow().run()">
      <IFluentTableDeleteRow24Regular />
    </TiptapToolButton>
    <TiptapToolButton :label="$t('rich-content.table_delete_column')" @click="editor.chain().focus().deleteColumn().run()">
      <IFluentTableDeleteColumn24Regular />
    </TiptapToolButton>
    <TiptapToolButton :label="$t('rich-content.table_delete')" class="text-destructive hover:text-destructive" @click="editor.chain().focus().deleteTable().run()">
      <IFluentDelete20Regular />
    </TiptapToolButton>
  </BubbleMenu>

  <TiptapImageMenu v-if="imageMenu" :editor />

  <TiptapLinkButton
    v-if="linksEnabled"
    ref="linkDialog"
    :editor
    @submit="(url, text) => commands.insertLink(url, text)"
    @document:submit="(url, text) => commands.insertLink(url, text, { document: true })"
  />
</template>

<script setup lang="ts">
import type { Editor } from '@tiptap/core';
import { computed, useTemplateRef } from 'vue';
import { BubbleMenu } from '@tiptap/vue-3/menus';
import { trans as $t } from 'laravel-vue-i18n';

import TiptapFormattingButtons from './TiptapFormattingButtons.vue';
import TiptapImageMenu from './TiptapImageMenu.vue';
import TiptapLinkButton from './TiptapLinkButton.vue';
import TiptapToolButton from './TiptapToolButton.vue';
import { useTiptapCommands } from './composables/useTiptapCommands';
import {
  shouldShowLinkBubbleMenu,
  shouldShowTableBubbleMenu,
  shouldShowTextBubbleMenu,
} from './bubbleMenuVisibility';

import { Separator } from '@/Components/ui/separator';
import IFluentDelete20Regular from '~icons/fluent/delete20-regular';
import IFluentEdit20Regular from '~icons/fluent/edit20-regular';
import IFluentLink24Regular from '~icons/fluent/link24-regular';
import IFluentLinkDismiss20Regular from '~icons/fluent/link-dismiss20-regular';
import IFluentOpen16Regular from '~icons/fluent/open16-regular';
import IFluentTableCellsMerge24Regular from '~icons/fluent/table-cells-merge24-regular';
import IFluentTableCellsSplit24Regular from '~icons/fluent/table-cells-split24-regular';
import IFluentTableDeleteColumn24Regular from '~icons/fluent/table-delete-column24-regular';
import IFluentTableDeleteRow24Regular from '~icons/fluent/table-delete-row24-regular';
import IFluentTableFreezeRow24Regular from '~icons/fluent/table-freeze-row24-regular';
import IFluentTableInsertColumn24Regular from '~icons/fluent/table-insert-column24-regular';
import IFluentTableInsertRow24Regular from '~icons/fluent/table-insert-row24-regular';

const props = withDefaults(defineProps<{
  editor: Editor;
  /** Off where a toolbar row already sits right above short text (the `marks` field). */
  textBubble?: boolean;
  imageMenu?: boolean;
  showBold?: boolean;
  disableLinks?: boolean;
}>(), {
  // eslint-disable-next-line vue/no-boolean-default -- most editors want the selection bubble.
  textBubble: true,
  imageMenu: false,
  // eslint-disable-next-line vue/no-boolean-default -- true for every caller but a bold-styled field.
  showBold: true,
  disableLinks: false,
});

const bubbleClass = 'flex items-center gap-0.5 border border-border bg-background p-1 text-foreground shadow-md';

const commands = useTiptapCommands(() => props.editor);
const linkDialog = useTemplateRef<InstanceType<typeof TiptapLinkButton>>('linkDialog');

const linksEnabled = computed(() => !props.disableLinks && Boolean(props.editor.schema.marks.link));
const currentHref = computed(() => (props.editor.getAttributes('link').href as string | undefined) ?? '');

function openLinkDialog(): void {
  linkDialog.value?.open();
}

function tableReference() {
  const { view, state } = props.editor;
  const dom = view.domAtPos(state.selection.from).node;
  const element = dom instanceof Element ? dom : dom.parentElement;
  const table = element?.closest('table');

  return table ? { getBoundingClientRect: () => table.getBoundingClientRect() } : null;
}

defineExpose({ openLinkDialog });
</script>
