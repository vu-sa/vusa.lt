<template>
  <NMenu
    v-model:value="activeMenuKey"
    mode="vertical"
    @update:value="handleSelectNavigation"
  />
</template>

<script setup lang="tsx">
import { NMenu } from "naive-ui";
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const emit = defineEmits<{
  (e: "close:drawer"): void;
}>();

const props = defineProps<{
  flatNavigation: any;
}>();

const activeMenuKey = ref(usePage().props.navigationItemId);

const handleSelectNavigation = (id: number) => {
  let navigationItem = props.flatNavigation.find((item: any) => item.id === id);

  if (navigationItem.url.includes("http")) {
    window.open(navigationItem.url, "_blank");
    return;
  }

  if (usePage().props.padalinys?.subdomain === "www") {
    router.visit(
      route("page", {
        lang: usePage().props.app.locale,
        subdomain: "www",
        permalink: navigationItem.url,
      }),
      {
        preserveScroll: false,
        onSuccess: () => {
          emit("close:drawer");
        },
      },
    );
    return;
  }

  window.location.href = `${usePage().props.app.url}/${
    usePage().props.app.locale
  }/${navigationItem.url}`;
};
</script>
