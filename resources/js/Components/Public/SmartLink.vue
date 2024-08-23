<template>
  <component
    :is="useInertiaRouter ? Link : 'a'"
    v-if="href"
    :href="href"
    :target="target ?? useInertiaRouter ? undefined : '_blank'"
  >
    <slot />
  </component>
  <span v-else>
    <slot />
  </span>
</template>

<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps<{
  href?: string | null;
  target?: string;
}>();

const getSubdomainFromHrefOrPath = (href: string) => {
  // sometimes href: https://www.vusa.lt/lt/nuorodos... sometimes path: /lt/nuorodos...

  const subdomain = href.split("/")[2].split(".")[0];

  return subdomain;
};

const useInertiaRouter = computed(() => {
  // 1. Check if first part is http
  if (!props.href?.startsWith("http")) {
    return true;
  }


  // 2. Check if hostname ends in vusa.lt or vusa.test or other ending (???)
  const hostname = window.location.hostname;

  if (
    !hostname.endsWith(
      usePage().props.app.url.split("://")[1].split(".").slice(1).join("."),
    )
  ) {
    return false;
  }

  // 3. Check if external link
  if (!props.href.startsWith(window.location.protocol + "//" + window.location.hostname)) {
    return false;
  }

  // 4. This checks if the subdomain matches, if not, don't use inertia router
  const linkSubdomain = getSubdomainFromHrefOrPath(props.href);

  return hostname.split(".")[0] === linkSubdomain;
});
</script>
