<template>
  <NButton
    tag="a"
    size="tiny"
    small
    text
    :href="link"
    :target="link?.includes('http') ? '_blank' : undefined"
  >
    {{ mainPageLink?.text }}
  </NButton>
</template>

<script setup lang="ts">
import { NButton } from "naive-ui";
import { computed } from "vue";
// import { router, usePage } from "@inertiajs/vue3";

const props = defineProps<{ mainPageLink: App.Entities.MainPage | null }>();

const link = computed(() => {
  let link = props.mainPageLink?.link;

  if (link === undefined) {
    return null;
  }

  // if link is null, return nothing
  if (link === null || link.includes("http")) {
    return link;
  }

  // if subdomain different than padalinys alias, use window.location.href
  // because inertiajs response is not normal

  // if first char is /, remove it
  if (link.charAt(0) === "/") {
    link = link.substring(1);
  }

  // if starts with lt or en, remove it
  if (link.startsWith("lt") || link.startsWith("en")) {
    link = link.substring(3);
  }

  return link;

  // router.visit(
  //   route("page", {
  //     lang: usePage().props.app.locale,
  //     subdomain: usePage().props.padalinys?.subdomain ?? "www",
  //     permalink: link,
  //   }),
  // );
});
</script>
