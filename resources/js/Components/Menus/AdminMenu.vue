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
  BookQuestionMark24Regular,
  CalendarLtr24Regular,
  DocumentMultiple24Regular,
  DocumentSettings20Regular,
  Flow20Regular,
  Flowchart20Regular,
  Folder24Regular,
  Grid24Regular,
  Home24Regular,
  ImageArrowBack24Regular,
  Navigation24Regular,
  News24Regular,
  Notebook24Regular,
  NotebookQuestionMark24Regular,
  People24Regular,
  PeopleSearch24Regular,
  PeopleTeam24Regular,
  Person24Regular,
  PersonBoard24Regular,
  PuzzlePiece24Regular,
  Settings24Regular,
  ShieldKeyhole24Regular,
  Sparkle24Regular,
  TabDesktopNewPage20Regular,
} from "@vicons/fluent";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { NIcon, NMenu } from "naive-ui";
import { computed, ref } from "vue";
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
    label: "Veikla",
    key: "doings",
    icon: () => {
      return <NIcon component={Sparkle24Regular}></NIcon>;
    },
    show: can.institutions,
    children: [
      {
        label: () => {
          return <Link href={route("questions.index")}>Klausimai</Link>;
        },
        key: "questions",
        icon: () => {
          return <NIcon component={BookQuestionMark24Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return (
            <Link href={route("questionGroups.index")}>Klausimų grupės</Link>
          );
        },
        key: "questionGroups",
        icon: () => {
          return <NIcon component={NotebookQuestionMark24Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("doings.index")}>Veiklos</Link>;
        },
        key: "doings",
        icon: () => {
          return <NIcon component={Sparkle24Regular}></NIcon>;
        },
      },
    ],
  },
  {
    label: "Žmonės",
    key: "contacts",
    icon: () => {
      return <NIcon component={Person24Regular}></NIcon>;
    },
    show: can.users || can.institutions,
    children: [
      {
        label: () => {
          return (
            <Link href={route("dutyInstitutions.index")}>Institucijos</Link>
          );
        },
        key: "dutyInstitutions",
        icon: () => {
          return <NIcon component={PeopleTeam24Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return (
            <Link href={route("dutyInstitutionGraph")}>
              Institucijų grafikas
            </Link>
          );
        },
        key: "dutyInstitutionsGraph",
        icon: () => {
          return <NIcon component={Flowchart20Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("duties.index")}>Pareigos</Link>;
        },
        key: "duties",
        icon: () => {
          return <NIcon component={PuzzlePiece24Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("users.index")}>Kontaktai</Link>;
        },
        key: "users",
        show: can.users,
        icon: () => {
          return <NIcon component={Person24Regular}></NIcon>;
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
    show: can.content,
    children: [
      {
        label: () => {
          return <Link href={route("pages.index")}>Puslapiai</Link>;
        },
        key: "pages",
        show: can.pages,
        icon: () => {
          return <NIcon component={DocumentMultiple24Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("news.index")}>Naujienos</Link>;
        },
        key: "news",
        show: can.news,
        icon: () => {
          return <NIcon component={News24Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("mainPage.index")}>Greitieji mygtukai</Link>;
        },
        key: "mainPage",
        show: can.mainPage,
        icon: () => {
          return <NIcon component={Grid24Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("banners.index")}>Baneriai</Link>;
        },
        key: "banners",
        show: can.banners,
        icon: () => {
          return <NIcon component={ImageArrowBack24Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("navigation.index")}>Navigacija</Link>;
        },
        key: "navigation",
        show: can.navigation,
        icon: () => {
          return <NIcon component={Navigation24Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("calendar.index")}>Kalendorius</Link>;
        },
        key: "calendar",
        show: can.calendar,
        icon: () => {
          return <NIcon component={CalendarLtr24Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("files.index")}>Failai</Link>;
        },
        key: "files",
        show: can.files,
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
    show: can.content || can.saziningai,
    children: [
      {
        label: () => {
          return <Link href={route("saziningaiExams.index")}>Sąžiningai</Link>;
        },
        key: "saziningai",
        icon: () => {
          return <NIcon component={PeopleSearch24Regular}></NIcon>;
        },
        show: can.saziningai,
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
          return <NIcon component={People24Regular}></NIcon>;
        },
        show: can.content,
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
          return <NIcon component={DocumentSettings20Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("relationships.index")}>Ryšiai</Link>;
        },
        key: "relationships",
        icon: () => {
          return <NIcon component={Flow20Regular}></NIcon>;
        },
      },
      // role index
      {
        label: () => {
          return <Link href={route("roles.index")}>Rolės</Link>;
        },
        key: "roles",
        icon: () => {
          return <NIcon component={PersonBoard24Regular}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("permissions.index")}>Leidimai</Link>;
        },
        key: "permissions",
        icon: () => {
          return <NIcon component={ShieldKeyhole24Regular}></NIcon>;
        },
      },
    ],
    show: can.settings,
  },
]);
</script>
