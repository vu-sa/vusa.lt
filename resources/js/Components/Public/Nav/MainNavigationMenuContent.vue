<template>
  <component :is="forAdminEdit ? 'div' : NavigationMenuContent" class="dark:bg-zinc-900">
    <ul
      :class="`grid max-md:max-h-[calc(100dvh-20rem)] md:max-h-[calc(100vh-10rem)] gap-3 overflow-y-auto p-4 max-md:w-[340px] md:w-[450px] lg:w-[650px] xl:w-[800px] lg:grid-cols-${item.cols ?? 1} content-stretch`">
      <li v-for="links in item.links" :key="item.id">
        <template v-for="(link, index) in links" :key="link.name">
          <div v-if="link.type === 'divider'" class="my-3 border-t border-zinc-200">
            <slot v-if="showEditIcons" :index :link :links name="editIconsDivider" />
          </div>

          <NavigationMenuLink v-else-if="link.image" :as="forAdminEdit ? 'div' : SmartLink"
            class="relative mb-2 flex rounded-md bg-zinc-900 transition-colors last:mb-0 hover:bg-zinc-800"
            :href="link.url" :class="link?.type === 'full-height-background-link' ? linkTypes[link.type].blockClass : null" @click="$emit('closeMenu')">
            <!-- <div class="h-24" /> -->
            <img class="absolute left-0 top-0 size-full rounded-md object-cover opacity-25 contrast-150"
              :src="link.image" alt="Background image">
            <div class="z-50 inline-block h-fit self-end p-4 align-bottom">
              <div class="text-lg font-black leading-tight text-zinc-50">
                {{ link.name }}
              </div>
              <p v-if="link.description" class="mt-2 line-clamp-2 leading-snug text-white">
                {{ link.description }}
              </p>
            </div>
            <div v-if="showEditIcons" class="z-40 my-auto inline-flex h-fit rounded-lg bg-white/80 p-2">
            <slot :index :link :links name="editIconsBg" />
            </div>
          </NavigationMenuLink>
          <NavigationMenuLink v-else :as="forAdminEdit ? 'div' : SmartLink"
            class="my-1 flex h-fit text-left items-center rounded-md leading-none transition-colors" :href="link.url"
            :class="[linkTypes[link?.type ?? 'block-link']?.blockClass]" @click="$emit('closeMenu')">
            <div class="flex w-full items-center justify-between gap-2">
              <div class="h-fit">
                <div class="inline-flex items-center" :class="[linkTypes[link?.type ?? 'block-link']?.textClass]">
                  <Icon v-if="link.icon" :icon="`fluent:${link.icon}`" class="mr-2 size-5" />
                  {{ link.name }}
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
import { Icon } from "@iconify/vue"
import {
  NavigationMenuContent,
  NavigationMenuLink,
} from '@/Components/ShadcnVue/ui/navigation-menu'

import SmartLink from '../SmartLink.vue';

defineProps<{
  forAdminEdit?: boolean;
  item: Record<string, any>;
  showEditIcons?: boolean;
}>()

defineEmits(['closeMenu', 'moveUp', 'moveDown']);

const linkTypes = {
  'link': {
    'textClass': 'hover:underline focus:underline',
    'blockClass': 'py-1 px-2.5 hover:bg-transparent focus:bg-transparent hover:underline',
  },
  'block-link': {
    'textClass': 'no-underline',
    'blockClass': 'p-2.5 hover:bg-zinc-100 focus:bg-zinc-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800',
  },
  'category-link': {
    'textClass': 'no-underline',
    'blockClass': 'p-2.5 font-bold hover:bg-zinc-100 focus:bg-zinc-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800',
  },
  'full-height-background-link': {
    'blockClass': 'h-full',
  },
};
</script>
