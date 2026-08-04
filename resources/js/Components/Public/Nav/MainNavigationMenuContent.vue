<template>
  <component :is="rootElement" class="dark:bg-zinc-950">
    <ul :class="ulClasses">
      <li v-for="(links, columnIndex) in item.links" :key="columnIndex">
        <template v-for="(link, index) in links" :key="link.id ?? link.name">
          <div v-if="link.type === 'divider'" class="my-4 border-t border-zinc-200 dark:border-zinc-700">
            <slot v-if="showEditIcons" :index :link :links name="editIconsDivider" />
          </div>

          <div v-else-if="link.type === 'heading'" class="mb-2 mt-4 border-b border-zinc-100 pb-1 text-xs font-bold uppercase tracking-wide text-zinc-400 first:mt-0 dark:border-zinc-800 dark:text-zinc-500">
            {{ link.name }}
          </div>

          <component :is="isUsedWithoutRoot ? linkComponent : NavigationMenuLink"
            v-else-if="link.image && link.image_render !== 'thumbnail'"
            :as="isUsedWithoutRoot ? undefined : linkComponent"
            prefetch
            :href="link.url"
            :target="link.new_tab ? '_blank' : undefined"
            :rel="link.new_tab ? 'noopener' : undefined"
            :class="[
              'relative mb-4 flex rounded-md bg-zinc-900 transition-all duration-300 last:mb-0',
              'hover:bg-zinc-800 hover:shadow-lg focus:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:focus:ring-zinc-400',
              linkClasses(link),
              resolveColSpan(link),
              link.featured && 'ring-2 ring-vusa-red/60',
            ]"
            @click="handleCloseMenu">
            <img class="absolute left-0 top-0 size-full rounded-md object-cover contrast-110"
              :class="[resolveImageOverlay(link), resolveImageBlur(link)]"
              :style="{ objectPosition: link.image_focal ?? '50% 50%' }"
              :src="link.image" alt="">
            <div class="absolute left-0 top-0 size-full rounded-md" :class="resolveImageGradient(link)" />
            <div class="relative z-10 p-4 mt-auto">
              <div class="inline-flex items-center text-lg font-black leading-tight text-white">
                {{ link.name }}
                <Icon v-if="link.new_tab" icon="fluent:open-16-regular" class="ml-1.5 size-3.5 opacity-80" />
                <Badge v-if="link.small_text" :variant="link.badge_variant ?? 'rose'" class="ml-2 rounded-full px-2 py-0 text-[10px]">
                  {{ link.small_text }}
                </Badge>
              </div>
              <p v-if="link.description" class="mt-1 line-clamp-2 leading-snug text-white/90">
                {{ link.description }}
              </p>
            </div>
            <div v-if="showEditIcons" class="relative z-20 my-auto inline-flex h-fit rounded-lg bg-white/90 p-2">
              <slot :index :link :links name="editIconsBg" />
            </div>
          </component>

          <component :is="isUsedWithoutRoot ? linkComponent : NavigationMenuLink"
            v-else :as="isUsedWithoutRoot ? undefined : linkComponent"
            prefetch
            :href="link.url"
            :target="link.new_tab ? '_blank' : undefined"
            :rel="link.new_tab ? 'noopener' : undefined"
            :class="[
              'mb-2 flex h-fit items-center rounded-md text-left leading-none transition-colors',
              'focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:focus:ring-zinc-400',
              linkClasses(link),
              resolveColSpan(link),
              link.featured && 'bg-primary/5 ring-1 ring-primary/20 dark:bg-primary/10',
            ]"
            @click="handleCloseMenu">
            <div class="flex w-full items-center justify-between gap-2">
              <img v-if="link.image && link.image_render === 'thumbnail'" class="mr-3 size-10 shrink-0 rounded-md object-cover"
                :style="{ objectPosition: link.image_focal ?? '50% 50%' }" :src="link.image" alt="">
              <div class="h-fit">
                <div class="inline-flex items-center" :class="textClasses(link)">
                  <Icon v-if="link.icon && !(link.image && link.image_render === 'thumbnail')" :icon="`fluent:${link.icon}`" class="mr-2 size-5" />
                  {{ link.name }}
                  <Icon v-if="link.new_tab" icon="fluent:open-16-regular" class="ml-1.5 size-3.5 opacity-60" />
                  <Badge v-if="link.small_text" :variant="link.badge_variant ?? 'rose'" class="ml-2 rounded-full px-2 py-0 text-[10px]">
                    {{ link.small_text }}
                  </Badge>
                </div>
                <p v-if="link.description" class="mt-1 line-clamp-2 text-sm leading-snug text-zinc-500 dark:text-zinc-400">
                  {{ link.description }}
                </p>
              </div>
              <slot v-if="showEditIcons" :index :link :links name="editIconsLink" />
            </div>
          </component>
        </template>
      </li>
    </ul>
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';

import SmartLink from '../SmartLink.vue';

import type { NavItem, NavLink, NavLinkType } from './types';
import { resolveColSpan, resolveCols, resolveDropdownWidth, resolveImageBlur, resolveImageGradient, resolveImageOverlay } from './navLinkStyles';

import {
  NavigationMenuContent,
  NavigationMenuLink,
} from '@/Components/ui/navigation-menu';
import { Badge } from '@/Components/ui/badge';

const { isUsedWithoutRoot, areLinksDisabled, item } = defineProps<{
  isUsedWithoutRoot?: boolean;
  areLinksDisabled?: boolean;
  item: NavItem;
  showEditIcons?: boolean;
}>();

const emit = defineEmits<{ closeMenu: [] }>();

const linkTypes = {
  'link': {
    textClass: 'hover:underline focus:underline transition-all',
    blockClass: 'py-1 px-2.5 hover:bg-zinc-50 focus:bg-zinc-50 dark:hover:bg-zinc-800/50 dark:focus:bg-zinc-800/50',
  },
  'block-link': {
    textClass: 'no-underline',
    blockClass: 'p-2 hover:bg-zinc-100 focus:bg-zinc-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800',
  },
  'category-link': {
    textClass: 'no-underline',
    blockClass: 'p-2.5 font-bold hover:bg-zinc-100 focus:bg-zinc-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800',
  },
  'full-height-background-link': {
    textClass: 'no-underline',
    blockClass: 'h-full hover:bg-zinc-100 focus:bg-zinc-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800',
  },
};

const rootElement = computed(() => isUsedWithoutRoot ? 'div' : NavigationMenuContent);

const ulClasses = computed(() => [
  'grid max-lg:max-h-[calc(100dvh-20rem)] lg:max-h-[calc(100vh-10rem)] gap-3 overflow-y-auto p-4 content-stretch',
  resolveDropdownWidth(item.menu_width, item.cols),
  resolveCols(item.cols),
]);

const linkComponent = computed(() => areLinksDisabled ? 'div' : SmartLink);

const linkClasses = (link: NavLink) => {
  const linkType = (link?.type && link.type !== 'divider' && link.type !== 'heading' ? link.type : 'block-link') as NavLinkType;
  return linkTypes[linkType]?.blockClass;
};

const textClasses = (link: NavLink) => {
  const linkType = (link?.type && link.type !== 'divider' && link.type !== 'heading' ? link.type : 'block-link') as NavLinkType;
  return linkTypes[linkType]?.textClass;
};

const handleCloseMenu = () => {
  emit('closeMenu');
};

</script>
