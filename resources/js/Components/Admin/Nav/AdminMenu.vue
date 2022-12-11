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
  CalendarLtr24Regular,
  Folder24Regular,
  Home24Regular,
  Navigation24Regular,
  Notebook24Regular,
  People24Regular,
  PeopleSearch24Regular,
  Person24Regular,
  Settings24Regular,
  SlideText24Regular,
  Sparkle20Filled,
  Sparkle20Regular,
  TabDesktopNewPage20Regular,
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
const setActiveKey = (route: string | undefined) => {
  // delimit route with .
  if (route === undefined) return;

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
    icon: renderIcon(Home24Regular),
  },
  {
    label: "Veikla",
    key: "doings",
    icon: renderIcon(Sparkle20Regular),
    // make children - klausimai and veiklos
    children: [
      {
        label: () =>
          h(Link, { href: route("questions.index") }, () => "Klausimai"),
        key: "questions",
        // show: can.questions,
      },
      {
        // question groups
        label: () =>
          h(
            Link,
            { href: route("questionGroups.index") },
            () => "Klausimų grupės"
          ),
        key: "questionGroups",
        // show: can.questionGroups,
      },
      {
        label: () => h(Link, { href: route("doings.index") }, () => "Veiklos"),
        key: "doings",
        // show: can.doings,
      },
    ],
  },
  {
    label: "Žmonės",
    key: "contacts",
    icon: renderIcon(Person24Regular),
    show: can.users || can.dutyInstitutions || can.duties,
    children: [
      {
        label: () =>
          h(
            Link,
            { href: route("dutyInstitutions.index") },
            () => "Institucijos"
          ),
        key: "dutyInstitutions",
        show: can.dutyInstitutions,
      },
      {
        label: () => h(Link, { href: route("duties.index") }, () => "Pareigos"),
        key: "duties",
        show: can.duties,
      },
      {
        label: () => h(Link, { href: route("users.index") }, () => "Kontaktai"),
        key: "users",
        show: can.users,
      },
    ],
  },
  {
    label: "vusa.lt",
    key: "content",
    icon: renderIcon(TabDesktopNewPage20Regular),
    show: can.content,
    children: [
      {
        label: () => h(Link, { href: route("pages.index") }, () => "Puslapiai"),
        key: "pages",
        show: can.pages,
      },
      {
        label: () => h(Link, { href: route("news.index") }, () => "Naujienos"),
        key: "news",
        show: can.news,
      },
      {
        label: () =>
          h(
            Link,
            { href: route("mainPage.index") },
            () => "Pradinio puslapio mygtukai"
          ),
        key: "mainPage",
        show: can.mainPage,
      },
      {
        label: () =>
          h(Link, { href: route("banners.index") }, () => "Baneriai"),
        key: "banners",
        show: can.banners,
      },
      {
        label: () =>
          h(Link, { href: route("navigation.index") }, () => "Navigacija"),
        key: "navigation",
        // icon: renderIcon(Navigation24Regular),
        show: can.navigation,
      },
      {
        label: () =>
          h(Link, { href: route("calendar.index") }, () => "Kalendorius"),
        key: "calendar",
        // icon: renderIcon(CalendarLtr24Regular),
        show: can.calendar,
      },
      {
        label: () => h(Link, { href: route("files.index") }, () => "Failai"),
        key: "files",
        // icon: renderIcon(Folder24Regular),
        show: can.files,
      },
    ],
  },
  {
    label: "Registracijos",
    key: "registrations",
    icon: renderIcon(Notebook24Regular),
    show: can.content || can.saziningai,
    children: [
      {
        label: () =>
          h(Link, { href: route("saziningaiExams.index") }, () => "Sąžiningai"),
        key: "saziningai",
        icon: renderIcon(PeopleSearch24Regular),
        show: can.saziningai,
      },
      {
        label: () =>
          h(
            Link,
            { href: route("registrationForms.show", 2) },
            () => "Narių registracija"
          ),
        key: "memberRegister",
        icon: renderIcon(People24Regular),
        show: can.content,
      },
    ],
  },
  {
    label: "Nustatymai",
    key: "settings",
    icon: renderIcon(Settings24Regular),
    children: [
      {
        label: () => h(Link, { href: route("types.index") }, () => "Tipai"),
        key: "types",
      },
    ],
    show: can.settings,
  },
]);
</script>
