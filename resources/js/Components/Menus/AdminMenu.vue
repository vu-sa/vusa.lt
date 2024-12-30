<template>
  <!-- Dinaminės nuorodos į administravimo puslapius -->
  <div class="gap mt-4 flex flex-col px-6 h-full">
    <span class="mb-2 text-xs uppercase text-zinc-500">{{ $t('Funkcijos') }}</span>
    <Link v-if="$page.props.auth?.can.create.meeting" :href="route('dashboard.atstovavimas')">
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
    <!-- Aplankytos nuorodos -->
    <!-- <div v-if="latestItems.length > 0" class="my-4">
      <p class="mb-2 text-xs uppercase text-zinc-500">
        {{ $t('Aplankyti') }}
      </p>
      <div v-for="(item, index) in latestItems" :key="index">
        <Link class="mr-2" :href="`/${item.path}`">
        <NButton quaternary text>
          <template #icon>
            <NIcon :component="getIcon(item.path)" />
          </template>
          {{ item.title ?? item.path }}
        </NButton>
        </Link>
        <NButton v-if="item.favorited" text @click="handleFavorite(index, false)">
          <template #icon>
            <IFluentStar24Filled />
          </template>
        </NButton>
        <NButton v-else text @click="handleFavorite(index, true)">
          <template #icon>
            <IFluentStar24Regular />
          </template>
        </NButton>
      </div>
    </div>
  </div> -->
  <!-- Nuorodos esančios visada -->
  <div class="mt-auto flex flex-col items-start justify-end gap-5">
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
            <img v-if="$page.props.app.locale === 'en'" class="transition hover:opacity-70"
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
  </div>
</template>

<script setup lang="tsx">
import { Link, router, usePage } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { loadLanguageAsync } from 'laravel-vue-i18n';
import entities from '@/entities';
import { watch } from 'vue';

import { capitalize } from '@/Utils/String';
import FeedbackModalButton from '../Buttons/FeedbackModalButton.vue';
import DarkModeButton from '../Buttons/DarkModeButton.vue';
import UserSettingsDropdown from './UserSettingsDropdown.vue';
import IFluentHistory24Regular from '~icons/fluent/history24-regular';

const latestItems = useStorage('latestItems', []);

const changeLocale = () => {
  const toLocale = usePage().props.app.locale === "en" ? "lt" : "en";
  router.reload({ data: { lang: toLocale }, onSuccess: () => loadLanguageAsync(toLocale) });
};

watch(() => usePage().props.app.path, () => {
  // make unique and put to top
  if (latestItems.value.find((item) => item.path === usePage().props.app.path)) {
    latestItems.value = latestItems.value.filter((item) => item.path !== usePage().props.app.path);
  }

  // if contains dashboard or just === '/' then do not add to latest items
  if (['dashboard', 'administration'].some((path) => usePage().props.app.path.includes(path))) {
    return;
  }

  // add title and path
  latestItems.value = [{ title: usePage().props.seo.title, path: usePage().props.app.path, favorited: false }, ...latestItems.value];

  // only slice if not favorited items
  if (latestItems.value.filter((item) => item.favorited).length < latestItems.value.length) {
    latestItems.value = latestItems.value.slice(0, 5);
  }
});

const getIcon = (path: string) => {
  // check if path contains entities key string
  const entity = entities.find((entity) => path.includes(entity.key));
  return entity?.icon ?? <IFluentHistory24Regular />
    ;
};

const handleFavorite = (index: number, favorited: boolean) => {
  latestItems.value[index].favorited = favorited;

  // move to top
  const item = latestItems.value.splice(index, 1);
};

</script>
