<template>
  <FadeTransition appear>
    <div class="my-8 min-w-full max-w-7xl">
      <section
        title="Search bar"
        class="mx-auto flex h-[40vw] max-h-[90vw] w-fit justify-center bg-[url('/images/photos/observatorijos_kiemelis.jpg')] bg-cover object-cover px-32 text-zinc-600 dark:text-zinc-100"
      >
        <div
          class="my-16 flex flex-col items-center justify-center rounded-sm bg-zinc-900/90 px-20 py-8"
        >
          <h2 class="text-4xl font-black text-zinc-100">
            Studentiškos iniciatyvos - tau po ranka!
          </h2>
          <p class="my-8 w-[65ch] text-center text-zinc-300">
            Šis iniciatyvų puslapis leis tau surasti iniciatyvą Vilniaus
            universitete, prie kurios galėsi prisidėti jau dabar!
          </p>
          <NInput
            round
            placeholder="Ieškok veiklos pagal pavadinimą..."
            class="my-4 max-w-[75%]"
          ></NInput>
          <div class="flex">
            <NFormItem label="Padalinys" class="w-64"
              ><NSelect
                :options="$page.props.padaliniai"
                label-field="shortname"
                value-field="id"
                class="w-64"
                size="small"
              ></NSelect
            ></NFormItem>
          </div>
          <NButton size="large" type="primary">
            <template #icon>
              <NIcon :component="Search24Regular"></NIcon>
            </template>
            Ieškom!</NButton
          >
        </div>
      </section>
      <section class="mx-auto my-8 max-w-7xl px-32" title="Initiative cards">
        <h3 class="my-12 text-center text-3xl font-black">Iniciatyvos</h3>
        <div class="grid grid-cols-ramFill gap-x-4 gap-y-8">
          <InitiativeCard
            v-for="initiative in initiatives"
            :key="initiative.id"
            :initiative="initiative"
          ></InitiativeCard>
        </div>
      </section>
    </div>
  </FadeTransition>
</template>

<script setup lang="tsx">
import {
  NButton,
  NFormItem,
  NIcon,
  NInput,
  NInputGroup,
  NSelect,
} from "naive-ui";
import { Search24Regular } from "@vicons/fluent";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import InitiativeCard from "./Initiatives/InitiativeCard.vue";

defineProps<{
  initiatives: App.Entities.Initiative[];
}>();
</script>
