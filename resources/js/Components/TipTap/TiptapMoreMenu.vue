<template>
  <DropdownMenu :modal="false">
    <DropdownMenuTrigger as-child>
      <Button
        type="button"
        size="icon-sm"
        variant="ghost"
        data-testid="tiptap-more-menu"
        :title="$t('rich-content.toolbar_more')"
        :aria-label="$t('rich-content.toolbar_more')"
      >
        <IFluentMoreHorizontal24Regular class="size-4" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent data-rc-smart-toolbar-layer align="end" class="w-60">
      <template v-if="showHistory">
        <DropdownMenuItem class="sm:hidden" :disabled="!editor.can().undo()" @select="editor.chain().focus().undo().run()">
          <IFluentArrowUndo20Regular />
          {{ $t('rich-content.undo') }}
        </DropdownMenuItem>
        <DropdownMenuItem class="sm:hidden" :disabled="!editor.can().redo()" @select="editor.chain().focus().redo().run()">
          <IFluentArrowRedo20Regular />
          {{ $t('rich-content.redo') }}
        </DropdownMenuItem>
        <DropdownMenuSeparator class="sm:hidden" />
      </template>

      <DropdownMenuItem v-if="tools.blockquote" data-testid="tiptap-more-blockquote" @select="editor.chain().focus().toggleBlockquote().run()">
        <IFluentTextQuote20Regular />
        {{ $t('rich-content.blockquote') }}
        <IFluentCheckmark12Regular v-if="editor.isActive('blockquote')" class="ml-auto" />
      </DropdownMenuItem>
      <DropdownMenuItem v-if="tools.clearFormatting" @select="commands.clearFormatting()">
        <IFluentClearFormatting20Filled />
        {{ $t('rich-content.clear_formatting') }}
      </DropdownMenuItem>

      <DropdownMenuSub v-if="tools.headingStyle && editor.isActive('heading')">
        <DropdownMenuSubTrigger>
          <IFluentTextEffects20Regular />
          {{ $t('rich-content.heading_style') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent data-rc-smart-toolbar-layer class="w-56">
          <DropdownMenuLabel>{{ $t('rich-content.heading_size') }}</DropdownMenuLabel>
          <DropdownMenuItem v-for="size in headingSizeOptions()" :key="size.value" @select="commands.setHeadingAttribute('size', size.value)">
            <IFluentCheckmark12Regular :class="commands.currentHeadingSize.value === size.value ? 'opacity-100' : 'opacity-0'" />
            {{ size.label }}
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuLabel>{{ $t('rich-content.heading_accent') }}</DropdownMenuLabel>
          <DropdownMenuItem v-for="accent in headingAccentOptions()" :key="accent.value" @select="commands.setHeadingAttribute('accent', accent.value)">
            <IFluentCheckmark12Regular :class="commands.currentHeadingAccent.value === accent.value ? 'opacity-100' : 'opacity-0'" />
            <span v-if="accent.swatch" class="size-2.5 rounded-full" :class="accent.swatch" />
            {{ accent.label }}
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuLabel>{{ $t('rich-content.heading_spacing') }}</DropdownMenuLabel>
          <DropdownMenuItem v-for="spacing in headingSpacingOptions()" :key="spacing.value" @select="commands.setHeadingAttribute('spacing', spacing.value)">
            <IFluentCheckmark12Regular :class="commands.currentHeadingSpacing.value === spacing.value ? 'opacity-100' : 'opacity-0'" />
            {{ spacing.label }}
          </DropdownMenuItem>
        </DropdownMenuSubContent>
      </DropdownMenuSub>

      <DropdownMenuSub v-if="tools.alignment">
        <DropdownMenuSubTrigger>
          <IFluentTextAlignLeft24Regular />
          {{ $t('rich-content.alignment') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent data-rc-smart-toolbar-layer>
          <DropdownMenuItem v-for="alignment in alignmentOptions()" :key="alignment.value" @select="commands.setAlignment(alignment.value)">
            <IFluentCheckmark12Regular :class="commands.currentAlignment.value === alignment.value ? 'opacity-100' : 'opacity-0'" />
            {{ alignment.label }}
          </DropdownMenuItem>
        </DropdownMenuSubContent>
      </DropdownMenuSub>

      <DropdownMenuSub v-if="tools.tag">
        <DropdownMenuSubTrigger>
          <IFluentTag24Regular />
          {{ $t('rich-content.tag') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent data-rc-smart-toolbar-layer class="w-52">
          <DropdownMenuLabel>{{ $t('rich-content.tag_variant') }}</DropdownMenuLabel>
          <DropdownMenuItem @select.prevent="commands.tagVariant.value = 'filled'">
            <IFluentCheckmark12Regular :class="commands.tagVariant.value === 'filled' ? 'opacity-100' : 'opacity-0'" />
            {{ $t('rich-content.tag_variant_filled') }}
          </DropdownMenuItem>
          <DropdownMenuItem @select.prevent="commands.tagVariant.value = 'plain'">
            <IFluentCheckmark12Regular :class="commands.tagVariant.value === 'plain' ? 'opacity-100' : 'opacity-0'" />
            {{ $t('rich-content.tag_variant_plain') }}
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem v-for="color in tagColorOptions()" :key="color.value" @select="commands.applyTag(color.value)">
            <span class="size-2.5 rounded-full" :class="color.swatch" />
            {{ color.label }}
          </DropdownMenuItem>
          <template v-if="editor.isActive('rcTag')">
            <DropdownMenuSeparator />
            <DropdownMenuItem @select="commands.removeTag()">
              {{ $t('rich-content.tag_remove') }}
            </DropdownMenuItem>
          </template>
        </DropdownMenuSubContent>
      </DropdownMenuSub>

      <!-- The canvas toolbar has no Insert menu, so its rarer inserts live here. -->
      <template v-if="includeInserts">
        <DropdownMenuSeparator />
        <DropdownMenuItem v-if="tools.horizontalRule" @select="commands.insertHorizontalRule()">
          <IFluentLineHorizontal120Regular />
          {{ $t('rich-content.insert_horizontal_rule') }}
        </DropdownMenuItem>
        <DropdownMenuItem v-if="tools.youtube" @select="openLater(showYoutube)">
          <IFluentVideoClip24Regular />
          {{ $t('rich-content.insert_youtube') }}
        </DropdownMenuItem>
        <DropdownMenuItem v-if="tools.video" @select="openLater(showVideo)">
          <IFluentVideo24Regular />
          {{ $t('rich-content.insert_video') }}
        </DropdownMenuItem>
      </template>
    </DropdownMenuContent>
  </DropdownMenu>

  <template v-if="includeInserts">
    <TiptapYoutubeButton v-if="tools.youtube" v-model:open="showYoutube" @submit="commands.insertYoutube" />
    <ImageSelector v-if="tools.video" v-model:show-modal="showVideo" selection-type="video" @submit="(video) => commands.insertVideo(video.src)" />
  </template>
</template>

<script setup lang="ts">
import type { Editor } from '@tiptap/core';
import { ref, type Ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import ImageSelector from './ImageSelector.vue';
import TiptapYoutubeButton from './TiptapYoutubeButton.vue';
import { useTiptapCommands } from './composables/useTiptapCommands';
import {
  alignmentOptions,
  headingAccentOptions,
  headingSizeOptions,
  headingSpacingOptions,
  tagColorOptions,
} from './toolbarOptions';
import type { ToolbarTools } from './toolbarProfiles';

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
import IFluentArrowRedo20Regular from '~icons/fluent/arrow-redo20-regular';
import IFluentArrowUndo20Regular from '~icons/fluent/arrow-undo20-regular';
import IFluentCheckmark12Regular from '~icons/fluent/checkmark12-regular';
import IFluentClearFormatting20Filled from '~icons/fluent/clear-formatting20-filled';
import IFluentLineHorizontal120Regular from '~icons/fluent/line-horizontal-1-20-regular';
import IFluentMoreHorizontal24Regular from '~icons/fluent/more-horizontal24-regular';
import IFluentTag24Regular from '~icons/fluent/tag24-regular';
import IFluentTextAlignLeft24Regular from '~icons/fluent/text-align-left24-regular';
import IFluentTextEffects20Regular from '~icons/fluent/text-effects20-regular';
import IFluentTextQuote20Regular from '~icons/fluent/text-quote20-regular';
import IFluentVideo24Regular from '~icons/fluent/video24-regular';
import IFluentVideoClip24Regular from '~icons/fluent/video-clip24-regular';

const props = withDefaults(defineProps<{
  editor: Editor;
  tools: ToolbarTools;
  /** Undo/redo for phones, where the toolbar row has no room for them. */
  showHistory?: boolean;
  includeInserts?: boolean;
}>(), {
  showHistory: false,
  includeInserts: false,
});

const commands = useTiptapCommands(() => props.editor);

const showYoutube = ref(false);
const showVideo = ref(false);

function openLater(flag: Ref<boolean>): void {
  setTimeout(() => {
    flag.value = true;
  });
}
</script>
