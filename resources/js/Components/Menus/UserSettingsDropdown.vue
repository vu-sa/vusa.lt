<template>
  <NDropdown :options>
    <NButton text size="tiny">
      <UserAvatar class="mr-2" :size="28" :user="$page.props.auth?.user" />
      {{ $page.props.auth?.user ? $page.props.auth.user?.name : "" }}
    </NButton>
  </NDropdown>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { type DropdownOption, NIcon } from "naive-ui";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import DoorArrowRight28Regular from "~icons/fluent/door-arrow-right28-regular";
import PersonSettings16Regular from "~icons/fluent/person-settings16-regular";

import UserAvatar from "../Avatars/UserAvatar.vue";

const loading = ref(false);

const options: DropdownOption[] = [
  {
    label() {
      return $t("Nustatymai");
    },
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
    label() {
      return $t("auth.logout");
    },
    key: "logout",
    icon() {
      return <NIcon component={DoorArrowRight28Regular}></NIcon>;
    },
    props: {
      onClick: () => {
        loading.value = true;
        fetch(route("logout"), {
          method: "POST",
          headers: {
            "X-CSRF-TOKEN": document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute("content") as string,
          },
        })
          .then(() => {
            window.location.href = "/";
          })
          .finally(() => {
            loading.value = false;
          });
      },
    },
  },
];
</script>
