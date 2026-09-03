<template>
  <!-- `static` overrides the primitive's own `relative`. The mega-menu viewport is positioned
       `absolute` against the nearest positioned ancestor; leaving this relative anchored the
       panel to the nav items themselves, so it hugged the trigger instead of spanning the bar.
       With this static, the header container (which is `relative`) becomes the anchor. -->
  <!-- `delay-duration="0"`: reka waits 200ms on hover before opening, which on a bar this wide
       reads as the menu being slow rather than deliberate. The skip window stays short so moving
       between triggers does not re-animate each time. -->
  <NavigationMenu
    v-model="activeMenuItem"
    as="div"
    class="static"
    :delay-duration="0"
    :skip-delay-duration="200"
  >
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
