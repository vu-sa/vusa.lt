<template>
  <NDropdown :options="options">
    <NButton text size="tiny">
      <UserAvatar show-padalinys :user="$page.props.user" />
    </NButton>
  </NDropdown>
</template>

<script setup lang="ts">
import {
  DoorArrowRight28Regular,
  PersonSettings16Regular,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NDropdown, NIcon } from "naive-ui";
import { h, ref } from "vue";
import route from "ziggy-js";

import UserAvatar from "@/Components/Admin/UserAvatar.vue";

import type { Component } from "vue";

const loading = ref(false);

const renderIcon = (icon: Component) => {
  return () => {
    return h(NIcon, null, {
      default: () => h(icon),
    });
  };
};

const options = [
  {
    label: "Nustatymai",
    key: "user-settings",
    icon: renderIcon(PersonSettings16Regular),
    props: {
      onClick: () => {
        Inertia.visit(route("profile"));
      },
    },
  },
  {
    label: "Atsijungti",
    key: "logout",
    icon: renderIcon(DoorArrowRight28Regular),
    props: {
      onClick: () => {
        loading.value = true;
        Inertia.post(route("logout"));
      },
    },
  },
];
</script>
