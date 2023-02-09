<template>
  <NMenu
    v-model:value="activeKey"
    accordion
    :collapsed="collapsed"
    :collapsed-width="56"
    :collapsed-icon-size="26"
    :options="menuOptions"
  />
</template>

<script setup lang="tsx">
import { trans as $t, getActiveLanguage } from "laravel-vue-i18n";
import {
  Flowchart20Regular,
  Folder24Regular,
  HatGraduation24Regular,
  Home24Regular,
  Notebook24Regular,
  Settings24Regular,
  TabDesktopNewPage20Regular,
} from "@vicons/fluent";
import { Link, usePage } from "@inertiajs/vue3";
import { NIcon, NMenu } from "naive-ui";
import { computed, ref } from "vue";

import Icons from "@/Types/Icons/regular";

defineProps<{
  collapsed: boolean;
}>();

const { auth } = usePage().props;
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
      return <Link href={route("dashboard")}>{$t("Pradinis")}</Link>;
    },
    key: "dashboard",
    icon: () => {
      return <NIcon component={Home24Regular}></NIcon>;
    },
  },
  {
    label() {
      return $t("Atstovavimas");
    },
    key: "representation",
    icon: () => {
      return <NIcon component={HatGraduation24Regular}></NIcon>;
    },
    show:
      auth?.can.index.goal ||
      auth?.can.index.goalGroup ||
      auth?.can.index.doing ||
      auth?.can.index.institution ||
      auth?.can.index.matter ||
      auth?.can.index.meeting,
    children: [
      {
        label: () => {
          return <Link href={route("goals.index")}>{$t("Tikslai")}</Link>;
        },
        key: "goals",
        icon: () => {
          return <NIcon component={Icons.GOAL}></NIcon>;
        },
        show: auth?.can.index.goal,
      },
      {
        label: () => {
          return (
            <Link href={route("goalGroups.index")}>{$t("Tikslų grupės")}</Link>
          );
        },
        key: "goalGroups",
        icon: () => {
          return <NIcon component={Icons.GOAL_GROUP}></NIcon>;
        },
        show: auth?.can.index.goalGroup,
      },
      {
        label: () => {
          return <Link href={route("doings.index")}>{$t("Veiksmai")}</Link>;
        },
        key: "doings",
        icon: () => {
          return <NIcon component={Icons.DOING}></NIcon>;
        },
        show: auth?.can.index.doing,
      },
      {
        key: "divider",
        type: "divider",
      },
      {
        label: () => {
          return (
            <Link href={route("institutions.index")}>{$t("Institucijos")}</Link>
          );
        },
        key: "institutions",
        icon: () => {
          return <NIcon component={Icons.INSTITUTION}></NIcon>;
        },
        show: auth?.can.index.institution,
      },
      {
        label: () => {
          return (
            <Link href={route("matters.index")}>
              {$t("Svarstomi klausimai")}
            </Link>
          );
        },
        key: "matters",
        icon: () => {
          return <NIcon component={Icons.MATTER}></NIcon>;
        },
        show: auth?.can.index.matter,
      },
      {
        label: () => {
          return (
            <Link href={route("meetings.index")}>{$t("Susitikimai")}</Link>
          );
        },
        key: "meetings",
        icon: () => {
          return <NIcon component={Icons.MEETING}></NIcon>;
        },
        show: auth?.can.index.meeting,
      },
      {
        label: () => {
          return (
            <Link href={route("institutionGraph")}>
              {$t("Institucijų grafa")}
            </Link>
          );
        },
        key: "institutionsGraph",
        icon: () => {
          return <NIcon component={Flowchart20Regular}></NIcon>;
        },
        show: auth?.can.index.institution,
      },
    ],
  },
  {
    label() {
      return $t("Žmonės");
    },
    key: "contacts",
    icon: () => {
      return <NIcon component={Icons.USER}></NIcon>;
    },
    show:
      auth?.can.index.user || auth?.can.index.duty || auth?.can.index.contact,
    children: [
      {
        label: () => {
          return <Link href={route("users.index")}>{$t("Nariai")}</Link>;
        },
        key: "users",
        show: auth?.can.index.user,
        icon: () => {
          return <NIcon component={Icons.USER}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("duties.index")}>{$t("Pareigos")}</Link>;
        },
        key: "duties",
        icon: () => {
          return <NIcon component={Icons.DUTY}></NIcon>;
        },
        show: auth?.can.index.duty,
      },
      {
        label: () => {
          return (
            <Link href={route("contacts.index")}>{$t("Kiti kontaktai")}</Link>
          );
        },
        key: "contacts",
        icon: () => {
          return <NIcon component={Icons.CONTACT}></NIcon>;
        },
        show: auth?.can.index.contact,
      },
    ],
  },
  {
    label: "vusa.lt",
    key: "content",
    icon: () => {
      return <NIcon component={TabDesktopNewPage20Regular}></NIcon>;
    },
    show:
      auth?.can.index.page ||
      auth?.can.index.news ||
      auth?.can.index.mainPage ||
      auth?.can.index.navigation ||
      auth?.can.index.banner ||
      auth?.can.index.sharepointFile ||
      auth?.can.index.calendar,
    children: [
      {
        label: () => {
          return <Link href={route("pages.index")}>{$t("Puslapiai")}</Link>;
        },
        key: "pages",
        show: auth?.can.index.page,
        icon: () => {
          return <NIcon component={Icons.PAGE}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("news.index")}>{$t("Naujienos")}</Link>;
        },
        key: "news",
        show: auth?.can.index.news,
        icon: () => {
          return <NIcon component={Icons.NEWS}></NIcon>;
        },
      },
      {
        label: () => {
          return (
            <Link href={route("mainPage.index")}>
              {$t("Greitieji mygtukai")}
            </Link>
          );
        },
        key: "mainPage",
        show: auth?.can.index.mainPage,
        icon: () => {
          return <NIcon component={Icons.MAIN_PAGE}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("banners.index")}>{$t("Baneriai")}</Link>;
        },
        key: "banners",
        show: auth?.can.index.banner,
        icon: () => {
          return <NIcon component={Icons.BANNER}></NIcon>;
        },
      },
      {
        label: () => {
          return (
            <Link href={route("navigation.index")}>{$t("Navigacija")}</Link>
          );
        },
        key: "navigation",
        show: auth?.can.index.navigation,
        icon: () => {
          return <NIcon component={Icons.NAVIGATION}></NIcon>;
        },
      },
      {
        label: () => {
          return (
            <Link href={route("calendar.index")}>{$t("Kalendorius")}</Link>
          );
        },
        key: "calendar",
        show: auth?.can.index.calendar,
        icon: () => {
          return <NIcon component={Icons.CALENDAR}></NIcon>;
        },
      },
      {
        label: () => {
          return <Link href={route("files.index")}>{$t("Failai")}</Link>;
        },
        key: "files",
        show: auth?.can.index.sharepointFile,
        icon: () => {
          return <NIcon component={Folder24Regular}></NIcon>;
        },
      },
    ],
  },
  {
    label() {
      return $t("Registracijos");
    },
    key: "registrations",
    icon: () => {
      return <NIcon component={Notebook24Regular}></NIcon>;
    },
    show: auth?.can.index.institution || auth?.can.index.saziningaiExam,
    children: [
      {
        label: () => {
          return <Link href={route("saziningaiExams.index")}>Sąžiningai</Link>;
        },
        key: "saziningai",
        icon: () => {
          return <NIcon component={Icons.SAZININGAI_EXAM}></NIcon>;
        },
        show: auth?.can.index.saziningaiExam,
      },
      {
        label: () => {
          return (
            <Link href={route("registrationForms.show", 2)}>
              {$t("Narių registracija")}
            </Link>
          );
        },
        key: "memberRegister",
        icon: () => {
          return <NIcon component={Icons.USER}></NIcon>;
        },
        show: auth?.can.index.institution,
      },
    ],
  },
  {
    label: () => {
      return <Link href={route("sharepointFiles.index")}>{$t("Failai")}</Link>;
    },
    key: "files",
    show: auth?.can.index.sharepointFile,
    icon: () => {
      return <NIcon component={Icons.SHAREPOINT_FILE}></NIcon>;
    },
  },
  {
    label() {
      return $t("Nustatymai");
    },
    key: "settings",
    icon: () => {
      return <NIcon component={Settings24Regular}></NIcon>;
    },
    children: [
      {
        label: () => {
          return <Link href={route("types.index")}>{$t("Tipai")}</Link>;
        },
        key: "types",
        icon: () => {
          return <NIcon component={Icons.TYPE}></NIcon>;
        },
        show: auth?.can.index.type,
      },
      {
        label: () => {
          return (
            <Link href={route("relationships.index")}>{$t("Ryšiai")}</Link>
          );
        },
        key: "relationships",
        icon: () => {
          return <NIcon component={Icons.RELATIONSHIP}></NIcon>;
        },
        show: auth?.can.index.relationship,
      },
      // role index
      {
        label: () => {
          return <Link href={route("roles.index")}>{$t("Rolės")}</Link>;
        },
        key: "roles",
        icon: () => {
          return <NIcon component={Icons.ROLE}></NIcon>;
        },
        show: auth?.can.index.role,
      },
      {
        label: () => {
          return (
            <Link href={route("permissions.index")}>{$t("Leidimai")}</Link>
          );
        },
        key: "permissions",
        icon: () => {
          return <NIcon component={Icons.PERMISSION}></NIcon>;
        },
        show: auth?.can.index.permission,
      },
    ],
    show:
      auth?.can.index.type ||
      auth?.can.index.relationship ||
      auth?.can.index.role ||
      auth?.can.index.permission,
  },
]);
</script>
