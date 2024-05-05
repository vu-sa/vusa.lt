<template>
  <NavigationMenu v-model="modelValue" as="div">
    <div class="mr-8">
      <slot name="additional" />
    </div>
    <NavigationMenuList>
      <NavigationMenuItem v-for="item in mainNavigation" :key="item.name">
        <NavigationMenuTrigger
          class="bg-transparent px-2 py-1.5 hover:bg-zinc-100 dark:bg-transparent dark:hover:bg-zinc-700 max-lg:text-xs">
          {{ item.name }}
        </NavigationMenuTrigger>
        <MainNavigationMenuContent :item :for-admin-edit="false" @close-menu="closeMenu" />      
      </NavigationMenuItem>
    </NavigationMenuList>
  </NavigationMenu>
</template>

<script setup lang="ts">
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuList,
  NavigationMenuTrigger,
} from '@/Components/ShadcnVue/ui/navigation-menu'
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import MainNavigationMenuContent from './MainNavigationMenuContent.vue';

const modelValue = ref(null);

const mainNavigation = computed(() => usePage().props.mainNavigation);

function closeMenu(event: MouseEvent) {
  //event.preventDefault();
  modelValue.value = null;
}

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
        image: '/images/photos/VU SA 2023.jpg',
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
        image: 'http://www.vusa.test/images/photos/pirmakursiu_stovykla_kaune.jpg',
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
        image: 'http://www.vusa.test/images/photos/pirmakursiu_stovykla_kaune.jpg',
        description: 'Kiekvienas VU fakultetas turi savo VU SA padalinį, kuris atstovauja studentų interesams. Surask savo!',
        blockClass: 'h-full',
      },
    ],
    [
      {
        name: 'Centrinis biuras',
        url: '/kontaktai/koordinatoriai',
        description: 'VU SA komandos kontaktai, kuriais galite susisiekti su mumis!',
        image: '/images/photos/observatorijos_kiemelis.jpg',
      },
      {
        name: 'Studentų atstovų sąrašas',
        url: '#',
        icon: 'search-24-regular',
        textClass: 'font-bold',
        image: 'http://www.vusa.test/images/photos/pirmakursiu_stovykla_kaune.jpg',
        description: "Ieškok studentų atstovo pagal fakultetą ir darinio tipą."
      },
    ]
    ]
  },
];
</script>
