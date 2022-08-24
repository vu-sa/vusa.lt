<template>
  <div>
    <div class="group relative">
      <ShapeDivider1 class="absolute -top-1 z-10"></ShapeDivider1>
      <ShapeDivider1 class="absolute -bottom-1 z-10 rotate-180"></ShapeDivider1>
      <Link
        :href="
          route('main.ataskaita2022', {
            lang: $page.props.locale,
            permalink: 'pradzia',
          })
        "
      >
        <img
          src="/images/ataskaita2022/kitos-nuotraukos/VU SA.jpg"
          class="my-4 h-48 w-full object-cover duration-200 hover:opacity-90 lg:h-80"
          style="object-position: 0% 35%"
        />
      </Link>
    </div>

    <div class="prose-sm mx-8 lg:mx-32 lg:px-32">
      <h2
        class="my-4 text-2xl font-bold text-gray-900 duration-200 hover:text-vusa-red lg:w-4/5 lg:text-3xl"
      >
        <Link
          class="flex w-fit flex-row items-center gap-2"
          style="text-decoration: none"
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.locale,
              permalink: 'pradzia',
            })
          "
          ><template v-if="$page.props.locale === 'lt'"
            >Ką veikė VU SA 2021–2022 metais?</template
          ><template v-else>What did VU SR do in 2021–2022?</template>

          <NIcon class="" style="font-size: 24pt">
            <ArrowCircleRight20Regular />
          </NIcon>
        </Link>
      </h2>
      <div class="space-between flex flex-row">
        <p
          class="prose-sm mb-4 text-sm text-gray-700 sm:prose lg:max-w-[80ch] lg:text-base"
        >
          <template v-if="$page.props.locale === 'lt'">
            Ataskaitos knygutė – kiekvienais metais skelbiama VU SA nuveiktų
            darbų, atliktų projektų, įgyvendintų iniciatyvų ataskaita. Artėjant
            VU SA ataskaitinei-rinkiminei konferencijai dalinamės ir šių,
            2021–2022 metų nuveiktų darbų pilna knygute. Ji papildyta ir VU SA
            bendruomenės narių veidais bei smagiomis akimirkomis. Kviečiame
            skaityti!
          </template>
          <template v-else
            >Report booklet – every year, a report on the work done, projects
            completed, and initiatives implemented at VU SR is published. As the
            VU SR Annual Convention approaches, we share a book full of these
            works done in 2021–2022. It is also filled with faces and fun
            moments of the VU SR community members. Feel free to read!</template
          >
        </p>
      </div>
      <NDivider />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowCircleRight20Regular } from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NDivider, NIcon } from "naive-ui";
import route from "ziggy-js";

import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";
</script>
