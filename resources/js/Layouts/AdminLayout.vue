<template>
  <!-- <NConfigProvider :theme-overrides="themeOverrides"> -->

  <Head :title="title" />
  <MetaIcons />

  <div class="min-h-screen grid grid-cols-9 bg-gray-200 px-4 pb-4">
    <!-- Main Navigation -->
    <nav
      class="md:mr-4 shadow-lg md:rounded-xl col-span-1 md:sticky top-0 left-0 md:left-auto md:top-4 w-screen md:w-auto mb-auto bg-gray-50 transition fixed flex md:flex-col items-center text-center text-gray-800 z-20 overflow-x-scroll md:overflow-auto justify-evenly"
    >
      <a class="w-full md:bg-white md:rounded-t-lg" href="/">
        <AppLogo class="mx-auto my-2" />
      </a>
      <MenuButton :menu-content="['dashboard']">
        <template #icon> <Home48Regular /> </template>
        Pradžia
      </MenuButton>
      <MenuButton
        v-if="can.content"
        :menu-content="['pages.*', 'news.*', 'mainPage.*', 'banners.*']"
      >
        <template #icon>
          <SlideText48Regular />
        </template>
        Turinys
      </MenuButton>
      <MenuButton
        v-if="can.users"
        :menu-content="['dutyInstitutions.*', 'duties.*', 'users.*', 'roles.*']"
      >
        <template #icon>
          <Person48Regular />
        </template>
        Kontaktai
      </MenuButton>
      <MenuButton v-if="can.navigation" :menu-content="['navigation.*']">
        <template #icon>
          <Navigation24Regular />
        </template>
        Navigacija
      </MenuButton>
      <MenuButton
        v-if="can.calendar"
        :menu-content="['calendar.*', 'agenda.*']"
      >
        <template #icon>
          <CalendarLtr48Regular />
        </template>
        Kalendorius
      </MenuButton>
      <MenuButton v-if="can.files" :menu-content="['files.*']">
        <template #icon>
          <Folder48Regular />
        </template>
        Failų tvarkyklė
      </MenuButton>
      <MenuButton
        v-if="can.saziningai"
        :menu-content="['saziningaiExams.*', 'saziningaiExamObservers.*']"
      >
        <template #icon>
          <BookOpen48Regular />
        </template>
        Sažiningai
      </MenuButton>
      <div class="py-2">
        <NButton
          style="font-size: 8pt"
          class="hover:text-vusa-red text-gray-600"
          text
          @click="showModal = true"
        >
          v0.1.2 (2022-07-08)
        </NButton>
      </div>
      <NModal v-model:show="showModal">
        <Changelog></Changelog>
      </NModal>
    </nav>
    <!-- Main Navigation End -->
    <!-- Page Content -->
    <PageContent :create-url="createUrl">
      <template #header>
        <slot name="header">{{ title }}</slot>
      </template>

      <template #aside-header>
        <slot name="aside-header"></slot>
      </template>

      <div class="col-span-2 ml-12 mt-4 mb-5"></div>
      <Transition name="fade" appear>
        <main class="md:col-span-4 col-span-full">
          <slot />
        </main>
      </Transition>
      <!-- Aside Navigation -->
      <AsideNavigation>
        <slot name="aside-navigation-options"></slot>
      </AsideNavigation>
      <!-- Aside Navigation End -->
    </PageContent>
    <!-- Page Content End -->
  </div>
  <!-- </NConfigProvider> -->
</template>

<script setup lang="ts">
import {
  BookOpen48Regular,
  CalendarLtr48Regular,
  Folder48Regular,
  Home48Regular,
  Navigation24Regular,
  Person48Regular,
  SlideText48Regular,
} from "@vicons/fluent";
import { Head, usePage } from "@inertiajs/inertia-vue3";
import { NButton, NModal } from "naive-ui";
import { ref } from "vue";
import AppLogo from "@/Components/AppLogo.vue";
import AsideNavigation from "@/Layouts/Partials/Admin/AsideNavigation.vue";
import Changelog from "@/Components/Admin/Misc/ChangelogCard.vue";
import MenuButton from "@/Components/Admin/MenuButton.vue";
import MetaIcons from "@/Components/MetaIcons.vue";
import PageContent from "@/Layouts/Partials/Admin/PageContent.vue";

defineProps<{
  title: string;
  createUrl?: string | null;
}>();

const { can } = usePage<InertiaProps>().props.value;

const showModal = ref(false);
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
