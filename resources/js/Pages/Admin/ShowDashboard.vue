<template>
  <PageContent>
    <h2>Tavo institucijos</h2>
    <div class="my-4 flex max-w-4xl flex-wrap gap-4">
      <DutyInstitutionCard
        v-for="institution in dutyInstitutions"
        :key="institution.id"
        :institution="institution"
        :is-padalinys="institution.alias === institution.padalinys.alias"
        show-last-meeting
      />
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

<script setup lang="ts">
import { ExternalLinkSquareAlt } from "@vicons/fa";
import { NButton, NIcon } from "naive-ui";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import DutyInstitutionCard from "@/Components/Cards/DutyInstitutionCard.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  dutyInstitutions: Record<string, any>[];
}>();

const windowOpen = (url: string, target: string) => {
  window.open(url, target);
};
</script>
