<template>
  <NavigationMenu v-model="activeMenuItem" as="div">
    <div class="mr-8">
      <slot name="additional" />
    </div>
    <NavigationMenuList>
      <NavigationMenuItem class="list-none" v-for="item in mainNavigation" :key="item.name">
        <NavigationMenuTrigger
          class="bg-transparent cursor-pointer hover:bg-zinc-100 dark:bg-transparent dark:hover:bg-zinc-700 max-lg:px-1 max-lg:py-0.5 max-lg:text-xs lg:px-2 lg:py-1.5">
          {{ item.name }}
        </NavigationMenuTrigger>
        <MainNavigationMenuContent :item @close-menu="closeMenu" />
      </NavigationMenuItem>
    </NavigationMenuList>
  </NavigationMenu>
</template>

<script setup lang="ts">
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuList,
  NavigationMenuTrigger,
} from '@/Components/ui/navigation-menu'
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import MainNavigationMenuContent from './MainNavigationMenuContent.vue';

const activeMenuItem = ref(undefined);

const mainNavigation = computed(() => usePage().props.mainNavigation);

function closeMenu() {
  activeMenuItem.value = undefined;
}
</script>
