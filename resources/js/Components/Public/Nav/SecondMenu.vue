<template>
  <section class="z-5 relative grid grid-cols-[min-content__1fr] rounded-b-2xl pl-12">
    <SmartLink prefetch href="/"
      class="my-auto mr-6 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-200 dark:hover:text-vusa-red">
      {{
        $page.props.tenant?.shortname
          ? $t($page.props.tenant?.shortname)
          : "VU SA"
      }}
    </SmartLink>
    <nav class="grid grid-cols-[1fr_auto_auto] items-center gap-5 whitespace-nowrap py-2 text-xs">
      <div class="relative flex items-center gap-4 overflow-hidden w-full">
        <QuickLink v-for="link in $page.props.tenant?.links" :key="link?.id" :quick-link="link" />
        <div
          class="absolute pointer-events-none right-0 h-8 overflow-hidden bg-linear-to-br from-transparent to-zinc-100 dark:from-transparent dark:to-[rgb(29,_29,_30)] w-10" />
      </div>
      <div class="inline-flex gap-1">
        <NDropdown :options="dropdownOptions" @select="handleSelect">
          <NButton text size="tiny" text-color="#767875">
            <template #icon>
              <IFluentLineHorizontal1Dot20Filled />
            </template>
          </NButton>
        </NDropdown>
      </div>
      <div class="ml-auto flex pr-4 gap-4 items-center">
        <SearchButton size="tiny">
          {{ $t('Paieška') }}
        </SearchButton>
        <a href="/login">
          <NButton text size="tiny">
            <template #icon>
              <IFluentPerson24Filled v-if="$page.props.auth?.user" />
              <IFluentPerson24Regular v-else />
            </template>
            {{ $page.props.auth?.user ? $page.props.auth.user?.name : $t('auth.login') }}
          </NButton>
        </a>
      </div>
    </nav>
  </section>
</template>

<script setup lang="ts">
import QuickLink from "./QuickLink.vue";
import SmartLink from "../SmartLink.vue";
import SearchButton from "./SearchButton.vue";
import { usePage } from "@inertiajs/vue3";

const dropdownOptions = usePage().props.tenant?.links.map((link) => ({
  label: link?.text,
  key: link?.link,
}));

const handleSelect = (value: string) => {
  window.location.href = value;
};
</script>
