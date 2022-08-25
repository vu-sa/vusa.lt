<template>
  <NDropdown :options="options">
    <NButton text size="tiny">
      <NAvatar round size="small" :src="inertiaProps.user.profile_photo_path">
        <span v-if="!inertiaProps.user.profile_photo_path">
          {{ userInitials(inertiaProps.user.name) }}
        </span>
      </NAvatar>
      <span class="ml-2"
        >{{ inertiaProps.user.name }} ({{
          inertiaProps.user.padalinys ?? "Be padalinio"
        }})</span
      >
    </NButton>
  </NDropdown>
</template>

<script setup lang="ts">
import { DoorArrowRight28Regular } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NAvatar, NButton, NDropdown, NIcon } from "naive-ui";
import { h, ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import type { Component } from "vue";

const inertiaProps = usePage<InertiaProps>().props.value;
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

const userInitials = (name: string) => {
  const words = name.split(" ");
  return words[0].charAt(0) + words[words.length - 1].charAt(0);
};
</script>
