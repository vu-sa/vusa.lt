<template>
  <PageContent>
    <section id="tavo-institucijos">
      <h2>Tavo institucijos</h2>
      <div class="flex flex-wrap gap-4">
        <InstitutionCard
          v-for="institution in institutions"
          :key="institution.id"
          :institution="institution"
          :duties="duties"
          :is-padalinys="institution.alias === institution.padalinys.alias"
        />
      </div>
    </section>
    <section id="naudingos-nuorodos">
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
    </section>
    <template #aside-card>
      <!-- <NAnchor
        class="hidden md:block"
        style="margin: 0 1rem 0 1rem"
        listen-to="#main-scroll-container"
        affix
        :bound="108"
      >
        <NAnchorLink
          title="Tavo institucijos"
          href="#tavo-institucijos"
        ></NAnchorLink>
        <NAnchorLink
          title="Naudingos nuorodos"
          href="#naudingos-nuorodos"
        ></NAnchorLink>
      </NAnchor> -->
    </template>
  </PageContent>
</template>

<script setup lang="tsx">
import { ExternalLinkSquareAlt } from "@vicons/fa";
import { NAnchor, NAnchorLink, NButton, NIcon } from "naive-ui";

import InstitutionCard from "@/Components/Cards/InstitutionCard.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

defineProps<{
  institutions: Record<string, any>[];
  duties: App.Entities.Duty[];
}>();

const windowOpen = (url: string, target: string) => {
  window.open(url, target);
};
</script>

<style scoped>
section {
  scroll-margin-top: 1rem;
}
</style>
