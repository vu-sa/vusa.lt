<template>
  <div class="fixed bottom-0 z-50 w-screen">
    <div
      class="mx-auto mb-12 flex h-fit w-fit max-w-sm flex-wrap items-center justify-center gap-4 bg-white p-4 shadow-md dark:bg-zinc-700 sm:rounded-md md:max-w-none"
    >
      <p
        class="prose-sm prose flex h-fit items-center gap-4 px-4 dark:prose-invert"
      >
        <NIcon :size="20" :component="Cookies24Regular"></NIcon>

        <span>
          Naudojame seanso slapukus, kurie yra privalomi tinklalapio veikimui.
        </span>
      </p>
      <div class="flex gap-2">
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
  </div>
</template>

<script setup lang="ts">
import { Cookies24Regular } from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";

defineEmits(["okayCookieConsent"]);
</script>
