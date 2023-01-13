<template>
  <Pradzia>
    <div
      class="min-w-[1/2] max-w-[3/4] w-auto col-span-4 md:col-span-2 md:sticky md:top-36 md:self-start"
    >
      <img
        class="my-4 md:w-4/5 col-span-4 rounded-sm shadow-md hover:shadow-lg duration-200 h-auto object-cover mx-auto mb-4 w-3/4"
        src="/images/ataskaita2022/kitos-nuotraukos/VUSAARK_EN-1-01.jpg"
      />
      <div class="flex gap-2 justify-center flex-wrap">
        <Link
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.app.locale,
              permalink: 'sveikinimai',
            })
          "
          ><NButton strong round secondary>{{
            $t("Sveikinimai")
          }}</NButton></Link
        >

        <Link
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.app.locale,
              permalink: 'vu-sa',
            })
          "
        >
          <NButton strong round secondary>{{ $t("Apie VU SA") }}</NButton></Link
        >

        <Link
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.app.locale,
              permalink: 'mvp',
            })
          "
          ><NButton strong round secondary>{{
            $t("Metų veiklos planas")
          }}</NButton></Link
        >
        <Link
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.app.locale,
              permalink: 'studijos',
            })
          "
          ><NButton strong round secondary>{{
            $t("Strateginės kryptys")
          }}</NButton></Link
        >

        <Link
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.app.locale,
              permalink: 'sritys',
            })
          "
          ><NButton strong round secondary>{{
            $t("Bendruomenė")
          }}</NButton></Link
        >
      </div>
    </div>
    <div class="col-span-4 md:col-span-2 mt-4 md:mt-0 prose">
      <img
        class="carousel-img"
        style="object-position: 0% 40%"
        src="/images/ataskaita2022/sveikinimai/neda.jpg"
      />
      <div class="bg-white p-6 text-sm prose-sm">
        <p>
          Dear students, members of the Vilnius University community, and dear
          VU SR,
        </p>

        <p>
          This year has been truly extraordinary - you want to be happy and
          thank you, and at the same time, it's hard to see how much is going on
          around you!
        </p>

        <p>
          This year we have managed to achieve significant results both in
          representing students and strengthening the Organization: the
          consultation process of VU SR students has been renewed, the
          development of a culture of self-reflection has started, the
          Organization's strategic action plan has been developed, requirements
          for higher education have been harmonised, the financing mechanism of
          higher education institutions has been supplemented with an incentive
          for the quality of studies, the state budget provides for a larger
          share of funds to finance research activities, a preliminary draft of
          the social dimension plan has been prepared and the legal status of
          international students.
        </p>

        <p>
          I am proud of the Organization that all this work has been achieved in
          a still-pending pandemic situation. I am even more proud that we have
          been able to respond quickly and meaningfully to Russia's war in
          Ukraine by expanding the mentoring program for students from Ukraine,
          initiating more financial support, and providing free accommodation at
          the University.
        </p>

        <p>
          This year we completed the three-year project “Improving Public
          Governance in Education with the Involvement of Non-Governmental
          Organizations”, thanks to which VU SR has already expanded the wide
          range of topics and issues related to students - we can rely on
          specific and research-based data. (which are often difficult or
          non-existent in external sources) and have been and will be the
          subject of significant proposals and changes for students at both the
          University and national levels.
        </p>

        <p>
          We would not have done all this and many things not yet listed without
          solid partnerships and united work together! So I would like to say a
          big THANK YOU to everyone who contributed to making it better for the
          students and the Organization.
        </p>

        <p>
          I invite you to get acquainted with the works and changes that we have
          created and for which we have laid the foundations!
        </p>

        <p>Unanimously for the Future of Students!</p>

        <strong>President of VU SR Neda Žutautaitė</strong>
      </div>
    </div>
  </Pradzia>
</template>

<script setup lang="ts">
import Pradzia from "../0-Pradzia.vue";

import { Link } from "@inertiajs/inertia-vue3";
import { NButton } from "naive-ui";

</script>

f
<style scoped>
.carousel-img {
  width: 100%;
  height: 280px;
  object-fit: cover;
  border-radius: 1em 1em 0 0;
  margin-top: 0;
  margin-bottom: 0;
}
</style>
