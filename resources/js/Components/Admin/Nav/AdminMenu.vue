<template>
  <NMenu
    v-model:value="activeKey"
    :collapsed="collapsed"
    :collapsed-width="64"
    :collapsed-icon-size="28"
    :options="menuOptions"
  />
</template>

<script setup lang="ts">
import {
  CalendarLtr48Regular,
  Folder48Regular,
  Home48Regular,
  Navigation24Regular,
  Person48Regular,
  SlideText48Regular,
} from "@vicons/fluent";
import { Component, computed, h, ref } from "vue";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { NIcon, NMenu } from "naive-ui";
import route from "ziggy-js";

defineProps<{
  collapsed: boolean;
}>();

const { can } = usePage<InertiaProps>().props.value;
const activeKey = ref("");

// set active key with a switch
const setActiveKey = (route: string) => {
  // delimit route with .
  const routeParts = route.split(".");
  if (routeParts[0] === "dashboard") {
    activeKey.value = "dashboard";
  }
  if (["pages", "news", "mainPage", "banners"].includes(routeParts[0])) {
    activeKey.value = "content";
  }
  if (["dutyInstitutions", "duties", "users"].includes(routeParts[0])) {
    activeKey.value = "contacts";
  }
  if (routeParts[0] === "navigation") {
    activeKey.value = "navigation";
  }
  if (routeParts[0] === "calendar") {
    activeKey.value = "calendar";
  }
  if (["saziningaiExams", "saziningaiExamPeople"].includes(routeParts[0])) {
    activeKey.value = "saziningai";
  }
  if (routeParts[0] === "files") {
    activeKey.value = "files";
  }
};

setActiveKey(route().current());

const renderIcon = (icon: Component) => {
  return () => h(NIcon, null, { default: () => h(icon) });
};

const menuOptions = computed(() => [
  {
    label: () => h(Link, { href: route("dashboard") }, () => "Pradinis"),
    key: "dashboard",
    icon: renderIcon(Home48Regular),
  },
  {
    label: () => h(Link, { href: route("pages.index") }, () => "Turinys"),
    key: "content",
    icon: renderIcon(SlideText48Regular),
    show: can.content,
  },
  {
    label: () =>
      h(Link, { href: route("dutyInstitutions.index") }, () => "Kontaktai"),
    key: "contacts",
    icon: renderIcon(Person48Regular),
    show: can.users,
  },
  {
    label: () =>
      h(Link, { href: route("navigation.index") }, () => "Navigacija"),
    key: "navigation",
    icon: renderIcon(Navigation24Regular),
    show: can.navigation,
  },
  {
    label: () =>
      h(Link, { href: route("calendar.index") }, () => "Kalendorius"),
    key: "calendar",
    icon: renderIcon(CalendarLtr48Regular),
    show: can.calendar,
  },
  {
    label: () =>
      h(Link, { href: route("saziningaiExams.index") }, () => "Sąžiningai"),
    key: "saziningai",
    icon: renderIcon(Person48Regular),
    show: can.saziningai,
  },
  {
    label: () => h(Link, { href: route("files.index") }, () => "Failai"),
    key: "files",
    icon: renderIcon(Folder48Regular),
    show: can.files,
  },
]);
</script>
