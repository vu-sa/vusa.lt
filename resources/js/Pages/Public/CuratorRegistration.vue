<template>
  <Head title="Registracija į kuratorių programą" />

  <FadeTransition appear>
    <div>
      <HeaderWithShapeDivider1
        :is-theme-dark="isThemeDark"
        image-src="/images/photos/stovykla.jpg"
        >Registracija į kuratorių programą
      </HeaderWithShapeDivider1>

      <div
        class="mx-auto grid max-w-7xl grid-cols-1 gap-4 px-4 pt-2 last:pb-2 lg:grid-cols-5 lg:px-16"
      >
        <div class="prose prose-sm col-span-3 px-12 dark:prose-invert">
          <h2>Kuratorių registracijos</h2>
        </div>
        <section
          class="-order-1 col-span-2 flex flex-wrap justify-center gap-6 px-12 lg:order-1 lg:content-start lg:px-0"
        >
          <strong class="text-xl text-zinc-800 dark:text-zinc-100"
            >Nuorodos į kuratorių registracijas! 🔗 ⬇️</strong
          >
          <div
            v-for="padalinys in curatorPadaliniai"
            :key="padalinys.id"
            class="group h-fit w-48 rounded-b-md bg-white/0"
          >
            <a target="_blank" :href="padalinys.registration_url">
              <div
                class="flex h-24 items-center justify-center rounded-xl object-cover shadow-md transition group-hover:shadow-xl"
              >
                <h3
                  class="p-4 text-center text-lg font-extrabold leading-tight"
                >
                  {{ "VU" + getFacultyName(padalinys) }}
                </h3>
              </div>
            </a>
          </div>
        </section>
      </div>
    </div>
  </FadeTransition>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";

import { NButton } from "naive-ui";
import { getFacultyName } from "@/Utils/String";
import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import HeaderWithShapeDivider1 from "@/Components/Headers/HeaderWithShapeDivider1.vue";

defineProps<{
  curatorPadaliniai: {
    id: number;
    fullname: string;
    registration_url: string;
    registration_launch_time: string;
  }[];
}>();

const isThemeDark = ref(isDarkMode());

onMounted(() => {
  updateDarkMode(isThemeDark);
});
</script>
