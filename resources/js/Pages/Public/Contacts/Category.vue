<template>
  <PublicLayout title="Kontaktų kategorijos">
    <div
      class="grid grid-cols-1 gap-0 gap-y-4 px-16 lg:gap-8 lg:px-32 xl:grid-cols-2 2xl:grid-cols-3"
    >
      <button
        v-for="institution in institutions"
        :key="institution.id"
        class="h-64 min-w-[24em] max-w-2xl rounded-2xl"
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
  </PublicLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NButton } from "naive-ui";
import route from "ziggy-js";

import CategoryCard from "@/Components/Public/CategoryCard.vue";
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

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
