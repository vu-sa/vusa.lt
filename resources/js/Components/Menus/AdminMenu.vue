<template>
  <NMenu
    v-model:value="activeKey"
    :collapsed="collapsed"
    :collapsed-width="64"
    :collapsed-icon-size="28"
    :options="menuOptions"
  />
</template>

<script setup lang="tsx">
import {
  Flowchart20Regular,
  Folder24Regular,
  HatGraduation24Regular,
  Home24Regular,
  Notebook24Regular,
  Settings24Regular,
  TabDesktopNewPage20Regular,
} from "@vicons/fluent";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { NIcon, NMenu } from "naive-ui";
import { computed, ref } from "vue";

import Icons from "@/Types/Icons/regular";

defineProps<{
  collapsed: boolean;
}>();

const { auth } = usePage().props.value;
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
  if (["institutions", "duties", "users"].includes(routeParts[0])) {
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

const menuOptions = computed(() => [
  {
    label: () => {
      return <Link href={route("dashboard")}>Pradinis</Link>;
    },
    key: "dashboard",
    icon: () => {
      return <NIcon component={Home24Regular}></NIcon>;
    },
  },

  {
    label: "Atstovavimas",
    key: "doings",
    icon: () => {
      return <NIcon component={HatGraduation24Regular}></NIcon>;
    },
    show: auth?.can.institutions,
    children: [
      {
        label: () => {
          return <Link href={route("goals.index")}>Tikslai</Link>;
        },
        key: "goals",
        icon: () => {
          return <NIcon component={Icons.GOAL}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("goalGroups.index")}>Tikslų grupės</Link>;
        },
        key: "goalGroups",
        icon: () => {
          return <NIcon component={Icons.GOAL_GROUP}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("doings.index")}>Veiksmai</Link>;
        },
        key: "doings",
        icon: () => {
          return <NIcon component={Icons.DOING}></NIcon>;
        },
      },
      {
        key: "divider",
        type: "divider",
      },
      {
        label: () => {
          return <Link href={route("institutions.index")}>Institucijos</Link>;
        },
        key: "institutions",
        icon: () => {
          return <NIcon component={Icons.INSTITUTION}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("matters.index")}>Svarstomi klausimai</Link>;
        },
        key: "matters",
        icon: () => {
          return <NIcon component={Icons.MATTER}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("meetings.index")}>Posėdžiai</Link>;
        },
        key: "meetings",
        icon: () => {
          return <NIcon component={Icons.MEETING}></NIcon>;
        },
      },
      {
        label: () => {
          return (
            <Link href={route("institutionGraph")}>Institucijų grafa</Link>
          );
        },
        key: "institutionsGraph",
        icon: () => {
          return <NIcon component={Flowchart20Regular}></NIcon>;
        },
      },
    ],
  },
  {
    label: "Žmonės",
    key: "contacts",
    icon: () => {
      return <NIcon component={Icons.USER}></NIcon>;
    },
    show: auth?.can.users || auth?.can.institutions,
    children: [
      {
        label: () => {
          return <Link href={route("users.index")}>Nariai</Link>;
        },
        key: "users",
        show: auth?.can.users,
        icon: () => {
          return <NIcon component={Icons.USER}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("duties.index")}>Pareigos</Link>;
        },
        key: "duties",
        icon: () => {
          return <NIcon component={Icons.DUTY}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("contacts.index")}>Kiti kontaktai</Link>;
        },
        key: "contacts",
        show: auth?.can.users,
        icon: () => {
          return <NIcon component={Icons.CONTACT}></NIcon>;
        },
      },
    ],
  },
  {
    label: "vusa.lt",
    key: "content",
    icon: () => {
      return <NIcon component={TabDesktopNewPage20Regular}></NIcon>;
    },
    show: auth?.can.content,
    children: [
      {
        label: () => {
          return <Link href={route("pages.index")}>Puslapiai</Link>;
        },
        key: "pages",
        show: auth?.can.pages,
        icon: () => {
          return <NIcon component={Icons.PAGE}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("news.index")}>Naujienos</Link>;
        },
        key: "news",
        show: auth?.can.news,
        icon: () => {
          return <NIcon component={Icons.NEWS}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("mainPage.index")}>Greitieji mygtukai</Link>;
        },
        key: "mainPage",
        show: auth?.can.mainPage,
        icon: () => {
          return <NIcon component={Icons.MAIN_PAGE}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("banners.index")}>Baneriai</Link>;
        },
        key: "banners",
        show: auth?.can.banners,
        icon: () => {
          return <NIcon component={Icons.BANNER}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("navigation.index")}>Navigacija</Link>;
        },
        key: "navigation",
        show: auth?.can.navigation,
        icon: () => {
          return <NIcon component={Icons.NAVIGATION}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("calendar.index")}>Kalendorius</Link>;
        },
        key: "calendar",
        show: auth?.can.calendar,
        icon: () => {
          return <NIcon component={Icons.CALENDAR}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("files.index")}>Failai</Link>;
        },
        key: "files",
        show: auth?.can.files,
        icon: () => {
          return <NIcon component={Folder24Regular}></NIcon>;
        },
      },
    ],
  },
  {
    label: "Registracijos",
    key: "registrations",
    icon: () => {
      return <NIcon component={Notebook24Regular}></NIcon>;
    },
    show: auth?.can.content || auth?.can.saziningai,
    children: [
      {
        label: () => {
          return <Link href={route("saziningaiExams.index")}>Sąžiningai</Link>;
        },
        key: "saziningai",
        icon: () => {
          return <NIcon component={Icons.SAZININGAI_EXAM}></NIcon>;
        },
        show: auth?.can.saziningai,
      },
      {
        label: () => {
          return (
            <Link href={route("registrationForms.show", 2)}>
              Narių registracija
            </Link>
          );
        },
        key: "memberRegister",
        icon: () => {
          return <NIcon component={Icons.USER}></NIcon>;
        },
        show: auth?.can.content,
      },
    ],
  },
  {
    label: "Nustatymai",
    key: "settings",
    icon: () => {
      return <NIcon component={Settings24Regular}></NIcon>;
    },
    children: [
      {
        label: () => {
          return <Link href={route("types.index")}>Tipai</Link>;
        },
        key: "types",
        icon: () => {
          return <NIcon component={Icons.TYPE}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("relationships.index")}>Ryšiai</Link>;
        },
        key: "relationships",
        icon: () => {
          return <NIcon component={Icons.RELATIONSHIP}></NIcon>;
        },
      },
      // role index
      {
        label: () => {
          return <Link href={route("roles.index")}>Rolės</Link>;
        },
        key: "roles",
        icon: () => {
          return <NIcon component={Icons.ROLE}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("permissions.index")}>Leidimai</Link>;
        },
        key: "permissions",
        icon: () => {
          return <NIcon component={Icons.PERMISSION}></NIcon>;
        },
      },
    ],
    show: auth?.can.settings,
  },
]);
</script>
