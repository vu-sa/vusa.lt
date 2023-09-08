<template>
  <component
    :is="useInertiaRouter ? Link : 'a'"
    :href="mainPageLink?.link"
    class="text-xs text-zinc-700 dark:text-zinc-300 dark:hover:text-vusa-red"
    :target="useInertiaRouter ? undefined : '_blank'"
  >
    {{ mainPageLink?.text }}
  </component>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps<{
  mainPageLink: App.Entities.MainPage | null;
}>();

const useInertiaRouter = computed(() => {
  // if link is null, return nothing
  let link = props.mainPageLink?.link;

  // if link doesn't include http, useInertia
  if (!link?.includes("http")) {
    return true;
  }

  const hostnameSubdomain = window.location.hostname.split(".")[0];
  const linkSubdomain = getSubdomainFromHrefOrPath(link);

  return hostnameSubdomain !== linkSubdomain;
});

const getSubdomainFromHrefOrPath = (url: string) => {
  // sometimes href: https://www.vusa.lt/lt/nuorodos... sometimes path: /lt/nuorodos...

  const subdomain = url.split("/")[2].split(".")[0];

  return subdomain;
};
</script>
