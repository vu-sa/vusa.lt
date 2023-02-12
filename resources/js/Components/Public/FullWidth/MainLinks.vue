<template>
  <div class="mx-8 mb-8 lg:mx-16 lg:px-16">
    <h2 class="mb-4">{{ $t("Pagrindinės nuorodos") }}:</h2>
    <!-- TODO: In dev, buttons from other padalinys, don't return normal response from parent -->
    <div class="flex flex-wrap gap-2 overflow-x-auto">
      <NButton
        v-for="item in mainPage"
        :key="item.id"
        secondary
        round
        @click="goToLink(item.link)"
      >
        {{ item.text }}
      </NButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { NButton } from "naive-ui";
import { router, usePage } from "@inertiajs/vue3";

defineProps<{ mainPage: Array<App.Entities.MainPage> }>();

const goToLink = (link: string | null) => {
  // if link is null, return nothing
  if (link === null) {
    return;
  }

  // check if link is external
  if (link.includes("http")) {
    window.open(link, "_blank");
    return;
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

  router.visit(
    route("page", {
      lang: usePage().props.app.locale,
      padalinys:
        usePage().props.alias === "vusa"
          ? "www"
          : usePage().props.alias ?? "www",
      permalink: link,
    })
  );
};
</script>
