<template>
  <div class="full-bleed group relative">
    <ShapeDivider1 class="absolute -top-1 z-10"></ShapeDivider1>
    <ShapeDivider1
      class="absolute -bottom-2 z-10 rotate-180 lg:-bottom-1"
    ></ShapeDivider1>
    <a href="https://ataskaita2023.vusa.lt" target="_blank">
      <img
        src="/images/photos/VU SA 2023.jpg"
        loading="lazy"
        class="mt-2 h-48 w-full object-cover lg:my-1 lg:h-64"
        style="object-position: 0% 50%"
      />
    </a>
  </div>

  <div class="typography lg:px-32">
    <h2
      class="my-4 border-0 text-2xl font-bold text-gray-900 duration-200 hover:text-vusa-red dark:text-zinc-50 lg:w-4/5 lg:text-3xl"
    >
      <a
        class="flex w-fit flex-row items-center gap-2"
        style="text-decoration: none"
        href="https://ataskaita2023.vusa.lt"
        target="_blank"
        ><template v-if="$page.props.app.locale === 'lt'"
          >Ką veikė VU SA 2022–2023 metais?</template
        ><template v-else>What did VU SR do in 2022–2023?</template>

        <NIcon class="" style="font-size: 24pt">
          <ArrowCircleRight20Regular />
        </NIcon>
      </a>
    </h2>
    <div class="space-between flex flex-row">
      <p
        class="mb-4 leading-6 lg:max-w-[80ch] text-base"
      >
        <template v-if="$page.props.app.locale === 'lt'">
          Ataskaitos knygutė – kiekvienais metais skelbiama VU SA nuveiktų
          darbų, atliktų projektų, įgyvendintų iniciatyvų ataskaita. Artėjant VU
          SA ataskaitinei-rinkiminei konferencijai dalinamės ir šių, 2022–2023
          metų nuveiktų darbų pilna knygute. Ji papildyta ir VU SA bendruomenės
          narių veidais bei smagiomis akimirkomis. Kviečiame skaityti!
        </template>
        <template v-else
          >Report booklet – every year, a report on the work done, projects
          completed, and initiatives implemented at VU SR is published. As the
          VU SR Annual Convention approaches, we share a book full of these
          works done in 2022–2023. It is also filled with faces and fun moments
          of the VU SR community members. Feel free to read!</template
        >
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowCircleRight20Regular } from "@vicons/fluent";
import { NIcon } from "naive-ui";

import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

defineOptions({ suspensible: false });
</script>
