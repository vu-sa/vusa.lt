<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button size="icon-sm" variant="ghost" :title="$t('rich-content.toolbar_more')">
        <IFluentMoreHorizontal24Regular class="size-4" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent data-rc-smart-toolbar-layer align="end" class="w-60">
      <DropdownMenuItem :disabled="!editor.isActive('link')" @select="editor.chain().focus().unsetLink().run()">
        <IFluentLinkDismiss20Filled />
        {{ $t('rich-content.remove_link') }}
      </DropdownMenuItem>
      <DropdownMenuItem @select="editor.chain().focus().unsetAllMarks().run()">
        <IFluentClearFormatting20Filled />
        {{ $t('rich-content.clear_formatting') }}
      </DropdownMenuItem>

      <DropdownMenuSeparator />

      <DropdownMenuSub>
        <DropdownMenuSubTrigger>
          <IFluentTextEffects20Regular />
          {{ $t('rich-content.heading_style') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent data-rc-smart-toolbar-layer class="w-56">
          <DropdownMenuLabel>{{ $t('rich-content.heading_size') }}</DropdownMenuLabel>
          <DropdownMenuItem v-for="size in headingSizes" :key="size.value" @select="setHeadingAttribute('size', size.value)">
            <IFluentCheckmark12Regular :class="currentHeadingSize === size.value ? 'opacity-100' : 'opacity-0'" />
            {{ size.label }}
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuLabel>{{ $t('rich-content.heading_accent') }}</DropdownMenuLabel>
          <DropdownMenuItem v-for="accent in headingAccents" :key="accent.value" @select="setHeadingAttribute('accent', accent.value)">
            <IFluentCheckmark12Regular :class="currentHeadingAccent === accent.value ? 'opacity-100' : 'opacity-0'" />
            <span v-if="accent.swatch" class="size-2.5 rounded-full" :class="accent.swatch" />
            {{ accent.label }}
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuLabel>{{ $t('rich-content.heading_spacing') }}</DropdownMenuLabel>
          <DropdownMenuItem v-for="spacing in headingSpacings" :key="spacing.value" @select="setHeadingAttribute('spacing', spacing.value)">
            <IFluentCheckmark12Regular :class="currentHeadingSpacing === spacing.value ? 'opacity-100' : 'opacity-0'" />
            {{ spacing.label }}
          </DropdownMenuItem>
        </DropdownMenuSubContent>
      </DropdownMenuSub>

      <DropdownMenuSub>
        <DropdownMenuSubTrigger>
          <IFluentTextAlignLeft24Regular />
          {{ $t('rich-content.alignment') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent data-rc-smart-toolbar-layer>
          <DropdownMenuItem v-for="alignment in alignments" :key="alignment.value" @select="setAlignment(alignment.value)">
            <IFluentCheckmark12Regular :class="currentAlignment === alignment.value ? 'opacity-100' : 'opacity-0'" />
            {{ alignment.label }}
          </DropdownMenuItem>
        </DropdownMenuSubContent>
      </DropdownMenuSub>

      <DropdownMenuSub>
        <DropdownMenuSubTrigger>
          <IFluentTag24Regular />
          {{ $t('rich-content.tag') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent data-rc-smart-toolbar-layer class="w-52">
          <DropdownMenuLabel>{{ $t('rich-content.tag_variant') }}</DropdownMenuLabel>
          <DropdownMenuItem @select="tagVariant = 'filled'">
            <IFluentCheckmark12Regular :class="tagVariant === 'filled' ? 'opacity-100' : 'opacity-0'" />
            {{ $t('rich-content.tag_variant_filled') }}
          </DropdownMenuItem>
          <DropdownMenuItem @select="tagVariant = 'plain'">
            <IFluentCheckmark12Regular :class="tagVariant === 'plain' ? 'opacity-100' : 'opacity-0'" />
            {{ $t('rich-content.tag_variant_plain') }}
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem v-for="color in tagColors" :key="color.value" @select="applyTag(color.value)">
            <span class="size-2.5 rounded-full" :class="color.swatch" />
            {{ color.label }}
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem :disabled="!editor.isActive('rcTag')" @select="editor.chain().focus().unsetRCTag().run()">
            {{ $t('rich-content.tag_remove') }}
          </DropdownMenuItem>
        </DropdownMenuSubContent>
      </DropdownMenuSub>

      <DropdownMenuSeparator />

      <DropdownMenuItem @select="editor.chain().focus().setHorizontalRule().run()">
        <IFluentLineHorizontal120Regular />
        {{ $t('rich-content.insert_horizontal_rule') }}
      </DropdownMenuItem>
      <TiptapYoutubeButton @submit="insertYoutube">
        <DropdownMenuItem @select.prevent>
          <IFluentVideoClip24Regular />
          {{ $t('rich-content.insert_youtube') }}
        </DropdownMenuItem>
      </TiptapYoutubeButton>
      <TiptapVideoButton :show-modal="showVideoModal" @update:show-modal="showVideoModal = $event" @submit="insertVideo">
        <DropdownMenuItem @select.prevent>
          <IFluentVideo24Regular />
          {{ $t('rich-content.insert_video') }}
        </DropdownMenuItem>
      </TiptapVideoButton>

      <template v-if="editor.isActive('table')">
        <DropdownMenuSeparator />
        <DropdownMenuSub>
          <DropdownMenuSubTrigger>
            <IFluentTableSettings24Regular />
            {{ $t('rich-content.table_options') }}
          </DropdownMenuSubTrigger>
          <DropdownMenuSubContent data-rc-smart-toolbar-layer class="w-56">
            <DropdownMenuItem @select="editor.chain().focus().toggleHeaderRow().run()">
              {{ $t('rich-content.table_toggle_header') }}
            </DropdownMenuItem>
            <DropdownMenuItem @select="editor.chain().focus().addColumnAfter().run()">
              {{ $t('rich-content.table_add_column') }}
            </DropdownMenuItem>
            <DropdownMenuItem @select="editor.chain().focus().addRowAfter().run()">
              {{ $t('rich-content.table_add_row') }}
            </DropdownMenuItem>
            <DropdownMenuItem :disabled="!editor.can().mergeCells()" @select="editor.chain().focus().mergeCells().run()">
              {{ $t('rich-content.table_merge_cells') }}
            </DropdownMenuItem>
            <DropdownMenuItem :disabled="!editor.can().splitCell()" @select="editor.chain().focus().splitCell().run()">
              {{ $t('rich-content.table_split_cell') }}
            </DropdownMenuItem>
            <DropdownMenuItem @select="editor.chain().focus().deleteColumn().run()">
              {{ $t('rich-content.table_delete_column') }}
            </DropdownMenuItem>
            <DropdownMenuItem @select="editor.chain().focus().deleteRow().run()">
              {{ $t('rich-content.table_delete_row') }}
            </DropdownMenuItem>
            <DropdownMenuItem @select="editor.chain().focus().fixTables().run()">
              {{ $t('rich-content.table_fix') }}
            </DropdownMenuItem>
          </DropdownMenuSubContent>
        </DropdownMenuSub>
      </template>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import type { Editor } from '@tiptap/core';
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import TiptapVideoButton from './TiptapVideoButton.vue';
import TiptapYoutubeButton from './TiptapYoutubeButton.vue';
import type { HeadingAccent, HeadingSize, HeadingSpacing } from './CustomHeading';
import type { RCTagColor, RCTagVariant } from './RCTag';
import type { TextAlignValue } from './TextAlign';

import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import IFluentCheckmark12Regular from '~icons/fluent/checkmark12-regular';
import IFluentClearFormatting20Filled from '~icons/fluent/clear-formatting20-filled';
import IFluentLineHorizontal120Regular from '~icons/fluent/line-horizontal-1-20-regular';
import IFluentLinkDismiss20Filled from '~icons/fluent/link-dismiss20-filled';
import IFluentMoreHorizontal24Regular from '~icons/fluent/more-horizontal24-regular';
import IFluentTableSettings24Regular from '~icons/fluent/table-settings24-regular';
import IFluentTag24Regular from '~icons/fluent/tag24-regular';
import IFluentTextAlignLeft24Regular from '~icons/fluent/text-align-left24-regular';
import IFluentTextEffects20Regular from '~icons/fluent/text-effects20-regular';
import IFluentVideo24Regular from '~icons/fluent/video24-regular';
import IFluentVideoClip24Regular from '~icons/fluent/video-clip24-regular';

const props = defineProps<{ editor: Editor }>();

const showVideoModal = ref(false);
const tagVariant = ref<RCTagVariant>('filled');

const headingSizes: { value: HeadingSize; label: string }[] = [
  { value: 'sm', label: $t('rich-content.heading_size_sm') },
  { value: 'md', label: $t('rich-content.heading_size_md') },
  { value: 'lg', label: $t('rich-content.heading_size_lg') },
  { value: 'xl', label: $t('rich-content.heading_size_xl') },
];

const headingAccents: { value: HeadingAccent; label: string; swatch?: string }[] = [
  { value: 'none', label: $t('rich-content.heading_accent_none') },
  { value: 'red', label: $t('rich-content.colors.red'), swatch: 'bg-red-500' },
  { value: 'yellow', label: $t('rich-content.colors.yellow'), swatch: 'bg-yellow-500' },
  { value: 'zinc', label: $t('rich-content.colors.gray'), swatch: 'bg-zinc-500' },
];

const headingSpacings: { value: HeadingSpacing; label: string }[] = [
  { value: 'default', label: $t('rich-content.heading_spacing_default') },
  { value: 'tight', label: $t('rich-content.heading_spacing_tight') },
  { value: 'loose', label: $t('rich-content.heading_spacing_loose') },
  { value: 'none', label: $t('rich-content.heading_spacing_none') },
];

const alignments: { value: TextAlignValue; label: string }[] = [
  { value: 'start', label: $t('rich-content.align_left') },
  { value: 'center', label: $t('rich-content.align_center') },
  { value: 'end', label: $t('rich-content.align_right') },
];

const tagColors: { value: RCTagColor; label: string; swatch: string }[] = [
  { value: 'zinc', label: $t('rich-content.colors.gray'), swatch: 'bg-zinc-500' },
  { value: 'red', label: $t('rich-content.colors.red'), swatch: 'bg-red-500' },
  { value: 'yellow', label: $t('rich-content.colors.yellow'), swatch: 'bg-yellow-500' },
  { value: 'green', label: $t('rich-content.colors.green'), swatch: 'bg-green-500' },
];

const currentHeadingSize = computed<HeadingSize | null>(
  () => (props.editor.getAttributes('heading').size as HeadingSize | undefined) ?? null,
);
const currentHeadingAccent = computed<HeadingAccent>(
  () => (props.editor.getAttributes('heading').accent as HeadingAccent | undefined) ?? 'none',
);
const currentHeadingSpacing = computed<HeadingSpacing>(
  () => (props.editor.getAttributes('heading').spacing as HeadingSpacing | undefined) ?? 'default',
);
const currentAlignment = computed<TextAlignValue>(() => {
  const type = props.editor.isActive('heading') ? 'heading' : 'paragraph';
  return (props.editor.getAttributes(type).align as TextAlignValue | undefined) ?? 'start';
});

function setHeadingAttribute(attribute: 'size' | 'accent' | 'spacing', value: string): void {
  props.editor.chain().focus().updateAttributes('heading', { [attribute]: value }).run();
}

function setAlignment(alignment: TextAlignValue): void {
  const type = props.editor.isActive('heading') ? 'heading' : 'paragraph';
  props.editor.chain().focus().updateAttributes(type, { align: alignment }).run();
}

function applyTag(color: RCTagColor): void {
  props.editor.chain().focus().setRCTag({ variant: tagVariant.value, color }).run();
}

function insertYoutube(url: string): void {
  props.editor.commands.setYoutubeVideo({ src: url });
}

function insertVideo(url: string): void {
  props.editor.chain().focus().setVideo(url).run();
  showVideoModal.value = false;
}
</script>
