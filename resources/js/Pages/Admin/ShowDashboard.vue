<template>
  <PageContent>
    <h2>Tavo institucijos</h2>
    <div class="my-4 flex max-w-4xl flex-wrap gap-4">
      <NCard
        v-for="institution in dutyInstitutions"
        :key="institution.id"
        hoverable
        :segmented="{
          footer: 'soft',
        }"
        as="button"
        style="max-width: 400px"
        class="cursor-pointer shadow-sm"
        :title="institution.name"
        @click="Inertia.visit(route('dutyInstitutions.show', institution.id))"
      >
        <template #footer>
          <NTag
            v-for="type in institution.types"
            :key="type.id"
            size="small"
            :bordered="false"
          >
            {{ type.title }}
          </NTag>
        </template>
        <!-- <template #action>
          <NButton>Pasižiūrėti</NButton>
        </template> -->

        <!-- <template #header-extra>
          <NDropdown trigger="click" :options="options">
            <NButton size="small" round circle quaternary @click.stop
              ><NIcon
                size="16"
                :component="ArrowForwardDownLightning20Regular"
              ></NIcon
            ></NButton>
          </NDropdown>
        </template> -->
        <InstitutionAvatarGroup :users="institution.users" />
      </NCard>
    </div>
    <h3>Naudingos nuorodos</h3>
    <div class="mt-4 flex gap-2">
      <NButton
        type="warning"
        secondary
        :bordered="false"
        size="tiny"
        round
        @click="windowOpen('https://atstovavimas.vusa.lt', '_blank')"
        ><template #icon
          ><div class="ml-2 mr-1">
            <NIcon :size="10" :component="ExternalLinkSquareAlt"></NIcon></div
        ></template>
        <span class="text-zinc-900/70 dark:text-zinc-100/80"
          >atstovavimas.vusa.lt</span
        ></NButton
      >
      <NButton
        secondary
        :bordered="false"
        size="tiny"
        round
        @click="windowOpen('https://apklausa.vusa.lt', '_blank')"
        ><template #icon
          ><div class="ml-2 mr-1">
            <NIcon :size="10" :component="ExternalLinkSquareAlt"></NIcon></div
        ></template>
        <span class="text-zinc-900/70 dark:text-zinc-100/80"
          >apklausa.vusa.lt</span
        ></NButton
      >
    </div>
  </PageContent>
</template>

<script lang="ts">
import { h } from "vue";
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import {
  ArrowForwardDownLightning20Regular,
  DocumentAdd24Filled,
  PeopleTeamAdd24Filled,
} from "@vicons/fluent";
import { ExternalLinkSquareAlt } from "@vicons/fa";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NCard, NDropdown, NIcon, NTag } from "naive-ui";
import route from "ziggy-js";

import InstitutionAvatarGroup from "@/Components/Admin/Misc/InstitutionAvatarGroup.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import QuickActionButton from "@/Components/Admin/Buttons/QuickActionButton.vue";

defineProps<{
  dutyInstitutions: Record<string, any>[];
}>();

const options = [
  {
    label: "Pranešti apie artėjantį posėdį",
    key: "notify-about-meeting",
    icon() {
      return h(NIcon, {
        size: "16",
        component: PeopleTeamAdd24Filled,
      });
    },
  },
  {
    label: "Įkelti posėdžio protokolą",
    key: "upload-meeting-protocol",
    icon() {
      return h(NIcon, {
        size: "16",
        component: DocumentAdd24Filled,
      });
    },
  },
];

const windowOpen = (url: string, target: string) => {
  window.open(url, target);
};
</script>
