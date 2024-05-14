<template>
  <NavigationMenu v-model="modelValue" as="div">
    <div class="mr-8">
      <slot name="additional" />
    </div>
    <NavigationMenuList>
      <NavigationMenuItem v-for="item in mainNavigation" :key="item.name">
        <NavigationMenuTrigger
          class="bg-transparent hover:bg-zinc-100 dark:bg-transparent  dark:hover:bg-zinc-700 max-lg:px-1 max-lg:py-0.5 max-lg:text-xs lg:px-2 lg:py-1.5">
          {{ item.name }}
        </NavigationMenuTrigger>
        <MainNavigationMenuContent :item :for-admin-edit="false" @close-menu="closeMenu" />      
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
} from '@/Components/ShadcnVue/ui/navigation-menu'
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import MainNavigationMenuContent from './MainNavigationMenuContent.vue';

const modelValue = ref(null);

const mainNavigation = computed(() => usePage().props.mainNavigation);

function closeMenu(event: MouseEvent) {
  //event.preventDefault();
  modelValue.value = null;
}
</script>
