<template>
  <!-- <NConfigProvider :theme-overrides="themeOverrides"> -->
  <!-- <NMessageProvider> -->
  <Head :title="title" />
  <MetaIcons />

  <FullWindow>
    <!-- Main Navigation -->
    <MainNavigation>
      <a class="w-full" href="/">
        <AppLogo class="mx-auto my-2" />
      </a>
      <div class="bg-gray-50 w-full h-full rounded-b-xl">
        <MenuButton :menuContent="['dashboard']">
          <template #icon>
            <HomeIcon class="mx-auto w-7 h-7 mb-1" />
          </template>
          Pradžia
        </MenuButton>
        <MenuButton
          :menuContent="['pages.*', 'news.*', 'mainPage.*', 'banners.*']"
        >
          <template #icon
            ><DocumentTextIcon class="mx-auto w-7 h-7 mb-1"
          /></template>
          Turinys
        </MenuButton>
        <MenuButton :menuContent="['users.*']">
          <template #icon><UserIcon class="mx-auto w-7 h-7 mb-1" /></template>
          Kontaktai
        </MenuButton>
        <MenuButton :menuContent="['navigation.*']">
          <template #icon
            ><MenuAlt2Icon class="mx-auto w-7 h-7 mb-1"
          /></template>
          Navigacija
        </MenuButton>
        <MenuButton :menuContent="['calendar.*', 'agenda.*']">
          <template #icon
            ><CalendarIcon class="mx-auto w-7 h-7 mb-1"
          /></template>
          Kalendorius
        </MenuButton>
        <MenuButton :menuContent="['files.*']">
          <template #icon><FolderIcon class="mx-auto w-7 h-7 mb-1" /></template>
          Failų tvarkyklė
        </MenuButton>
        <MenuButton
          :menuContent="['saziningaiExams.*', 'saziningaiObservers.*']"
        >
          <template #icon
            ><BookOpenIcon class="mx-auto w-7 h-7 mb-1"
          /></template>
          Sažiningai
        </MenuButton>
      </div>
    </MainNavigation>
    <!-- Main Navigation End -->
    <!-- Page Content -->
    <PageContent :createURL="createURL">
      <template #header
        ><slot name="header">{{ title }}</slot></template
      >

      <template #aside-header>
        <slot name="aside-header"></slot>
      </template>

      <div class="col-span-2 ml-12 mt-4 mb-5"></div>
      <transition name="fade">
        <main v-if="animated" class="col-span-4">
          <slot></slot>
        </main>
      </transition>
      <!-- Aside Navigation -->
      <AsideNavigation>
        <slot name="aside-navigation-options"></slot>
      </AsideNavigation>
      <!-- Aside Navigation End -->
    </PageContent>
    <!-- Page Content End -->
  </FullWindow>
  <!-- </NMessageProvider> -->
  <!-- </NConfigProvider> -->
</template>

<script setup>
import { onMounted, ref } from "vue";
import AppLogo from "@/Components/AppLogo.vue";
import JetBanner from "@/Jetstream/Banner.vue";
import JetDropdown from "@/Jetstream/Dropdown.vue";
import JetDropdownLink from "@/Jetstream/DropdownLink.vue";
import JetNavLink from "@/Jetstream/NavLink.vue";
import JetResponsiveNavLink from "@/Jetstream/ResponsiveNavLink.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import {
  HomeIcon,
  BookOpenIcon,
  DocumentTextIcon,
  UserIcon,
  MenuAlt2Icon,
  CalendarIcon,
  FolderIcon,
} from "@heroicons/vue/outline";
import MetaIcons from "@/Components/MetaIcons.vue";
import MenuButton from "@/Components/Admin/MenuButton.vue";
import MainNavigation from "@/Layouts/Partials/MainNavigation.vue";
import FullWindow from "@/Layouts/Partials/FullWindow.vue";
import PageContent from "@/Layouts/Partials/PageContent.vue";
import AsideNavigation from "@/Layouts/Partials/AsideNavigation.vue";
import { NConfigProvider, NAlert } from "naive-ui";

const props = defineProps({
  title: String,
  createURL: String,
});

// const themeOverrides = {
//   common: {
//     baseColor: "#fafafa",
//     },
// };

const animated = ref(false);

onMounted(() => {
  animated.value = true;
});

const logout = () => {
  Inertia.post(route("logout"));
};
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