<template>
  <PageContent>
    <NPopover>
      <NCheckboxGroup v-model:value="shownSections">
        <div class="flex flex-col gap-2">
          <NCheckbox value="Veiksmai" label="Greitieji veiksmai"></NCheckbox>
          <NCheckbox value="Institucijos" label="Tavo institucijos"></NCheckbox>
          <NCheckbox value="Posėdžiai" label="Artėjantys posėdžiai" />
          <NCheckbox
            value="Nuorodos"
            label="Naudingos nuorodos"
            disabled
          ></NCheckbox>
        </div>
      </NCheckboxGroup>
      <template #trigger>
        <div class="absolute top-0 right-0">
          <NButton circle quaternary
            ><template #icon
              ><NIcon
                :size="24"
                :component="Settings24Filled"
              ></NIcon></template
          ></NButton>
        </div>
      </template>
    </NPopover>
    <section v-if="shownSections.includes('Veiksmai')" class="mb-8">
      <h2 class="mb-6">Greitieji veiksmai</h2>
      <div class="flex items-center gap-4">
        <QuickActionButton :icon="PeopleCommunity24Regular"
          >Organizuoti <em>focus</em> grupę</QuickActionButton
        >
        <QuickActionButton :icon="DocumentCheckmark24Regular"
          >Organizuoti el. apklausą</QuickActionButton
        >
      </div>
    </section>
    <section
      v-if="shownSections.includes('Institucijos')"
      id="tavo-institucijos"
      class="mb-8"
    >
      <h2>Tavo institucijos</h2>
      <!-- <NScrollbar
        :class="{
          'max-h-[18rem]': !institutionExpanded,
          'max-h-[100%]': institutionExpanded,
        }"
      > -->
      <div
        class="relative mt-4 grid w-full grid-cols-ramFill gap-4 overflow-hidden pb-4 transition-transform duration-300 ease-in-out"
      >
        <InstitutionCard
          v-for="institution in institutions"
          :key="institution.id"
          :institution="institution"
          :duties="duties"
          :is-padalinys="institution.alias === institution.padalinys.alias"
          @click="router.visit(route('institutions.show', institution.id))"
        />
        <!-- <div
            v-if="!institutionExpanded && institutions.length > 4"
            class="absolute bottom-0 h-12 w-full bg-gradient-to-b from-transparent to-[rgb(250,_248,_248)] text-center dark:to-[rgb(30,_30,_33)]"
          >
            <NButton
              secondary
              circle
              @click="institutionExpanded = !institutionExpanded"
              ><template #icon
                ><NIcon :component="MoreHorizontal24Regular"></NIcon></template
            ></NButton>
          </div> -->
      </div>
      <!-- </NScrollbar> -->
    </section>
    <section v-if="shownSections.includes('Posėdžiai')" class="relative mb-8">
      <h2>Artėjantys posėdžiai</h2>

      <!-- <NScrollbar
        :class="{
          'max-h-[16rem]': !meetingExpanded,
          'max-h-[100%]': meetingExpanded,
        }"
      > -->
      <div class="grid grid-cols-ramFill gap-x-4">
        <template v-for="institution in institutions">
          <MeetingCard
            v-for="meeting in institution.meetings"
            :key="meeting.id"
            style="min-width: 300px; max-width: 400px"
            :meeting="meeting"
            :institution="institution"
            @click="router.visit(route('meetings.show', meeting.id))"
          ></MeetingCard>
        </template>
      </div>
      <!-- </NScrollbar> -->
      <!-- <div
        v-if="!meetingExpanded && institutions.length > 3"
        class="absolute bottom-0 h-12 w-full bg-gradient-to-b from-transparent to-[rgb(250,_248,_248)] text-center dark:to-[rgb(30,_30,_33)]"
      >
        <NButton secondary circle @click="meetingExpanded = !meetingExpanded"
          ><template #icon
            ><NIcon :component="MoreHorizontal24Regular"></NIcon></template
        ></NButton>
      </div> -->
    </section>
    <section id="naudingos-nuorodos">
      <h2 class="mb-4">Naudingos nuorodos</h2>
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
  </PageContent>
</template>

<script setup lang="tsx">
import {
  DocumentCheckmark24Regular,
  MoreHorizontal24Regular,
  PeopleCommunity24Regular,
  Settings24Filled,
} from "@vicons/fluent";
import { ExternalLinkSquareAlt } from "@vicons/fa";
import {
  NButton,
  NCheckbox,
  NCheckboxGroup,
  NIcon,
  NPopover,
  NScrollbar,
} from "naive-ui";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import InstitutionCard from "@/Components/Cards/InstitutionCard.vue";
import MeetingCard from "@/Components/Cards/MeetingCard.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import QuickActionButton from "@/Components/Deprecated/QuickActionButton.vue";

defineProps<{
  institutions: Record<string, any>[];
  duties: App.Entities.Duty[];
}>();

const windowOpen = (url: string, target: string) => {
  window.open(url, target);
};

const institutionExpanded = ref(false);
const meetingExpanded = ref(false);

const shownSections = useStorage("dashboard-sections", [
  "Institucijos",
  "Veiksmai",
  "Nuorodos",
]);
</script>

<style scoped>
h2 {
  font-weight: 900;
}
</style>
