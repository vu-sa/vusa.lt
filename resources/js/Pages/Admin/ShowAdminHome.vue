<template>
  <PageContent>

    <Head :title="$t('Pradinis')" />

    <p class="mt-16 text-4xl font-bold tracking-tight">
      {{ $t('Labas') }}, {{ userNameAddress }}!  👋
    </p>

    <p class="mb-2 mt-8 font-medium text-zinc-600">
      {{ $t('Pasirink vieną iš veiksmų') }}:
    </p>

    <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <Link v-if="$page.props.auth?.can.create.meeting" :href="route('dashboard.atstovavimas')">
      <button
        class="flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-gradient-to-br from-white to-white p-4 text-left text-base text-zinc-700 shadow-sm duration-500 hover:shadow-lg hover:shadow-vusa-red/20 dark:border-zinc-800 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300">
        <IFluentHatGraduation24Filled class="mb-1 mt-2" width="28" height="28" />
        <span class="text-xl font-bold">{{ $t('Atstovavimas') }}</span>
        <p class="text-sm leading-4 text-zinc-500">
          {{ $t('Informuok apie posėdžius ir įkelk dokumentus atstovavimo organuose') }}
        </p>
      </button>
      </Link>
      <Link v-if="$page.props.auth?.can.create.page" :href="route('dashboard.svetaine')">
      <button
        class="flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-gradient-to-br from-white to-white p-4 text-left text-base text-zinc-700 shadow-sm duration-500 hover:shadow-lg hover:shadow-vusa-yellow/20 dark:border-zinc-800 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300">
        <IFluentGlobe24Filled class="mb-1 mt-2" width="28" height="28" />
        <span class="text-xl font-bold">{{ $t('Svetainė') }}</span>
        <p class="text-sm leading-4 text-zinc-500">
          {{ $t('Valdyk savo padalinio svetainės naujienas, puslapius ir kt.') }}
        </p>
      </button>
      </Link>
      <Link :href="route('dashboard.reservations')">
      <button
        class="flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-gradient-to-br from-white to-white p-4 text-left text-base text-zinc-700 shadow-sm duration-500 hover:shadow-lg dark:border-zinc-800 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300 dark:hover:shadow-white/20">
        <IFluentBookmark24Filled class="mb-1 mt-2" width="28" height="28" />
        <span class="text-xl font-bold">{{ $t('Rezervacijos') }}</span>
        <p class="text-sm leading-4 text-zinc-500">
          {{ $t('Rezervuokis daiktus iš visų VU SA padalinių') }}
        </p>
      </button>
      </Link>
    </div>
    <p class="mt-24 font-medium text-zinc-600">
      {{ $t('Kiti įrankiai') }}
    </p>
    <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <Link :href="route('administration')">
      <button
        class="flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-gradient-to-br from-white to-white p-4 text-left text-base text-zinc-700 shadow-sm duration-500 hover:shadow-lg dark:border-zinc-800 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300 dark:hover:shadow-white/20">
        <IFluentSettings24Filled class="mb-1 mt-2" width="28" height="28" />
        <span class="text-xl font-bold">{{ $t('Administravimas') }}</span>
        <p class="text-sm leading-4 text-zinc-500">
          {{ $t('Visos informacijos administravimo įrankiai ir lentelės') }}
        </p>
      </button>
      </Link>
      <a href="https://www.vusa.lt/docs">
        <button
          class="flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-gradient-to-br from-white to-white p-4 text-left text-base text-zinc-700 shadow-sm duration-500 hover:shadow-lg dark:border-zinc-800 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300 dark:hover:shadow-white/20">
          <IFluentBookExclamationMark20Filled class="mb-1 mt-2" width="28" height="28" />
          <span class="text-xl font-bold">{{ $t('Dokumentacija') }}</span>
          <p class="text-sm leading-4 text-zinc-500">
            {{ $t('Instrukcijos apie vusa.lt/mano platformą ir naudotojų atsakomybes') }}
          </p>
        </button>
      </a>
    </div>
    <!--<section class="mb-8">
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
        <QuickActionButton>
          {{
            $t("Kurti rezervaciją")
          }}
          <template #icon>
            <Icons.RESERVATION />
          </template>
</QuickActionButton>
</Link>
</div>
</section> -->
  </PageContent>
</template>

<script setup lang="tsx">
import { Head, Link, usePage } from "@inertiajs/vue3";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import { computed } from "vue";
import { addressivize } from "@/Utils/String";
//import Icons from "@/Types/Icons/filled";
//import QActCreateMeeting from "@/Components/Buttons/QActCreateMeeting.vue";
//import QActFocusGroupButton from "@/Components/Buttons/QActFocusGroupButton.vue";
//import QActSurveyButton from "@/Components/Buttons/QActSurveyButton.vue";
//import QuickActionButton from "@/Components/Buttons/QuickActionButton.vue";

const userNameAddress = computed(() => {
  const name = usePage().props.auth?.user.name;

  // Split
  const split = name?.split(" ");

  if (!split) {
    return "";
  }

  const firstName = split[0];

  return usePage().props.app.locale === 'lt' ? addressivize(firstName) : firstName;
});

//const atstovaiPassword = import.meta.env.VITE_ATSTOVAI_PASSWORD ?? "";
//const archyvasPassword = import.meta.env.VITE_ARCHYVAS_PASSWORD ?? "";

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
