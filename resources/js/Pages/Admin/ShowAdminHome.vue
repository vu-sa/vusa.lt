<template>
  <PageContent>
    <Head :title="$t('Pradinis')" />

    <p class="mt-16 text-4xl font-bold tracking-tight">
      {{ $t('Labas') }}, {{ userNameAddress }}!  👋
    </p>

    <p class="mb-2 mt-8 font-medium text-zinc-600">
      {{ $t('Pasirink vieną iš veiksmų') }}:
    </p>

    <AdminMultiHomeCards />

    <p class="mt-24 font-medium text-zinc-600">
      {{ $t('Kiti įrankiai') }}
    </p>
    <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <Link :href="route('administration')">
      <button
        class="flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-linear-to-br from-white to-white p-4 text-left text-base text-zinc-700 shadow-xs duration-500 hover:shadow-lg dark:border-zinc-800 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300 dark:hover:shadow-white/20">
        <IFluentSettings24Filled class="mb-1 mt-2" width="28" height="28" />
        <span class="text-xl font-bold">{{ $t('Administravimas') }}</span>
        <p class="text-sm leading-4 text-zinc-500">
          {{ $t('Visos informacijos administravimo įrankiai ir lentelės') }}
        </p>
      </button>
      </Link>
      <a href="https://www.vusa.lt/docs">
        <button
          class="flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-linear-to-br from-white to-white p-4 text-left text-base text-zinc-700 shadow-xs duration-500 hover:shadow-lg dark:border-zinc-800 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300 dark:hover:shadow-white/20">
          <IFluentBookExclamationMark20Filled class="mb-1 mt-2" width="28" height="28" />
          <span class="text-xl font-bold">{{ $t('Dokumentacija') }}</span>
          <p class="text-sm leading-4 text-zinc-500">
            {{ $t('Instrukcijos apie vusa.lt/mano platformą ir naudotojų atsakomybes') }}
          </p>
        </button>
      </a>
    </div>
  </PageContent>
</template>

<script setup lang="tsx">
import { Head, Link, usePage } from "@inertiajs/vue3";
import { useBreadcrumbs } from '@/Composables/useBreadcrumbs';
import { trans as $t } from "laravel-vue-i18n";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import { computed } from "vue";
import { addressivize } from "@/Utils/String";
import AdminMultiHomeCards from "../../Components/Cards/AdminMultiHomeCards.vue";

const { setBreadcrumbs } = useBreadcrumbs();

setBreadcrumbs([]);

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
