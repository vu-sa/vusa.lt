<template>
  <HeroElement is-first-element :element="heroElement" />
  <div class="grid grid-cols-2 gap-x-12 gap-y-8 items-center w-full h-full px-4 py-12">
    <img class="px-12" src="https://placehold.co/600x400/e2e8f0/475569?text=People+Talking">
    <div>
      <h2 class="text-4xl font-extrabold mb-1">
        Ar ieškai naujos aplinkos ir draugų?
      </h2>
      <p class="mt-4 text-lg leading-tight">
        Vilniaus universiteto Studentų atstovybė (VU SA) – tai bendruomenė, jungianti visus Universiteto fakultetus,
        telkianti skirtingas asmenybės bendru tikslu. Stiprioje, motyvuotoje aplinkoje rasi bendraminčių bendruomenę ar
        draugų.
      </p>
    </div>
    <div>
      <h2 class="text-4xl font-extrabold mb-1 text-right">
        Ar nori palikti prasmingą pokytį?
      </h2>
      <p class="mt-4 text-lg leading-tight text-right">
        Vilniaus universiteto Studentų atstovybė (VU SA) – tai bendruomenė, jungianti visus Universiteto fakultetus,
        telkianti skirtingas asmenybės bendru tikslu. Stiprioje, motyvuotoje aplinkoje rasi bendraminčių bendruomenę ar
        draugų.
      </p>
    </div>
    <img class="px-12 w-4/5 mx-auto" src="https://placehold.co/600x400/e2e8f0/475569?text=Brand+Communication">
    <img class="px-12 w-4/5 mx-auto" src="https://placehold.co/600x400/e2e8f0/475569?text=Feedback">
    <div>
      <h2 class="text-4xl font-extrabold mb-1">
        Ar sieki augti karjeroje ir kaip asmenybė?
      </h2>
      <p class="mt-4 text-lg leading-tight">
        Kartu su komanda augama kaip asmenybės, gali atrasti savo stiprybes ir mėgstamas sritis – galbūt atrasi
        patinkančią karjeros kryptį, o profesinį gyvenimą pradėsi su stipresniu CV bei daug įgūdžių išmokęs (-usi)
        užbėgdamas (-a) už akių.
      </p>
    </div>
  </div>
  <RCNumberSection class="bg-gray-100 full-bleed" :element="numberElement" />
  <div class="h-84 hidden full-bleed" />
  <div class="py-8 full-bleed px-24">
    <div class="text-center mb-12">
      <h2 class="text-4xl font-extrabold">
        Studentų atstovybės veiklos kryptys
      </h2>
    </div>
    <div class="grid-cols-3 grid gap-x-8">
      <div>
        <h3 class="text-2xl leading-6 mb-4">
          Akademinės ir socialinės aplinkos gerinimas
        </h3>
        <p class="text-base">
          Prisidėk prie studijų kokybės, infrastruktūros ir mokymosi sąlygų Universitete gerinimo, puoselėsi skirtingų
          socialinių grupių įtrauktį, gerovę, tarptautinių galimybių stiprinimą bei padėk užtikrinti tvarią
          organizacijos ir universiteto veiklą.
        </p>
      </div>
      <div>
        <h3 class="text-2xl leading-6 mb-4">
          Saviraiškos galimybių užtikrinimas
        </h3>
        <p class="text-base">
          Prisidėk prie sąlygų sudarymo visiems (-oms) studentams (-ėms) kurti naujas iniciatyvas bei įsitraukti į jau
          esamas veiklas ir tarp jų laisvai judėti. Kelk jų kompetencijas ir suteik našiai veiklai reikalingus
          išteklius.
        </p>
      </div>
      <div>
        <h3 class="text-2xl leading-6 mb-4">
          Studentų (-čių) kompetencijų ugdymas
        </h3>
        <p class="text-base">
          Prisidėk prie įvairių, su kompetencijų kėlimu susijusių, projektų inicijavimo ir įgyvendinimo, kūrybiškai
          generuok naujas mokymų veiklas, kurios padės studentams (-ėms) įsisavinti žinias bei turimus įgūdžius
          stiprinti.
        </p>
      </div>
    </div>
  </div>
  <div class="my-8">
    <!-- Set image placeholder 4x2 grid -->
    <div class="grid grid-cols-4 gap-5 items-center *:rounded-2xl">
      <img src="/images/curatorRegistration/curator1.jpg">
      <img src="/images/photos/VU SA 2022.jpg">
      <img src="/images/photos/pirmakursiu_stovykla_kaune.jpg">
      <img src="/images/photos/stovykla.jpg">
      <img src="/images/photos/vu.jpg">
      <img src="/images/curatorRegistration/curator1.jpg">
      <img src="/images/icons/pictograms/image00008.jpg">
      <img src="/images/photos/VU SA 2023.jpg">
    </div>
  </div>
  <div class="hidden">
    <h3> Būdų yra daug... </h3>
    ...
  </div>
  <div class="my-8 bg-zinc-100 full-bleed px-24 py-12">
    <div class="text-center mb-12">
      <h2 class="text-4xl">
        Mūsų vertybės sutampa?
      </h2>
    </div>
    <div class="grid-cols-3 grid gap-16">
      <div class="flex flex-col gap-4">
        <img src="https://placehold.co/400x300/e2e8f0/475569?text=Eco+Conscious" alt="Ekologiškumas">
        <h3 class="text-2xl leading-6">
          Ekologiškumas
        </h3>
        <p class="text-base">
          Ekologiškumas neturi būti tik madinga sąvoka, bet ir mūsų kasdienė praktika ir atsakomybė. Net mažiausi
          veiksmai gali tapti dideliais pokyčiais, kaip mūsų švaresnio ir tvaresnio žalio universiteto tikslas.
        </p>
      </div>
      <div class="flex flex-col gap-4">
        <h3 class="text-2xl leading-6">
          Tarptautiškumas
        </h3>
        <p class="text-base">
          Mes tikime tarptauškimu akademinėje veikloje, bet nereikia net išvykti į ERASMUS+, kad svetimų kalbų išgirsti
          ir Universiteto koridoriuose. Atstovybėje nebijome kalbėti antrąja ar trečiąja kalba, jog visi (-os) jaustųsi
          saugiai galėdami kitus suprasti, jog visi (-os) Universiteto studentai (-ės) galėtų įsitraukti į VU SA
          veiklas.
        </p>
        <img src="https://placehold.co/400x300/e2e8f0/475569?text=Translator" alt="Tarptautiškumas">
      </div>
      <div>
        <img src="https://placehold.co/400x300/e2e8f0/475569?text=Inclusive" alt="Įtraukumas">
        <h3 class="text-2xl leading-6 mb-4">
          Įtraukumas
        </h3>
        <p class="text-base">
          Esame skirtingi, įvairūs – ir tai yra gražu. Universiteto bendruomenę sudaro LGBTQ+, žmonės su negalia (matoma
          ir nematoma), socio-ekonomiškai besiskiriantys studentai (-ės). Dažnai mažumos interesai nukenčia arba nėra
          dar atliepti, todėl turime visi aktyviai juos atstovauti.
        </p>
      </div>
    </div>
  </div>
  <div class="my-8 mb-16 py-8">
    <p class="text-center text-4xl font-extrabold">
      Vilniaus universiteto Studentų atstovybė laukia tavęs – veikime, kurkime ir tobulėkime kartu!
    </p>
    <div class="my-8 text-center">
      <Link href="/registracija/nariu-registracija">
      <Button size="xl" class="rounded-full hover:shadow-md cursor-pointer">
        <span class="text-xl font-bold">Narystės registracija</span>
      </Button>
      </Link>
    </div>
  </div>
  <div class="grid-cols-3 grid gap-x-8">
    <div>
      <h3 class="text-2xl leading-6 mb-4">
        Mokymai
      </h3>
      <p class="text-base">
        Tapus nariu tavęs laukia studentų atstovų, kuratorių ir kiti mokymai, skirti įgyti vertingų žinių ir įgūdžių:
        nuo komandinio darbo iki Lietuvos aukštojo mokslo sistemos supratimo. Šie mokymai pravers ne tik veikloje
        atstovybėje, bet ir tavo asmeniniame bei profesiniame kelyje
      </p>
    </div>
    <div>
      <h3 class="text-2xl leading-6 mb-4">
        Priedas prie diplomo
      </h3>
      <p class="text-base">
        Aktyviai dalyvaudamas (-a) Vilniaus universiteto Studentų atstovybės veiklose gali gauti priedą prie savo
        diplomo! Šis dokumentas įvertins tavo savanorystės valandas ir įgytą patirtį – privalumas darbo rinkoje.
      </p>
    </div>
    <div>
      <h3 class="text-2xl leading-6 mb-4">
        Stipendija už savanorystę
      </h3>
      <p class="text-base">
        Už aktyvią ir į rezultatą orientuotą savanorišką veiklą kiekvieną semestrą gali gauti VU visuomeninės veiklos
        stipendiją, įvertinančią tavo laiką ir pastangas, iniciatyvas bei pasiekimus, prisidedančius prie Vilniaus
        universiteto bendruomenės stiprinimo.
      </p>
    </div>
  </div>

  <div class="my-24 mx-16">
    <h3 class="text-4xl leading-6 mb-8">
      Ko mūsų klausiate?
    </h3>
    <Accordion type="single" collapsible class="py-8 rounded-lg">
      <AccordionItem v-for="(item, index) in accordionItems" :key="index" :value="'item-' + (index + 1)">
        <AccordionTrigger><span class="font-medium">{{ item.title }}</span></AccordionTrigger>
        <AccordionContent>
          <span class="text-base">
            {{ item.content }}
          </span>
        </AccordionContent>
      </accordionitem>
    </Accordion>
  </div>
</template>

<script setup lang="ts">
import HeroElement from '@/Components/RichContent/RCHeroSection/HeroElement.vue';
import RCNumberSection from '@/Components/RichContent/RCNumberStatSection/RCNumberSection.vue';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ShadcnVue/ui/accordion';
import Button from '@/Components/ShadcnVue/ui/button/Button.vue';
import type { Hero, NumberStatSection } from '@/Types/contentParts';
import { Link } from '@inertiajs/vue3';

const accordionItems = [
  {
    title: 'How can I become a member?',
    content: 'You can become a member by filling out an application form on our website or visiting our office on campus. Membership is open to all enrolled students.'
  },
  {
    title: 'What are the benefits of membership?',
    content: 'As a member, you will have access to various resources, training programs, and networking opportunities that can enhance your academic and professional journey.'
  },
  {
    title: 'Is there a fee for membership?',
    content: 'Membership is free for all enrolled students at Vilnius University.'
  }
];

const numberElement: NumberStatSection = {
  json_content: [
    {
      endNumber: 35,
      label: 'metų',
    },
    {
      endNumber: 1100,
      label: 'narių',
    },
    {
      endNumber: 300,
      label: 'studentų atstovai (-ės)',
    },
  ],
  options: {
    color: 'yellow',
    title: ''
  }
};

const heroElement: Hero = {
  json_content: {
    title: '„...“',
    subtitle: '...',
    backgroundMedia: "https://placehold.co/1920x1080/e2e8f0/475569?text=Hero+Background",
    buttonText: "Sužinok daugiau!",
    buttonLink: "/registracija/nariu-registracija",
  },
  options: {
    is_active: true,
    buttonColor: "yellow",
    // backgroundBlur: true,
  }
};
</script>
