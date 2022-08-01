<template>

  <Head title="Kontaktų kategorija"></Head>
  
    <div
      class="mt-4 grid grid-cols-1 gap-y-4 px-8 md:grid-cols-2 md:gap-8 lg:px-32 2xl:grid-cols-3"
    >
      <button
        v-for="institution in institutions"
        :key="institution.id"
        class="h-48 min-w-[14em] max-w-xl rounded-2xl lg:h-64"
        :class="{ 'shadow-md': institution.image_url }"
        style="white-space: normal"
        @click="inertiaVisitOnClick(institution.alias)"
      >
        <CategoryCard
          :title="institution.name ?? institution.short_name"
          :image="institution.image_url"
        >
          <div class="mt-4 flex justify-center gap-2">
            <NButton
              ghost
              :color="institution.image_url ? 'white' : 'black'"
              round
              size="large"
              type="primary"
              @click.stop="
                Inertia.visit(
                  route('padalinys.contacts.alias', {
                    alias: 'koordinatoriai',
                    padalinys: institution.alias,
                    lang: 'lt',
                  })
                )
              "
              >Koordinatoriai</NButton
            >
            <NButton
              ghost
              size="large"
              :color="institution.image_url ? 'white' : 'black'"
              round
              type="primary"
              @click.stop="
                Inertia.visit(
                  route('padalinys.contacts.alias', {
                    alias: 'kuratoriai',
                    padalinys: institution.alias,
                    lang: 'lt',
                  })
                )
              "
              >Kuratoriai</NButton
            >
          </div>
        </CategoryCard>
      </button>
    </div>
</template>

<script lang="ts">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

export default {
  layout: PublicLayout,
};
</script>

<script setup lang="ts">
import { Head } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { NButton } from "naive-ui";
import route from "ziggy-js";

import CategoryCard from "@/Components/Public/CategoryCard.vue";

defineProps<{
  institutions: App.Models.DutyInstitution[];
}>();

const inertiaVisitOnClick = (alias: string) => {
  Inertia.visit(
    route("main.contacts.alias", {
      alias: alias,
      lang: "lt",
    }),
    {},
    {
      preserveScroll: false,
    }
  );
};
</script>
