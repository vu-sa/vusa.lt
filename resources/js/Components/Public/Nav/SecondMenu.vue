<template>
  <section class="z-5 relative grid grid-cols-[min-content,_1fr] rounded-b-lg pl-12">
    <SmartLink prefetch href="/"
      class="my-auto mr-6 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-200 dark:hover:text-vusa-red">
      {{
        $page.props.tenant?.shortname
          ? $t($page.props.tenant?.shortname)
          : "VU SA"
      }}
    </SmartLink>
    <nav class="relative inline-flex items-center gap-5 overflow-hidden whitespace-nowrap py-2 text-xs">
      <MainPageLink v-for="link in $page.props.tenant?.links" :key="link?.id" :main-page-link="link" />
      <div
        class="pointer-events-none absolute right-0 top-0 h-full w-16 rounded-br-md bg-gradient-to-r from-transparent to-zinc-100 dark:to-zinc-900" />
    </nav>
  </section>
</template>

<script setup lang="ts">
import MainPageLink from "./MainPageLink.vue";
import SmartLink from "../SmartLink.vue";
</script>
