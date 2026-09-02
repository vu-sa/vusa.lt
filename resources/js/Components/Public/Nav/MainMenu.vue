<template>
  <NavigationMenu v-model="activeMenuItem" as="div">
    <NavigationMenuList>
      <NavigationMenuItem v-for="item in mainNavigation" :key="item.name" class="list-none">
        <!-- Uppercase, `whitespace-nowrap shrink-0`: the primary nav must never wrap to a second
             line. If labels stop fitting, shorten them or raise the desktop breakpoint. Colour and
             hover come from navigationMenuTriggerStyle, which is tokenised. -->
        <NavigationMenuTrigger
          class="shrink-0 cursor-pointer whitespace-nowrap text-sm font-bold uppercase tracking-wide lg:px-3 lg:py-2">
          {{ item.name }}
        </NavigationMenuTrigger>
        <MainNavigationMenuContent :item @close-menu="closeMenu" />
      </NavigationMenuItem>
    </NavigationMenuList>
  </NavigationMenu>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import MainNavigationMenuContent from './MainNavigationMenuContent.vue';

import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuList,
  NavigationMenuTrigger,
} from '@/Components/ui/navigation-menu';

const activeMenuItem = ref(undefined);

const mainNavigation = computed(() => usePage().props.mainNavigation);

function closeMenu() {
  activeMenuItem.value = undefined;
}
</script>
