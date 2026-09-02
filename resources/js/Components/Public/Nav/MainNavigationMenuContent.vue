<template>
  <component :is="rootElement" class="bg-popover text-popover-foreground">
    <ul :class="ulClasses">
      <li v-for="(links, columnIndex) in item.links" :key="columnIndex">
        <template v-for="(link, index) in links" :key="link.id ?? link.name">
          <div v-if="link.type === 'divider'" class="my-4 border-t border-border">
            <slot v-if="showEditIcons" :index :link :links name="editIconsDivider" />
          </div>

          <div v-else-if="link.type === 'heading'" class="u-eyebrow mb-2 mt-4 border-b border-border pb-1.5 first:mt-0">
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
              // Fixed-dark card: the scrim keeps the white copy legible in either theme, so it
              // does not follow the surface the way the rest of the panel does.
              'relative mb-4 flex bg-zinc-900 transition-all duration-300 last:mb-0',
              'hover:bg-zinc-800 focus:bg-zinc-800 focus:outline-2 focus:outline-offset-2 focus:outline-ring',
              linkClasses(link),
              resolveColSpan(link),
              link.featured && 'ring-2 ring-vusa-red/60',
            ]"
            @click="handleCloseMenu">
            <img class="absolute left-0 top-0 size-full object-cover grayscale contrast-110"
              :class="[resolveImageOverlay(link), resolveImageBlur(link)]"
              :style="{ objectPosition: link.image_focal ?? '50% 50%' }"
              :src="link.image" alt="">
            <div class="absolute left-0 top-0 size-full" :class="resolveImageGradient(link)" />
            <div class="relative z-10 p-4 mt-auto">
              <div class="inline-flex items-center text-lg font-black leading-tight text-white">
                {{ link.name }}
                <Icon v-if="link.new_tab" icon="fluent:open-16-regular" class="ml-1.5 size-3.5 opacity-80" />
                <Badge v-if="link.small_text" :variant="link.badge_variant ?? 'rose'" class="ml-2 px-2 py-0 text-[10px]">
                  {{ link.small_text }}
                </Badge>
              </div>
              <p v-if="link.description" class="mt-1 line-clamp-2 leading-snug text-white/90">
                {{ link.description }}
              </p>
            </div>
            <div v-if="showEditIcons" class="relative z-20 my-auto inline-flex h-fit bg-background/90 p-2">
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
              'mb-2 flex h-fit items-center text-left leading-none transition-colors',
              'focus:outline-2 focus:-outline-offset-2 focus:outline-ring',
              linkClasses(link),
              resolveColSpan(link),
              link.featured && 'bg-primary/5 ring-1 ring-primary/20 dark:bg-primary/10',
            ]"
            @click="handleCloseMenu">
            <div class="flex w-full items-center justify-between gap-2">
              <img v-if="link.image && link.image_render === 'thumbnail'" class="mr-3 size-10 shrink-0 object-cover grayscale"
                :style="{ objectPosition: link.image_focal ?? '50% 50%' }" :src="link.image" alt="">
              <div class="h-fit">
                <div class="inline-flex items-center" :class="textClasses(link)">
                  <Icon v-if="link.icon && !(link.image && link.image_render === 'thumbnail')" :icon="`fluent:${link.icon}`" class="mr-2 size-5" />
                  {{ link.name }}
                  <Icon v-if="link.new_tab" icon="fluent:open-16-regular" class="ml-1.5 size-3.5 opacity-60" />
                  <Badge v-if="link.small_text" :variant="link.badge_variant ?? 'rose'" class="ml-2 px-2 py-0 text-[10px]">
                    {{ link.small_text }}
                  </Badge>
                </div>
                <p v-if="link.description" class="mt-1 line-clamp-2 text-sm leading-snug text-muted-foreground">
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
    blockClass: 'py-1 px-2.5 hover:bg-secondary focus:bg-secondary',
  },
  'block-link': {
    textClass: 'no-underline',
    blockClass: 'p-2 hover:bg-secondary focus:bg-secondary',
  },
  'category-link': {
    textClass: 'no-underline',
    blockClass: 'p-2.5 font-bold hover:bg-secondary focus:bg-secondary',
  },
  'full-height-background-link': {
    textClass: 'no-underline',
    blockClass: 'h-full hover:bg-secondary focus:bg-secondary',
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
