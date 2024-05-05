<template>
  <NavigationMenu v-model="modelValue" as="div">
    <div class="mr-8">
      <slot name="additional" />
    </div>
    <NavigationMenuList>
      <NavigationMenuItem v-for="item in navigationData" :key="item.name">
        <NavigationMenuTrigger
          class="bg-transparent px-2 py-1.5 hover:bg-zinc-100 dark:bg-transparent dark:hover:bg-zinc-700 max-lg:text-xs">
          {{ item.name }}
        </NavigationMenuTrigger>
        <NavigationMenuContent class="dark:bg-zinc-900">
          <ul
            :class="`grid max-h-[calc(100vh-10rem)] gap-3 overflow-y-auto p-4 md:w-[400px] lg:w-[800px] lg:grid-cols-${item.cols ?? 1} content-stretch`">
            <li v-for="(links, index) in item.links" :key="index">
              <template v-for="link in links" :key="link.name">
                <div v-if="link.type === 'divider'" class="my-3 border-t border-zinc-200" />
                <NavigationMenuLink v-else-if="link.background" as-child
                  class="mb-2 rounded-md bg-zinc-900 transition-colors last:mb-0 hover:bg-zinc-800"
                  :class="[link.blockClass]">
                  <SmartLink class="relative flex" :href="link.url" @click="closeMenu">
                    <!-- <div class="h-24" /> -->
                    <img class="absolute top-0 size-full rounded-md object-cover opacity-25 contrast-150"
                      :src="link.background" alt="Background image">
                    <div class="z-50 inline-block h-fit self-end p-4 align-bottom">
                      <div class="text-lg font-black leading-tight text-zinc-50">
                        {{ link.name }}
                      </div>
                      <p v-if="link.description" class="mt-2 line-clamp-2 leading-snug text-white">
                        {{ link.description }}
                      </p>
                    </div>
                  </SmartLink>
                </NavigationMenuLink>
                <NavigationMenuLink v-else :as="SmartLink"
                  class="my-1 flex h-fit items-center rounded-md leading-none transition-colors" :href="link.url"
                  :class="[linkTypes[link?.type ?? 'block-link']?.blockClass]" @click="closeMenu">
                  <div class="h-fit">
                    <div class="inline-flex items-center" :class="[linkTypes[link?.type ?? 'block-link']?.textClass]">
                      <Icon v-if="link.icon" :icon="`fluent:${link.icon}`" class="mr-2 size-5" />
                      {{ link.name }}
                    </div>
                    <p v-if="link.description" class="mt-1 line-clamp-2 text-sm leading-snug text-zinc-500/90">
                      {{ link.description }}
                    </p>
                  </div>
                </NavigationMenuLink>
              </template>
            </li>
          </ul>
        </NavigationMenuContent>
      </NavigationMenuItem>
    </NavigationMenuList>
  </NavigationMenu>
</template>

<script setup lang="ts">
import {
  NavigationMenu,
  NavigationMenuContent,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  NavigationMenuTrigger,
} from '@/Components/ShadcnVue/ui/navigation-menu'
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { Icon } from "@iconify/vue"
import SmartLink from '../SmartLink.vue';

const modelValue = ref(null);

const mainNavigation = computed(() => usePage().props.mainNavigation);

console.log(mainNavigation.value);

function closeMenu(event: MouseEvent) {
  //event.preventDefault();
  modelValue.value = null;
}

const linkTypes = {
  'link': {
    'textClass': 'hover:underline focus:underline',
    'blockClass': 'py-1 px-2.5 hover:bg-transparent focus:bg-transparent hover:underline',
  },
  'block-link': {
    'textClass': 'no-underline',
    'blockClass': 'p-2.5 hover:bg-zinc-100 focus:bg-zinc-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800',
  },
  'category-link': {
    'textClass': 'no-underline',
    'blockClass': 'p-2.5 font-bold hover:bg-zinc-100 focus:bg-zinc-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800',
  },
};

const navigationData = [
  {
    name: 'Apie mus',
    url: '#',
    cols: 2,
    links: [[
      {
        name: 'Kas yra VU SA?',
        url: '#',
        description: 'Daugiau nei 30 metų atstovaujame Vilniaus universiteto studentų interesus.',
        background: '/images/photos/VU SA 2023.jpg',
        blockClass: 'h-full',
      },
    ], [
      {
        name: 'Tvarumas',
        url: '/tvarumas',
        type: 'block-link',
      },
      {
        name: 'Struktūra',
        url: 'https://vusa.lt/tvarumas',
        type: 'block-link',
      },
      {
        name: 'Strategija',
        url: '#',
      },
      {
        name: 'Išoriniai projektai',
        url: '#',
      },
      {
        name: 'Logotipas',
        url: '#',
      },
      {
        name: 'Dokumentai',
        url: '#',
        icon: 'document-bullet-list-multiple-24-regular',
        type: 'category-link',
      },
      {
        name: 'Veiklą reglamentuojantys dokumentai',
        url: '#',
        type: 'link',
      },
      {
        name: 'Protokolai ir nutarimai',
        url: '#',
        type: 'link',
      },
      {
        name: 'Raštai, pozicijos ir rezoliucijos',
        url: '#',
        type: 'link',
      },
      {
        name: 'Veiklos ir tyrimų ataskaitos',
        url: '#',
        type: 'link',
      },
      {
        name: 'Šablonai',
        url: '#',
        type: 'link',
      },
    ]],
  },
  {
    name: 'Studijos ir mokslas',
    cols: 2,
    links: [[
      {
        name: 'Akademinė informacija',
        url: '#',
        icon: 'document-bullet-list-multiple-24-regular',
        type: 'category-link'
      },
      {
        name: 'Atsiskaitymai, apeliacijos ir ginčai',
        url: '#',
        type: 'link',
      },
      {
        name: 'BUS, gretutinės studijos ir pasirenkamieji dalykai',
        url: '#',
        type: 'link',
      },
      {
        name: 'Akademinės atostogos, studijų stabdymas ir atnaujinimas',
        url: '#',
        type: 'link',
      },
      {
        name: 'Studijos ir praktika užsienyje',
        url: '#',
        type: 'link',
      },
      {
        name: 'Studentų teisės ir pareigos',
        url: '#',
        type: 'link',
      },
      {
        type: 'divider',
      },
      {
        name: 'Vilniaus universiteto sprendimų struktūros',
        url: '#',
      },
      {
        name: 'Seksualinio priekabiavimo ir diskriminacijos prevencija',
        url: '#',
      },
      {
        name: 'Prašymų formos',
        url: '#',
      },
      {
        name: 'Studijas reglamentuojantys dokumentai',
        url: '#',
      },
      {
        name: 'Atmintinės',
        url: '#',
        icon: 'document-bullet-list-multiple-24-regular',
      },
    ],
    [
      {
        name: 'Finansinė parama',
        url: '#',
        icon: 'document-bullet-list-multiple-24-regular',
        type: 'category-link',
      },
      {
        name: 'Stipendijos',
        url: '#',
        type: 'link',
      },
      {
        name: 'Parama neįgaliesiems',
        url: '#',
        type: 'link',
      },
      {
        name: 'Parama išeivijos vaikams',
        url: '#',
        type: 'link',
      },
      {
        name: 'Paskolos',
        url: '#',
        type: 'link',
      },
      {
        name: 'Įmokos už studijas',
        url: '#',
        type: 'link',
      },
      {
        name: 'LSP',
        url: '#',
        icon: 'contact-card-32-regular',
        type: 'category-link',
      },
      {
        name: 'Gamyba',
        url: '#',
        type: 'link',
      },
      {
        name: 'Grąžinimas',
        url: '#',
        type: 'link',
      },
      {
        name: 'Praradimas',
        url: '#',
        type: 'link',
      },
    ]]
  },
  {
    name: 'Iniciatyvos',
    cols: 2,
    links: [[
      {
        name: 'Programos, klubai ir projektai',
        url: '#',
        background: 'http://www.vusa.test/images/photos/pirmakursiu_stovykla_kaune.jpg',
        description: 'VU SA buria 20 iniciatyvų: kiekvienas studentas gali rasti save dominančią veiklą!',
        blockClass: 'h-full',
      },
    ],
    [
      {
        name: 'Studentiško gyvenimo vadovas',
        url: '#',
        description: 'Kiekvienam (-ai) pirmakursiui (-ei) skirtas vadovas, supažindinantis su studentišku gyvenimu!',
      },
      {
        name: 'Įkurk savo iniciatyvą!',
        url: '#',
        description: 'VU SA finansiškai ir organizaciniais ištekliais remia naujų idėjų įgyvendinimą!',
        textClass: 'font-bold',
      }
    ]
    ]
  },
  {
    name: 'Kontaktai',
    cols: 2,
    links: [[
      {
        name: 'Komandos padaliniuose',
        url: '#',
        background: 'http://www.vusa.test/images/photos/pirmakursiu_stovykla_kaune.jpg',
        description: 'Kiekvienas VU fakultetas turi savo VU SA padalinį, kuris atstovauja studentų interesams. Surask savo!',
        blockClass: 'h-full',
      },
    ],
    [
      {
        name: 'Centrinis biuras',
        url: '/kontaktai/koordinatoriai',
        description: 'VU SA komandos kontaktai, kuriais galite susisiekti su mumis!',
        background: '/images/photos/observatorijos_kiemelis.jpg',
      },
      {
        name: 'Studentų atstovų sąrašas',
        url: '#',
        icon: 'search-24-regular',
        textClass: 'font-bold',
        background: 'http://www.vusa.test/images/photos/pirmakursiu_stovykla_kaune.jpg',
        description: "Ieškok studentų atstovo pagal fakultetą ir darinio tipą."
      },
    ]
    ]
  },
];
</script>
