<template>
  <component :is="rootElement" class="dark:bg-zinc-900">
    <ul :class="ulClasses">
      <li v-for="links in item.links" :key="item.id">
        <template v-for="(link, index) in links" :key="link.name">
          <div v-if="link.type === 'divider'" class="my-3 border-t border-zinc-200">
            <slot v-if="showEditIcons" :index :link :links name="editIconsDivider" />
          </div>

          <NavigationMenuLink v-else-if="link.image" :as="linkComponent" prefetch
            class="relative mb-2 flex rounded-md bg-zinc-900 transition-colors last:mb-0 hover:bg-zinc-800"
            :href="link.url" :class="linkClasses(link)" @click="handleCloseMenu">
            <img class="absolute left-0 top-0 size-full rounded-md object-cover opacity-25 contrast-150"
              :src="link.image" alt="Background image">
            <div class="z-50 p-4 mt-auto">
              <div class="text-lg font-black leading-tight text-zinc-50">
                {{ link.name }}
              </div>
              <p v-if="link.description" class="mt-1 line-clamp-2 leading-snug text-white">
                {{ link.description }}
              </p>
            </div>
            <div v-if="showEditIcons" class="z-40 my-auto inline-flex h-fit rounded-lg bg-white/80 p-2">
              <slot :index="index" :link="link" :links="links" name="editIconsBg" />
            </div>
          </NavigationMenuLink>

          <NavigationMenuLink v-else :as="linkComponent" prefetch
            class="my-1 flex h-fit items-center rounded-md text-left leading-none transition-colors" :href="link.url"
            :class="linkClasses(link)" @click="handleCloseMenu">
            <div class="flex w-full items-center justify-between gap-2">
              <div class="h-fit">
                <div class="inline-flex items-center" :class="textClasses(link)">
                  <Icon v-if="link.icon" :icon="`fluent:${link.icon}`" class="mr-2 size-5" />
                  {{ link.name }}
                  <NTag size="tiny" round borderless v-if="link.small_text" type="error" class="ml-2">
                    {{ link.small_text }}
                  </NTag>
                </div>
                <p v-if="link.description" class="mt-1 line-clamp-2 text-sm leading-snug text-zinc-500/90">
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
import { Icon } from "@iconify/vue";
import {
  NavigationMenuContent,
  NavigationMenuLink,
} from '@/Components/ui/navigation-menu';
import SmartLink from '../SmartLink.vue';

interface Link {
  name: string;
  url?: string;
  icon?: string;
  image?: string;
  description?: string;
  type?: string;
}

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
  link: {
    textClass: 'hover:underline focus:underline',
    blockClass: 'py-1 px-2.5 my-0.5 hover:bg-transparent focus:bg-transparent hover:underline',
  },
  'block-link': {
    textClass: 'no-underline',
    blockClass: 'p-2 hover:bg-zinc-100 my-0.5 focus:bg-zinc-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800',
  },
  'category-link': {
    textClass: 'no-underline',
    blockClass: 'p-2.5 font-bold my-1 hover:bg-zinc-100 focus:bg-zinc-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800 not-first:mt-2' 
  },
  'full-height-background-link': {
    blockClass: 'h-full',
  },
};

const rootElement = computed(() => isUsedWithoutRoot ? 'div' : NavigationMenuContent);

const ulClasses = computed(() => `grid max-lg:max-h-[calc(100dvh-20rem)] lg:max-h-[calc(100vh-10rem)] gap-3 overflow-y-auto p-4 max-lg:w-[340px] lg:w-[650px] xl:w-[800px] lg:grid-cols-${item.cols ?? 1} content-stretch`);

const linkComponent = computed(() => areLinksDisabled ? 'div' : SmartLink);

const linkClasses = (link: Link) => linkTypes[link?.type ?? 'block-link']?.blockClass;

const textClasses = (link: Link) => linkTypes[link?.type ?? 'block-link']?.textClass;

const handleCloseMenu = () => {
  emit('closeMenu');
};

</script>
