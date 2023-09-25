<template>
  <component
    :is="useInertiaRouter ? Link : 'a'"
    :href="href"
    :target="target ?? useInertiaRouter ? undefined : '_blank'"
  >
    <slot />
  </component>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps<{
  href: string;
  target?: string;
}>();

const getSubdomainFromHrefOrPath = (href: string) => {
  // sometimes href: https://www.vusa.lt/lt/nuorodos... sometimes path: /lt/nuorodos...

  const subdomain = href.split("/")[2].split(".")[0];

  return subdomain;
};

const useInertiaRouter = computed(() => {
  // if link is null, return nothing

  // check if first part is http
  if (!props.href?.startsWith("http")) {
    return true;
  }

  const hostnameSubdomain = window.location.hostname.split(".")[0];
  const linkSubdomain = getSubdomainFromHrefOrPath(props.href);

  return hostnameSubdomain === linkSubdomain;
});
</script>
