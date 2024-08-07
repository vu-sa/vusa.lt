<template>
  <PageContent>

    <Head :title="$t('Pradinis')" />

    <NPopover>
      <NCheckboxGroup v-model:value="shownSections">
        <div class="flex flex-col gap-2">
          <NCheckbox disabled value="Greitieji veiksmai" :label="$t('Greitieji veiksmai')" />
          <NCheckbox value="Institucijos" :label="$t('Tavo institucijos')" />
          <NCheckbox value="Posėdžiai" :label="$t('Artėjantys posėdžiai')" />
          <NCheckbox value="Rezervacijos" :label="$t('Tavo rezervacijos')" />
          <NCheckbox value="Veiklos" :label="$t('Tavo veiklos')" />
          <NCheckbox value="Nuorodos" :label="$t('Naudingos nuorodos')" disabled />
        </div>
      </NCheckboxGroup>
      <template #trigger>
        <div class="absolute right-0 top-0">
          <NButton circle quaternary><template #icon>
            <IFluentSettings24Filled />
            </template></NButton>
        </div>
      </template>
    </NPopover>
    <section v-if="shownSections.includes('Greitieji veiksmai')" class="mb-8">
      <h2 class="mb-4 flex items-center gap-2">
        <IFluentLightbulbFilament24Filled class="text-vusa-yellow" />
        <span>{{ $t("Greitieji veiksmai")
          }}</span>
      </h2>
      <div class="flex flex-wrap items-center gap-4">
        <QActCreateMeeting />
        <QActSurveyButton />
        <QActFocusGroupButton />
        <Link :href="route('reservations.create')">
        <QuickActionButton>{{
          $t("Kurti rezervaciją")
        }}
          <template #icon>
            <Icons.RESERVATION />
          </template>
        </QuickActionButton>
        </Link>
      </div>
    </section>
    <section v-if="shownSections.includes('Institucijos')" id="tavo-institucijos" class="mb-8">
      <h2 class="flex items-center gap-2">
        <NIcon :component="Icons.INSTITUTION" /><span>{{ $t("Tavo institucijos") }}</span>
      </h2>
      <div v-if="currentUser.institutions.length > 0"
        class="relative mt-4 grid w-full grid-cols-ramFill items-start gap-4 overflow-hidden pb-4 transition-transform duration-300 ease-in-out">
        <InstitutionCard v-for="institution in currentUser.institutions" :key="institution.id"
          :institution="institution" :duties="currentUser.duties"
          :is-padalinys="institution.alias === institution.tenant?.alias"
          @click="router.visit(route('institutions.show', institution.id))" />
      </div>
      <p v-else>
        {{ $t("Neturi tiesiogiai priskirtų institucijų") }}.
      </p>
    </section>
    <section v-if="shownSections.includes('Posėdžiai')" class="relative mb-8">
      <h2 class="flex items-center gap-2">
        <NIcon :component="Icons.MEETING" /><span>{{ $t("Artėjantys posėdžiai") }}</span>
      </h2>
      <div class="grid grid-cols-ramFill gap-x-4">
        <template v-for="institution in currentUser.institutions">
          <MeetingCard v-for="meeting in institution.meetings" :key="meeting.id"
            style="min-width: 300px; max-width: 400px" :meeting="meeting" :institution="institution"
            @click="router.visit(route('meetings.show', meeting.id))" />
        </template>
        <p class="hidden first:block">
          {{ $t("Artėjančių posėdžių nėra") }}.
        </p>
      </div>
    </section>
    <section v-if="shownSections.includes('Rezervacijos')" class="relative mb-8">
      <h2 class="flex items-center gap-2">
        <NIcon :component="Icons.RESERVATION" /><span>{{ $t("Tavo rezervacijos") }}</span>
        <Link :href="route('reservations.create')">
        <IFluentAddCircle24Filled />
        </Link>
      </h2>
      <div v-if="currentUser.reservations && currentUser.reservations.length > 0"
        class="relative mt-4 grid w-full grid-cols-ramFill items-start gap-4 overflow-hidden pb-4 transition-transform duration-300 ease-in-out">
        <ReservationCard v-for="reservation in currentUser.reservations" :key="reservation.id"
          :reservation="reservation" />
      </div>
      <p v-else>
        {{ $t("Neturi sukurtų rezervacijų") }}.
      </p>
    </section>
    <section v-if="shownSections.includes('Veiklos')" class="relative mb-8">
      <h2 class="flex items-center gap-2">
        <NIcon :component="Icons.DOING" /><span>{{ $t("Tavo veiklos") }}</span>
      </h2>
      <div class="grid grid-cols-ramFill gap-x-4">
        <DoingCard v-for="doing in currentUser.doings" :key="doing.id" :doing="doing"
          @click="router.visit(route('doings.show', doing.id))" />
        <p class="hidden first:block">
          {{ $t("Neturi sukurtų veiklų") }}.
        </p>
      </div>
    </section>
    <section id="naudingos-nuorodos">
      <h2 class="mb-4 flex items-center gap-2">
        <IFluentLink24Filled />
        <span>{{ $t("Naudingos nuorodos") }}</span>
      </h2>
      <div class="flex flex-wrap gap-2">
        <NButton type="warning" secondary :bordered="false" size="tiny" round tag="a" target="_blank"
          href="https://atstovavimas.vusa.lt"><template #icon>
            <div class="ml-2 mr-1">
              <IMdiExternalLink width="10" height="10" />
            </div>
          </template>
          <span class="text-zinc-900/70 dark:text-zinc-100/80">atstovavimas.vusa.lt</span>
        </NButton>
        <NButton secondary :bordered="false" size="tiny" round tag="a" target="_blank" href="http://atstovai.vusa.lt">
          <template #icon>
            <div class="ml-2 mr-1">
              <IMdiExternalLink width="10" height="10" />
            </div>
          </template>
          <span class="text-zinc-900/70 dark:text-zinc-100/80">atstovai.vusa.lt (slaptažodis: {{ atstovaiPassword
            }})</span>
        </NButton>
        <NButton secondary :bordered="false" size="tiny" round tag="a" target="_blank" href="http://archyvas.vusa.lt">
          <template #icon>
            <div class="ml-2 mr-1">
              <IMdiExternalLink width="10" height="10" />
            </div>
          </template>
          <span class="text-zinc-900/70 dark:text-zinc-100/80">archyvas.vusa.lt (slaptažodis: {{ archyvasPassword
            }})</span>
        </NButton>
        <NButton secondary :bordered="false" size="tiny" round tag="a" target="_blank" href="https://office.com">
          <template #icon>
            <div class="ml-2 mr-1">
              <IMdiExternalLink width="10" height="10" />
            </div>
          </template>
          <span class="text-zinc-900/70 dark:text-zinc-100/80">Microsoft 365</span>
        </NButton>
      </div>
    </section>
  </PageContent>
</template>

<script setup lang="tsx">
import { Head, Link, router } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import DoingCard from "@/Components/Cards/DoingCard.vue";
import Icons from "@/Types/Icons/filled";
import InstitutionCard from "@/Components/Cards/InstitutionCard.vue";
import MeetingCard from "@/Components/Cards/MeetingCard.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import QActCreateMeeting from "@/Components/Buttons/QActCreateMeeting.vue";
import QActFocusGroupButton from "@/Components/Buttons/QActFocusGroupButton.vue";
import QActSurveyButton from "@/Components/Buttons/QActSurveyButton.vue";
import QuickActionButton from "@/Components/Buttons/QuickActionButton.vue";
import ReservationCard from "@/Components/Cards/ReservationCard.vue";

defineProps<{
  currentUser: App.Entities.User;
}>();

const shownSections = useStorage("dashboard-sections", [
  "Greitieji veiksmai",
  "Institucijos",
  "Nuorodos",
]);

const atstovaiPassword = import.meta.env.VITE_ATSTOVAI_PASSWORD ?? "";
const archyvasPassword = import.meta.env.VITE_ARCHYVAS_PASSWORD ?? "";
</script>

<style scoped>
h2 {
  font-weight: 900;
  font-size: 1.25rem;
  line-height: 1.75rem;
  font-kerning: auto;
  /* more compact */
  letter-spacing: -0.02em;
}
</style>
