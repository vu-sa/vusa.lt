<template>
  <div class="full-bleed group relative min-h-48">

    <Head>
      <link rel="preload" href="/images/photos/stovykla.jpg" as="image">
    </Head>

    <ShapeDivider1 class="absolute -top-1 z-1" />
    <ShapeDivider1 class="absolute -bottom-2 z-1 rotate-180 lg:-bottom-1" />
    <div class="relative">
      <img src="/images/photos/stovykla.jpg" loading="lazy"
        class="mt-2 h-32 w-full object-cover brightness-50 lg:my-1 lg:h-64" style="object-position: 0% 45%">
    </div>
  </div>
  <div class="mt-4 flex flex-col justify-center gap-4 lg:flex-row lg:items-center">
    <div class="typography text-base leading-7">
      <p class="text-2xl font-bold lg:w-4/5">
        VU SA
        <span class="font-extrabold text-vusa-red">pirmakursių stovyklos</span>
        jau greitai!
      </p>
      <p class="w-4/5">
        Sek naujienas mūsų
        <a href="https://www.facebook.com/VUstudentuatstovybe">Facebook</a>
        puslapyje 🔍 ir <a href="https://www.instagram.com/VUstudentuatstovybe/">Instagram</a> paskyroje 📸.
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-4 lg:justify-center">
      <SmartLink :href="route('pirmakursiuStovyklos', {
        year: null,
        lang: 'lt',
      })">
        <NButton round size="large" strong type="primary">Pirmakursių stovyklos<template #icon>
           <IFluentBalloon20Regular />
          </template>
        </NButton>
      </SmartLink>
      <SmartLink :href="route('pirmakursiuStovyklos', {
        year: 2023,
        lang: 'lt',
      })">
        <NButton round size="large" strong secondary>2023 m. stovyklos<template #icon>
            <IFluentHatGraduation20Filled />
        </template></NButton>
      </SmartLink>
      <SmartLink :href="route('contacts.category', {
        type: 'padaliniai',
        lang: 'lt',
        subdomain: 'www',
      })
        ">
        <NButton round strong secondary>VU SA kontaktai tavo fakultete
          <template #icon>
            <IFluentPerson20Filled />
          </template>
        </NButton>
      </SmartLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";
import SmartLink from "../SmartLink.vue";
</script>
