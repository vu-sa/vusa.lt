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

<script setup>
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { NIcon } from "naive-ui";
import { computed, ref } from "vue";

const props = defineProps(["menuContent"]);

const menuButtonIndex = computed(() => {
  return _.replace(props.menuContent[0], "*", "index");
});

const isCurrentRoute = computed(() => {
  let value = false;

  while (!value) {
    props.menuContent.forEach((routeValue) => {
      value = route().current(routeValue) || value;
      if (value) {
        return;
      }
    });
    return value;
  }
});
</script>
