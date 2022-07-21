<template>
  <Link
    :href="route(menuButtonIndex)"
    class="block p-2 md:p-3 hover:bg-gray-100 last:hover:rounded-b-xl duration-200 w-full"
    :class="
      isCurrentRoute
        ? ['stroke-red-800', 'text-red-800', 'hover:text-red-900']
        : [
            'stroke-gray-600',
            'hover:stroke-gray-800',
            'text-gray-600',
            'hover:text-gray-800',
          ]
    "
  >
    <div class="admin-navigation-icon">
      <NIcon size="24"><slot name="icon"></slot></NIcon>
    </div>
    <div class="w-full text-xs lg:text-base"><slot></slot></div>
  </Link>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { NIcon } from "naive-ui";
import { computed } from "vue";
import { replace } from "lodash";
import route from "ziggy-js";

const props = defineProps<{ menuContent: string[] }>();

// This chooses the first element in the array as the default route for the button

const menuButtonIndex = computed(() => {
  return replace(props.menuContent[0], "*", "index");
});

/**
 * This checks for the route in a array of routes, given in the AdminLayout.vue
 * If an route in the array is current route, then the button is active and stylized
 */

const isCurrentRoute = computed(() => {
  props.menuContent.forEach((routeValue) => {
    if (route().current(routeValue)) {
      return true;
    }
  });
  return false;
});
</script>
