<template>
  <div class="fixed bottom-0 z-50 w-screen">
    <div
      class="mx-auto mb-12 flex h-fit w-fit items-center gap-4 rounded-md bg-white px-4 py-3 shadow-md dark:bg-zinc-700"
    >
      <p
        class="prose-sm prose flex h-fit items-center gap-4 px-4 dark:prose-invert"
      >
        <NIcon :component="Cookies24Regular"></NIcon>

        <span>
          Naudojame tik seanso slapukus, kurie yra privalomi tinklalapio
          veikimui.
        </span>
      </p>
      <NButton size="small" round secondary :tag="Link" href="/privatumas"
        >Privatumo politika</NButton
      >

      <NButton
        size="small"
        round
        secondary
        type="warning"
        @click="$emit('okayCookieConsent')"
        >Gerai</NButton
      >
    </div>
  </div>
</template>

<script setup lang="ts">
import { Cookies24Regular } from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";

defineEmits(["okayCookieConsent"]);
</script>
