<template>
  <PageContent title="Tavo institucijos">
    <div class="flex max-w-4xl flex-wrap gap-4">
      <InstitutionCard
        v-for="institution in institutions"
        :key="institution.id"
        :institution="institution"
        :duties="duties"
        :is-padalinys="institution.alias === institution.padalinys.alias"
        show-last-meeting
      />
    </div>
    <h3 class="my-4">Naudingos nuorodos</h3>
    <div class="flex gap-2">
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

<script setup lang="ts">
import { ExternalLinkSquareAlt } from "@vicons/fa";
import { NButton, NIcon } from "naive-ui";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import InstitutionCard from "@/Components/Cards/InstitutionCard.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  institutions: Record<string, any>[];
  duties: App.Models.DutyExtended[];
}>();

const windowOpen = (url: string, target: string) => {
  window.open(url, target);
};
</script>
