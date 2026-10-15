<template>
  <!-- Non-modal, and dialogs mount outside the menu: a modal menu closing while a
       dialog opens leaves reka's pointer-events lock on <body>. -->
  <DropdownMenu :modal="false">
    <DropdownMenuTrigger as-child>
      <Button
        type="button"
        size="sm"
        voice="sentence"
        variant="ghost"
        class="gap-1 px-2"
        data-testid="tiptap-insert-menu"
        :title="$t('rich-content.insert')"
      >
        <IFluentAdd20Regular />
        <span class="hidden sm:inline">{{ $t('rich-content.insert') }}</span>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent data-rc-smart-toolbar-layer align="start" class="w-56">
      <DropdownMenuItem v-if="tools.image" data-testid="tiptap-insert-image" @select="openLater(showImage)">
        <IFluentImage24Regular />
        {{ $t('rich-content.select_image') }}
      </DropdownMenuItem>
      <DropdownMenuItem v-if="tools.youtube" data-testid="tiptap-insert-youtube" @select="openLater(showYoutube)">
        <IFluentVideoClip24Regular />
        {{ $t('rich-content.insert_youtube') }}
      </DropdownMenuItem>
      <DropdownMenuItem v-if="tools.video" data-testid="tiptap-insert-video" @select="openLater(showVideo)">
        <IFluentVideo24Regular />
        {{ $t('rich-content.insert_video') }}
      </DropdownMenuItem>
      <DropdownMenuItem v-if="tools.table && !editor.isActive('table')" data-testid="tiptap-insert-table" @select="commands.insertTable()">
        <IFluentTableAdd24Regular />
        {{ $t('rich-content.insert_table') }}
      </DropdownMenuItem>
      <DropdownMenuItem v-if="tools.horizontalRule" data-testid="tiptap-insert-rule" @select="commands.insertHorizontalRule()">
        <IFluentLineHorizontal120Regular />
        {{ $t('rich-content.insert_horizontal_rule') }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>

  <ImageSelector v-if="tools.image" v-model:show-modal="showImage" @submit="commands.insertImage" />
  <ImageSelector v-if="tools.video" v-model:show-modal="showVideo" selection-type="video" @submit="(video) => commands.insertVideo(video.src)" />
  <TiptapYoutubeButton v-if="tools.youtube" v-model:open="showYoutube" @submit="commands.insertYoutube" />
</template>

<script setup lang="ts">
import type { Editor } from '@tiptap/core';
import { ref, type Ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import ImageSelector from './ImageSelector.vue';
import TiptapYoutubeButton from './TiptapYoutubeButton.vue';
import { useTiptapCommands } from './composables/useTiptapCommands';
import type { ToolbarTools } from './toolbarProfiles';

import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import IFluentAdd20Regular from '~icons/fluent/add20-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';
import IFluentLineHorizontal120Regular from '~icons/fluent/line-horizontal-1-20-regular';
import IFluentTableAdd24Regular from '~icons/fluent/table-add24-regular';
import IFluentVideo24Regular from '~icons/fluent/video24-regular';
import IFluentVideoClip24Regular from '~icons/fluent/video-clip24-regular';

const props = defineProps<{
  editor: Editor;
  tools: ToolbarTools;
}>();

const commands = useTiptapCommands(() => props.editor);

const showImage = ref(false);
const showVideo = ref(false);
const showYoutube = ref(false);

/** Wait for the menu to close and hand focus back before a dialog claims it. */
function openLater(flag: Ref<boolean>): void {
  setTimeout(() => {
    flag.value = true;
  });
}
</script>
