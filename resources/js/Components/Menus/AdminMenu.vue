<template>
  <!-- Dinaminės nuorodos į administravimo puslapius -->
  <div class="gap mt-4 flex flex-col px-6">
    <span class="mb-2 text-xs uppercase text-zinc-500">{{ $t('Funkcijos') }}</span>
    <Link :href="route('dashboard.atstovavimas')">
    <NButton quaternary text>
      <template #icon>
        <IFluentHatGraduation24Filled />
      </template>
      {{ $t('Atstovavimas') }}
    </NButton>
    </Link>
    <Link v-if="$page.props.auth?.can.create.page" :href="route('dashboard.svetaine')">
    <NButton quaternary text>
      <template #icon>
        <IFluentGlobe24Regular />
      </template>
      {{ $t('Svetainė') }}
    </NButton>
    </Link>
    <Link :href="route('dashboard.reservations')">
    <NButton quaternary text>
      <template #icon>
        <IFluentBookmark24Regular />
      </template>
      {{ capitalize($tChoice('entities.reservation.model', 2)) }}
    </NButton>
    </Link>
    <span class="mb-2 mt-4 text-xs uppercase text-zinc-500">{{ $t('Kita') }}</span>
    <Link :href="route('administration')">
    <NButton quaternary text>
      <template #icon>
        <IFluentSettings24Filled />
      </template>
      {{ $t('Administravimas') }}
    </NButton>
    </Link>
  </div>
  <!-- Nuorodos esančios visada -->
  <div class="flex flex-col items-start justify-end gap-5 px-6">
    <a href="https://www.vusa.lt/docs" target="_blank">
    <NButton quaternary text>
      <template #icon>
        <IFluentBookExclamationMark20Filled />
      </template>
      {{ $t('Dokumentacija') }}
    </NButton>
    </a>
    <UserSettingsDropdown />
    <div class="mb-4 flex items-center justify-center gap-6 overflow-hidden">
      <NButton text @click="changeLocale">
        <template #icon>
          <NIcon :size="16">
            <img v-if="locale === 'en'" class="transition hover:opacity-70"
              src="https://hatscripts.github.io/circle-flags/flags/gb.svg">
            <img v-else class="transition hover:opacity-70"
              src="https://hatscripts.github.io/circle-flags/flags/lt.svg">
          </NIcon>
        </template>
      </NButton>
      <FeedbackModalButton quaternary text />
      <DarkModeButton style="margin-top: auto; margin-bottom: auto" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import FeedbackModalButton from '../Buttons/FeedbackModalButton.vue';
import DarkModeButton from '../Buttons/DarkModeButton.vue';
import UserSettingsDropdown from './UserSettingsDropdown.vue';
import { useStorage } from '@vueuse/core';
import { watch } from 'vue';
import { loadLanguageAsync } from 'laravel-vue-i18n';
import { capitalize } from '@/Utils/String';

const locale = useStorage("locale", usePage().props.app.locale);

const changeLocale = () => {
  locale.value = locale.value === "en" ? "lt" : "en";
  router.reload({ data: { lang: locale.value } });
};

watch(locale, (locale) => {
  usePage().props.app.locale = locale;
  loadLanguageAsync(locale);
});

</script>

<style scoped></style>
