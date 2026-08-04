<!-- NOTHING may precede the root <div> here — not even a comment. See the script block. -->
<template>
  <div
    class="nav-link-card group text-sm transition-colors"
    :class="isFullHeight
      ? ['relative isolate block min-h-24 grow overflow-hidden rounded-md border', !link.is_active && 'opacity-60']
      : ['flex items-center gap-2 rounded-md border bg-background p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/50']"
    :data-link-id="link.id"
    :data-link-type="linkType"
  >
    <template v-if="isFullHeight">
      <img
        v-if="imageUrl"
        class="absolute inset-0 size-full object-cover"
        :class="[imageOverlayClass, imageBlurClass]"
        :style="{ objectPosition: imageFocal }"
        :src="imageUrl"
        alt=""
      >
      <div v-else class="absolute inset-0 flex items-center justify-center bg-zinc-200 dark:bg-zinc-700">
        <ImageIcon class="size-6 text-zinc-400" />
      </div>
      <div class="absolute inset-0" :class="imageGradientClass" />

      <button type="button" class="nav-link-handle absolute left-1.5 top-1.5 z-20 cursor-grab touch-none rounded bg-black/30 p-1 text-white/80 hover:text-white active:cursor-grabbing">
        <GripVertical class="size-3.5" />
      </button>

      <div class="absolute right-1.5 top-1.5 z-20 flex items-center gap-1 rounded-md bg-white/90 p-1 dark:bg-zinc-900/90">
        <Tooltip>
          <TooltipTrigger as-child>
            <span class="inline-flex">
              <Switch
                :model-value="link.is_active"
                @update:model-value="val => $emit('toggle-active', val)"
              />
            </span>
          </TooltipTrigger>
          <TooltipContent>
            {{ link.is_active ? $t('navigation.builder.active') : $t('navigation.builder.inactive') }}
          </TooltipContent>
        </Tooltip>

        <Link :href="route('navigation.edit', { navigation: link.id })">
          <Button size="icon-xs" variant="ghost">
            <Pencil class="size-3.5" />
          </Button>
        </Link>

        <AlertDialog>
          <AlertDialogTrigger as-child>
            <Button size="icon-xs" variant="ghost" class="text-destructive hover:text-destructive">
              <Trash2 class="size-3.5" />
            </Button>
          </AlertDialogTrigger>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>{{ $t('navigation.builder.delete_confirm_title') }}</AlertDialogTitle>
              <AlertDialogDescription>{{ $t('navigation.builder.delete_confirm_description') }}</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>{{ $t('forms.cancel') }}</AlertDialogCancel>
              <AlertDialogAction @click="$emit('delete')">
                {{ $t('forms.delete') }}
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </div>

      <span class="absolute inset-x-0 bottom-0 z-10 truncate p-2 font-medium text-white drop-shadow">
        {{ displayName }}
      </span>
    </template>

    <template v-else>
      <button type="button" class="nav-link-handle cursor-grab touch-none text-zinc-400 hover:text-zinc-600 active:cursor-grabbing dark:hover:text-zinc-300">
        <GripVertical class="size-4" />
      </button>

      <component :is="typeIcon" class="size-3.5 shrink-0 text-zinc-400" :class="[!link.is_active && 'opacity-50']" />

      <!-- Only the descriptive content dims when inactive — the switch and action
           buttons must stay at full contrast so they're still usable and legible. -->
      <div class="min-w-0 flex-1" :class="[!link.is_active && 'opacity-50']">
        <div class="flex items-center gap-1.5">
          <span class="truncate" :class="[isStructural && 'italic text-muted-foreground']">
            {{ displayName }}
          </span>
          <Badge v-if="link.extra_attributes?.small_text" :variant="link.extra_attributes.badge_variant ?? 'rose'" size="tiny">
            {{ link.extra_attributes.small_text }}
          </Badge>
          <ImageIcon v-if="link.extra_attributes?.image" class="size-3 shrink-0 text-zinc-400" />
          <Star v-if="link.extra_attributes?.featured" class="size-3 shrink-0 fill-amber-400 text-amber-400" />
        </div>
        <p v-if="link.url && link.url !== '#'" class="truncate text-xs text-muted-foreground">
          {{ link.url }}
        </p>
      </div>

      <Tooltip>
        <TooltipTrigger as-child>
          <!-- The plain `<span>` (not the Switch itself) is the as-child target: both
               TooltipTrigger and SwitchRoot set their own `data-state` attribute, and
               `as-child` merges the trigger's attrs onto whatever single element it
               wraps — landing the tooltip's own `data-state` (open/closed) on the
               Switch's root would clobber its `checked`/`unchecked` one, and with it
               every `data-[state=...]` Tailwind class the Switch relies on for color. -->
          <span class="inline-flex">
            <Switch
              :model-value="link.is_active"
              @update:model-value="val => $emit('toggle-active', val)"
            />
          </span>
        </TooltipTrigger>
        <TooltipContent>
          {{ link.is_active ? $t('navigation.builder.active') : $t('navigation.builder.inactive') }}
        </TooltipContent>
      </Tooltip>

      <Link :href="route('navigation.edit', { navigation: link.id })">
        <Button size="icon-xs" variant="ghost">
          <Pencil class="size-3.5" />
        </Button>
      </Link>

      <AlertDialog>
        <AlertDialogTrigger as-child>
          <Button size="icon-xs" variant="ghost" class="text-destructive hover:text-destructive">
            <Trash2 class="size-3.5" />
          </Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{{ $t('navigation.builder.delete_confirm_title') }}</AlertDialogTitle>
            <AlertDialogDescription>{{ $t('navigation.builder.delete_confirm_description') }}</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>{{ $t('forms.cancel') }}</AlertDialogCancel>
            <AlertDialogAction @click="$emit('delete')">
              {{ $t('forms.delete') }}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </template>
  </div>
</template>

<script setup lang="ts">
/**
 * One draggable card in the navigation builder. A `full-height-background-link` fills
 * its whole column on the public site, so this card previews that treatment
 * (background image, gradient, name overlaid at the bottom, `grow` so it stretches to
 * the column's height) instead of the compact row every other type uses. The two
 * layouts are `<template>` branches inside the one root element rather than sibling
 * v-if/v-else roots.
 *
 * That single root element is load-bearing, and the template above must keep it as the
 * *literal first thing* in the block: Vue's SFC compiler preserves template comments in
 * dev builds, so a leading `<!-- … -->` turns the root into a Fragment (two text
 * anchors + the comment + the div). SortableJS drags only the `<div>`, leaving the
 * anchors behind in the source column — the moved node then sits outside the range
 * `removeFragment` walks on unmount, so Vue can no longer remove it and a cross-column
 * drag leaves a duplicate card behind in the column it came from. Prose about this
 * component belongs here in the script, never above the template's root.
 */
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Link } from '@inertiajs/vue3';
import { GripVertical, Image as ImageIcon, Link as LinkIcon, Minus, Pencil, Square, Star, Trash2, Type } from 'lucide-vue-next';

import type { AdminNavigationLink } from './types';

import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
import { blurClass, gradientClass, overlayClass } from '@/Components/Public/Nav/navLinkStyles';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';

const props = defineProps<{
  link: AdminNavigationLink;
}>();

defineEmits<{
  (event: 'toggle-active', value: boolean): void;
  (event: 'delete'): void;
}>();

const linkType = computed(() => props.link.extra_attributes?.type ?? 'block-link');

const isStructural = computed(() => linkType.value === 'divider' || linkType.value === 'heading');
const isFullHeight = computed(() => linkType.value === 'full-height-background-link');

const imageUrl = computed(() => props.link.extra_attributes?.image ?? null);
const imageOverlayClass = computed(() => overlayClass[props.link.extra_attributes?.image_overlay ?? 'medium']);
const imageBlurClass = computed(() => blurClass[props.link.extra_attributes?.image_blur ?? 0]);
const imageGradientClass = computed(() => gradientClass[props.link.extra_attributes?.image_gradient ?? 'bottom']);
const imageFocal = computed(() => props.link.extra_attributes?.image_focal ?? '50% 50%');

const displayName = computed(() => {
  if (linkType.value === 'divider') {
    return $t('navigation.form.type_divider');
  }

  if (linkType.value === 'heading') {
    return props.link.name || $t('navigation.form.type_heading');
  }

  return props.link.name || `#${props.link.id}`;
});

const typeIcon = computed(() => {
  switch (linkType.value) {
    case 'divider': return Minus;
    case 'heading': return Type;
    case 'full-height-background-link':
    case 'block-link':
    case 'category-link': return Square;
    default: return LinkIcon;
  }
});
</script>
