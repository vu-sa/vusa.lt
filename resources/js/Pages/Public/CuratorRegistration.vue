<template>

  <Head :title="$t('Registracija į kuratorių programą')" />

  <HeaderWithShapeDivider1 class="full-bleed" image-src="/images/curatorRegistration/curator3.jpg"
    object-position="0% 45%">{{ $t("Registracija į kuratorių programą") }}
  </HeaderWithShapeDivider1>

  <div class="mx-auto grid max-w-7xl grid-cols-1 gap-4 px-4 pt-2 last:pb-2 lg:grid-cols-5 lg:px-16">
    <div class="typography col-span-3 max-w-prose px-12 text-base leading-7">
      <NImage width="600" :src="'/images/curatorRegistration/curator1.jpg'" lazy />
      <template v-if="$page.props.app.locale === 'lt'">
        <p>
          Kiekvieną mokslo metų pradžią VU bendruomenė pradeda pasitikdama
          naujai prisijungusius (-ias) primo kurso studentus (-es). Tik įstojus
          į Universitetą, studentams (-ėms) iškyla daug klausimų, į kuriuos
          atsakyti tegali studijų pradžią jau patyrę studentai (-ės).
        </p>

        <p>
          Vilniaus universiteto Studentų atstovybės (VU SA) vykdoma kuratorių
          programa pradėjo veikti dar 2000 metais ir nuo tų laikų sėkmingai
          prisideda prie bendros VU studentų (-čių) integracijos į Universitetą
          proceso. Specialius mokymus išklausę kuratoriai (-ės) supažindina
          Universiteto pirmakursius (-es) su studijų tvarka ir VU tradicijomis,
          padeda spręsti akademinius bei socialinius klausimus, suprasti
          Universiteto bei miesto infrastruktūrą.
        </p>

        <p>
          Besikeičiant Universitetui ir jo bendruomenei tampant vis labiau
          tarptautinei, kuratorių programa pradėjo apimti ir pirmo kurso
          studentų (-čių) iš užsienio kuravimą. Taip pat, matant poreikį
          vyresnių pakopų studentų (-čių) integracijai, atsirado ir antros
          pakopos (MA) pirmo kurso studentų (-čių) kuratoriai (-ės).
        </p>

        <NImage width="400" :src="'/images/curatorRegistration/curator2.jpg'" lazy />

        <p>
          Šiais metais visame Universitete kuratorių programoje veikė daugiau
          nei 300 studentų (-čių), kurie (-ios) padėjo integruotis daugiau nei
          4000 pirmakursių bei prisidėjo ir prie platesnių integracijos veiklų
          įgyvendinimo: padalinio pirmakursių stovyklų , VU SA Integracijos
          festivalio „Ad Astra“ , pasiruošimo ir studentų (-čių) koordinavimo
          šventinėje VU rugsėjo 1-sios eisenoje, integracijos savaitės
          padaliniuose organizavimo. Taip pat metų eigoje kuratoriai (-ės)
          prisideda prie įvairiausių VU SA vykdomų projektų ir iniciatyvų:
          renginio VUnity, Mentorių programos atnaujinimo, VU SA Studentiško
          gyvenimo vadovo optimizavimo.
        </p>

        <p>
          Ar esi žmogus, kuriam patinka dalintis savo patirtimi bei žiniomis ir
          norėtum nešti teigiamą poveikį pirmakursių gyvenimui?
        </p>

        <p>Tuomet laukiame tavęs kuratorių programoje!</p>

        <p>
          Dešinėje gali matyti, kada
          <strong>2023 m. Kuratorių atrankos prasideda tavo padalinyje!</strong>
        </p>
      </template>
      <template v-else-if="$page.props.app.locale === 'en'">
        <p>
          Every year, the Vilnius University (VU) community starts the academic
          year by welcoming the new first-year students. Once they have joined
          the University, students have many questions that the mentors are
          always ready to answer.
        </p>

        <p>
          The mentoring programme run by the Vilnius University Students'
          Representation (VU SR) started in 2000 and has been successfully
          contributing to the overall process of integration of VU students into
          the University since then. The mentors, who have received special
          training, introduce the University's first-year students to the study
          procedures and traditions of VU, help them to deal with academic and
          social issues, and help them to understand the infrastructure of the
          University and the city.
        </p>

        <p>
          As the University is changing and its community is becoming more
          international, the mentoring programme has started to include
          mentoring of first-year international students. Also, seeing the need
          for the integration of second cycle students, mentors for first-year
          Master’s (MA) students were introduced.
        </p>

        <NImage width="400" :src="'/images/curatorRegistration/curator2.jpg'" lazy />

        <p>
          This year, more than 300 students were involved in the mentoring
          programme across the University, helping to integrate more than 4,000
          first-year students and also contributing to the implementation of
          broader integration activities: organising first-year camps,
          organising the VU SR Integration Festival "Ad Astra", preparing and
          coordinating the students in the celebratory VU community procession
          on 1 September, and organising the Integration Week in the units. In
          the course of the year, the mentors also contribute to a wide range of
          VU SR projects and initiatives: the organisation of the VUnity event,
          the renewal of the Mentor’s Programme, the optimisation of the VU SR
          Student Life Guidebook.
        </p>

        <p>
          Are you a person who enjoys sharing your experience and knowledge and
          would like to make a positive impact on the lives of first-year
          students?
        </p>

        <p>
          Then we are looking forward to welcoming you to the mentoring
          programme!
        </p>

        <p>
          On the right you can see when the
          <strong>2023 mentor’s recruitment</strong> starts in your unit!
        </p>
      </template>
      <NImageGroup :show-toolbar="false">
        <NSpace>
          <NImage v-for="(imageLink, index) in curatorImages" :key="index" width="150" :src="imageLink" lazy />
        </NSpace>
      </NImageGroup>
    </div>
    <aside class="-order-1 col-span-2 gap-6 px-12 text-zinc-800 dark:text-zinc-100 lg:order-1 lg:content-start lg:px-0">
      <h2 class="text-2xl">
        🔗 {{ $t("Nuorodos į kuratorių registracijas") }}!
      </h2>
      <section>
        <p class="mt-8 text-lg font-bold">
          ❗️ {{ $t("Registracijos jau prasidėjo") }}:
        </p>
        <ul>
          <li v-for="padalinys in arrayOfPadaliniaiWithStatus.active" :key="padalinys.id">
            <p>
              {{ "VU" + getFacultyName(padalinys) }}
              <NPopover>
                <template #trigger>
                  <NButton tag="a" :href="padalinys.registration_url" target="_blank" text class="align-center">
                    <template #icon>
                      <IFluentLink20Regular />
                    </template>
                  </NButton>
                </template>
                <!-- Tell when the registration will end -->
                <p>
                  {{ $t("Registracija baigsis") }}
                  <strong>
                    {{
                      formatStaticTime(padalinys.registration_end_time, {
                        month: "long",
                        day: "numeric",
                      })
                    }}
                  </strong>
                </p>
              </NPopover>
            </p>
          </li>
        </ul>
      </section>
      <section>
        <p class="mt-4 text-lg font-bold">
          ⏱️ {{ $t("Registracijos greitu metu prasidės") }}:
        </p>
        <ul>
          <li v-for="padalinys in arrayOfPadaliniaiWithStatus.upcoming" :key="padalinys.id">
            {{ "VU" + getFacultyName(padalinys) }}
            (prasidės
            {{
              padalinys.registration_launch_time !== null
                ? formatStaticTime(padalinys.registration_launch_time, {
                  month: "long",
                  day: "numeric",
                })
                : "netrukus"
            }})
          </li>
        </ul>
      </section>
      <!-- Section for ended registrations -->
      <section>
        <p class="mt-4 text-lg font-bold">
          📅 {{ $t("Registracijos jau baigėsi") }}:
        </p>
        <ul>
          <li v-for="padalinys in arrayOfPadaliniaiWithStatus.ended" :key="padalinys.id">
            {{ "VU" + getFacultyName(padalinys) }}
          </li>
        </ul>
      </section>
    </aside>
  </div>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import { getFacultyName } from "@/Utils/String";
import HeaderWithShapeDivider1 from "@/Components/Headers/HeaderWithShapeDivider1.vue";

const props = defineProps<{
  curatorTenants: {
    id: number;
    fullname: string;
    registration_url: string;
    registration_launch_time: string;
    registration_end_time: string;
  }[];
}>();

// reduce curatorPadaliniai prop to 3 arrays: active, upcoming and ended

const arrayOfPadaliniaiWithStatus = props.curatorPadaliniai.reduce(
  (acc, curr) => {
    const registration_launch_time = new Date(curr.registration_launch_time);
    const registration_end_time = new Date(curr.registration_end_time);
    const now = new Date();

    if (
      registration_launch_time.getTime() <= now.getTime() &&
      registration_end_time.getTime() >= now.getTime() &&
      curr.registration_url !== null
    ) {
      acc.active.push(curr);
    } else if (
      registration_launch_time.getTime() > now.getTime() ||
      curr.registration_url === null
    ) {
      acc.upcoming.push(curr);
    } else if (registration_end_time.getTime() < now.getTime()) {
      acc.ended.push(curr);
    }
    return acc;
  }, // initial value
  {
    active: [],
    upcoming: [],
    ended: [],
  },
);

// const curatorPadaliniaiMap = props.curatorPadaliniai.map((padalinys) => {
//   return {
//     ...padalinys,
//     registration_launch_time:
//       padalinys.registration_launch_time !== null
//         ? new Date(padalinys.registration_launch_time)
//         : null,
//   };
// });

// const curatorActiveRegistrationMap = curatorPadaliniaiMap.filter(
//   (padalinys) =>
//     padalinys.registration_launch_time !== null &&
//     padalinys.registration_launch_time.getTime() <= new Date().getTime() &&
//     padalinys.registration_url !== null
// );

// // get unused padaliniai, that are not in the active registration map
// const curatorUpcomingRegistrationMap = curatorPadaliniaiMap.filter(
//   (padalinys) =>
//     !curatorActiveRegistrationMap.some(
//       (activePadalinys) => activePadalinys.id === padalinys.id
//     )
// );

// create array of urls to curator images where the path is /images/curators/curator{range1-6}.jpg
const curatorImages = [
  "/images/curatorRegistration/curator1.jpg",
  "/images/curatorRegistration/curator3.jpg",
  "/images/curatorRegistration/curator4.jpg",
  "/images/curatorRegistration/curator5.jpg",
  "/images/curatorRegistration/curator6.jpg",
];
</script>
