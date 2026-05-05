<template>
  <component :is="rootElement" class="dark:bg-zinc-900">
    <ul :class="ulClasses">
      <li v-for="links in item.links" :key="item.id">
        <template v-for="(link, index) in links" :key="link.name">
          <div v-if="link.type === 'divider'" class="my-4 border-t border-zinc-200 dark:border-zinc-700">
            <slot v-if="showEditIcons" :index :link :links name="editIconsDivider" />
          </div>

          <NavigationMenuLink v-else-if="link.image" :as="linkComponent" prefetch
            class="relative mb-4 flex rounded-md bg-zinc-900 transition-all duration-300 last:mb-0 hover:bg-zinc-800 hover:shadow-lg focus:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:focus:ring-zinc-400"
            :href="link.url" :class="linkClasses(link)" @click="handleCloseMenu">
            <img class="absolute left-0 top-0 size-full rounded-md object-cover opacity-60 contrast-110"
              :src="link.image" alt="Background image">
            <div class="absolute left-0 top-0 size-full rounded-md bg-gradient-to-t from-black/60 via-black/20 to-transparent" />
            <div class="relative z-10 p-4 mt-auto">
              <div class="text-lg font-black leading-tight text-white">
                {{ link.name }}
              </div>
              <p v-if="link.description" class="mt-1 line-clamp-2 leading-snug text-white/90">
                {{ link.description }}
              </p>
            </div>
            <div v-if="showEditIcons" class="relative z-20 my-auto inline-flex h-fit rounded-lg bg-white/90 p-2">
              <slot :index :link :links name="editIconsBg" />
            </div>
          </NavigationMenuLink>

          <NavigationMenuLink v-else :as="linkComponent" prefetch
            class="mb-2 flex h-fit items-center rounded-md text-left leading-none transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:focus:ring-zinc-400" :href="link.url"
            :class="linkClasses(link)" @click="handleCloseMenu">
            <div class="flex w-full items-center justify-between gap-2">
              <div class="h-fit">
                <div class="inline-flex items-center" :class="textClasses(link)">
                  <Icon v-if="link.icon" :icon="`fluent:${link.icon}`" class="mr-2 size-5" />
                  {{ link.name }}
                  <Badge v-if="link.small_text" variant="destructive" class="ml-2 rounded-full px-2 py-0 text-[10px]">
                    {{ link.small_text }}
                  </Badge>
                </div>
                <p v-if="link.description" class="mt-1 line-clamp-2 text-sm leading-snug text-zinc-500 dark:text-zinc-400">
                  {{ link.description }}
                </p>
              </div>
              <slot v-if="showEditIcons" :index :link :links name="editIconsLink" />
            </div>
          </NavigationMenuLink>
        </template>
      </li>
    </ul>
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';

import SmartLink from '../SmartLink.vue';

import {
  NavigationMenuContent,
  NavigationMenuLink,
} from '@/Components/ui/navigation-menu';
import { Badge } from '@/Components/ui/badge';

interface Link {
  name: string;
  url?: string;
  icon?: string;
  image?: string;
  description?: string;
  type?: 'link' | 'block-link' | 'category-link' | 'full-height-background-link' | 'divider';
  small_text?: string;
}

type LinkType = Exclude<Link['type'], 'divider' | undefined>;

interface Item {
  id: string;
  cols?: number;
  links: Link[][];
}

const { isUsedWithoutRoot, areLinksDisabled, item } = defineProps<{
  isUsedWithoutRoot?: boolean;
  areLinksDisabled?: boolean;
  item: Item;
  showEditIcons?: boolean;
}>();

const emit = defineEmits(['closeMenu', 'moveUp', 'moveDown']);

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

const ulClasses = computed(() => `grid max-lg:max-h-[calc(100dvh-20rem)] lg:max-h-[calc(100vh-10rem)] gap-3 overflow-y-auto p-4 max-lg:w-[340px] lg:w-[650px] xl:w-[800px] lg:grid-cols-${item.cols ?? 1} content-stretch`);

const linkComponent = computed(() => areLinksDisabled ? 'div' : SmartLink);

const linkClasses = (link: Link) => {
  const linkType = (link?.type && link.type !== 'divider' ? link.type : 'block-link') as LinkType;
  return linkTypes[linkType]?.blockClass;
};

const textClasses = (link: Link) => {
  const linkType = (link?.type && link.type !== 'divider' ? link.type : 'block-link') as LinkType;
  return linkTypes[linkType]?.textClass;
};

const handleCloseMenu = () => {
  emit('closeMenu');
};

</script>
