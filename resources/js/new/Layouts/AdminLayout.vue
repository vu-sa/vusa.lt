<template>
  <Head :title="title" />
  <MetaIcons />

  <FullWindow>
    <!-- Main Navigation -->
    <MainNavigation>
      <a class="w-full" href="/">
        <AppLogo class="mx-auto my-2" />
      </a>
      <div class="bg-gray-50 w-full h-full rounded-b-xl">
        <MenuButton menuButtonRoute="dashboard">
          <template #icon>
            <HomeIcon class="mx-auto w-7 h-7 mb-1" />
          </template>
          Dashboard
        </MenuButton>
        <MenuButton menuButtonRoute="telescope">
          <template #icon
            ><NewspaperIcon class="mx-auto w-7 h-7 mb-1"
          /></template>
          Articles
        </MenuButton>
      </div>
    </MainNavigation>
    <!-- Main Navigation End -->
    <!-- Page Content -->
    <PageContent>
      <template #header
        ><slot name="header">{{ title }}</slot></template
      >
      <div class="col-span-2 ml-12 mt-4 mb-5"></div>
      <main class="bg-white p-8 rounded-xl drop-shadow-md col-span-4">
        <slot></slot>
      </main>
      <!-- Aside Navigation -->
      <AsideNavigation>
        
      </AsideNavigation>
      <!-- Aside Navigation End -->
    </PageContent>
    <!-- Page Content End -->
  </FullWindow>
</template>

<script setup>
import { ref } from "vue";
import AppLogo from "@/Components/AppLogo.vue";
import JetBanner from "@/Jetstream/Banner.vue";
import JetDropdown from "@/Jetstream/Dropdown.vue";
import JetDropdownLink from "@/Jetstream/DropdownLink.vue";
import JetNavLink from "@/Jetstream/NavLink.vue";
import JetResponsiveNavLink from "@/Jetstream/ResponsiveNavLink.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { HomeIcon, NewspaperIcon } from "@heroicons/vue/outline";
import MetaIcons from "@/Components/MetaIcons.vue";
import MenuButton from "@/Components/Admin/MenuButton.vue";
import MainNavigation from "@/Layouts/Partials/MainNavigation.vue";
import FullWindow from "@/Layouts/Partials/FullWindow.vue";
import PageContent from "@/Layouts/Partials/PageContent.vue";
import AsideNavigation from "@/Layouts/Partials/AsideNavigation.vue";

const props = defineProps({
  title: String,
});

const logout = () => {
  Inertia.post(route("logout"));
};
</script>
