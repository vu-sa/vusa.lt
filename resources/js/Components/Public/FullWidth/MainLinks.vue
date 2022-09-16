<template>
  <div class="mx-8 mb-8 lg:mx-16 lg:px-16">
    <h2 class="mb-4 dark:text-white">{{ $t("Pagrindinės nuorodos") }}:</h2>
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
import { Inertia } from "@inertiajs/inertia";
import { NButton } from "naive-ui";
import { usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

defineProps<{ mainPage: Array<App.Models.MainPage> }>();

const goToLink = (link: string | null) => {
  // if link is null, return nothing
  if (link === null) {
    return;
  }

  // check if link is external
  let padalinysAlias = usePage().props.value.alias;
  if (link.includes("http")) {
    window.open(link, "_blank");
    return;
  }

  // if has /lt/, truncate it
  if (link.includes("/lt/")) {
    link = link.replace("/lt/", "");
  }

  if (padalinysAlias === "vusa") {
    // if first char is /, remove it
    if (link.charAt(0) === "/") {
      link = link.substring(1);
    }
    Inertia.visit(
      route("main.page", {
        lang: usePage().props.value.locale,
        permalink: link,
      })
    );
  } else {
    Inertia.visit(
      route("padalinys.page", {
        lang: usePage().props.value.locale,
        permalink: link,
        padalinys: padalinysAlias,
      })
    );
  }
};
</script>
