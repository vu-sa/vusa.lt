<template>
  <NDropdown :options="options">
    <NButton text size="tiny">
      <UserAvatar :size="28" show-padalinys :user="$page.props.auth?.user" />
    </NButton>
  </NDropdown>
</template>

<script setup lang="tsx">
import {
  DoorArrowRight28Regular,
  PersonSettings16Regular,
} from "@vicons/fluent";
import { type DropdownOption, NButton, NDropdown, NIcon } from "naive-ui";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

const loading = ref(false);

const options: DropdownOption[] = [
  {
    label: "Nustatymai",
    key: "user-settings",
    icon() {
      return <NIcon component={PersonSettings16Regular}></NIcon>;
    },
    props: {
      onClick: () => {
        router.visit(route("profile"));
      },
    },
  },
  {
    label: "Atsijungti",
    key: "logout",
    icon() {
      return <NIcon component={DoorArrowRight28Regular}></NIcon>;
    },
    props: {
      onClick: () => {
        loading.value = true;
        router.post(route("logout"));
      },
    },
  },
];
</script>
