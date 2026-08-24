<template>
  <!--
    Image controls belong next to the image, not in the toolbar 20cm away: the toolbar
    copy was easy to miss, and its text-alignment buttons sat right beside the image
    ones doing something different. A distinct plugin-key keeps this menu from fighting
    the text bubble menu for the same ProseMirror plugin slot.
  -->
  <BubbleMenu
    v-if="editor"
    :editor
    plugin-key="imageBubbleMenu"
    :should-show="shouldShowImageBubbleMenu"
    :options="{ placement: 'top', offset: 8 }"
    class="flex items-center gap-1 rounded-lg border bg-white p-1 shadow-md dark:border-zinc-700 dark:bg-zinc-900"
  >
    <ButtonGroup>
      <Button v-for="option in alignments" :key="option.value" size="sm" class="h-8 w-8 p-0"
        :variant="isAlignmentActive(option.value) ? 'default' : 'ghost'" :title="option.label"
        @click="setAlignment(option.value)">
        <component :is="option.icon" class="h-4 w-4" />
      </Button>
    </ButtonGroup>

    <Separator orientation="vertical" class="mx-1 h-5" />

    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button size="sm" variant="ghost" class="h-8 gap-1 px-2" :title="$t('rich-content.image_size')">
          <IFluentResize20Regular class="h-4 w-4" />
          <span class="text-xs">{{ currentSizeLabel }}</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start">
        <DropdownMenuItem v-for="size in sizes" :key="size.value" @click="setSizePreset(size.value)">
          {{ size.label }}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>

    <Button size="sm" variant="ghost" class="h-8 gap-1 px-2" :title="$t('rich-content.image_alt_text')"
      @click="openAccessibilityDialog">
      <IFluentAccessibility24Regular class="h-4 w-4" />
      <!-- Missing alt is the one thing worth nagging about inline; everything else is
           discoverable from the icons. -->
      <span v-if="!hasAltText" class="text-xs font-medium text-vusa-red">
        {{ $t('rich-content.image_alt_missing') }}
      </span>
    </Button>

    <Separator orientation="vertical" class="mx-1 h-5" />

    <Button size="sm" variant="ghost" class="h-8 w-8 p-0 text-red-600 hover:text-red-700 dark:text-red-400"
      :title="$t('rich-content.image_remove')" @click="removeImage">
      <IFluentDelete24Regular class="h-4 w-4" />
    </Button>
  </BubbleMenu>

  <ImageAccessibilityDialog
    v-model:open="showImageDialog"
    :image-data="currentImageData"
    @submit="submitAccessibilityChanges"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { BubbleMenu } from '@tiptap/vue-3/menus';
import type { Editor } from '@tiptap/vue-3';
import { trans as $t } from 'laravel-vue-i18n';

import ImageAccessibilityDialog from './ImageAccessibilityDialog.vue';
import { useTiptapImageControls, type ImageAlignment, type ImageSizePreset } from './composables/useTiptapImageControls';
import { shouldShowImageBubbleMenu } from './bubbleMenuVisibility';

import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Separator } from '@/Components/ui/separator';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import IFluentTextAlignLeft24Regular from '~icons/fluent/text-align-left24-regular';
import IFluentTextAlignCenter24Regular from '~icons/fluent/text-align-center24-regular';
import IFluentTextAlignRight24Regular from '~icons/fluent/text-align-right24-regular';
import IFluentResize20Regular from '~icons/fluent/resize20-regular';
import IFluentAccessibility24Regular from '~icons/fluent/accessibility24-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';

const props = defineProps<{
  editor: Editor | undefined;
}>();

const editor = computed(() => props.editor);

const {
  showImageDialog,
  currentImageData,
  isAlignmentActive,
  setAlignment,
  setSizePreset,
  openAccessibilityDialog,
  submitAccessibilityChanges,
  IMAGE_SIZE_PRESETS,
} = useTiptapImageControls(editor);

const alignments: { value: ImageAlignment; label: string; icon: unknown }[] = [
  { value: 'left', label: $t('rich-content.align_left'), icon: IFluentTextAlignLeft24Regular },
  { value: 'center', label: $t('rich-content.align_center'), icon: IFluentTextAlignCenter24Regular },
  { value: 'right', label: $t('rich-content.align_right'), icon: IFluentTextAlignRight24Regular },
];

const sizes: { value: ImageSizePreset; label: string }[] = [
  { value: 'small', label: `${$t('rich-content.size_small')} (300px)` },
  { value: 'medium', label: `${$t('rich-content.size_medium')} (500px)` },
  { value: 'large', label: `${$t('rich-content.size_large')} (800px)` },
  { value: 'full', label: $t('rich-content.size_full') },
];

/** Reads back the preset a stored width corresponds to, so the trigger isn't a mystery. */
const currentSizeLabel = computed(() => {
  const width = props.editor?.getAttributes('image').width;

  if (!width) {
    return $t('rich-content.size_auto');
  }

  const preset = (Object.keys(IMAGE_SIZE_PRESETS) as ImageSizePreset[])
    .find(key => IMAGE_SIZE_PRESETS[key] === String(width));

  return preset ? sizes.find(size => size.value === preset)!.label.replace(/\s*\(.*\)$/, '') : String(width);
});

const hasAltText = computed(() => Boolean(props.editor?.getAttributes('image').alt));

function removeImage() {
  props.editor?.chain().focus().deleteSelection().run();
}
</script>
