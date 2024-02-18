<template>
  <Head
    :title="
      $page.props.app.locale === 'lt'
        ? 'Programos, klubai ir projektai'
        : 'Programs, clubs and projects'
    "
  />
  <h1 class="my-8">
    {{
      $page.props.app.locale === "lt"
        ? "Programos, klubai ir projektai"
        : "Programs, clubs and projects"
    }}
  </h1>
  <p class="text-lg">
    {{
      $page.props.app.locale === "lt"
        ? "Jei šiame sąraše nerandi to, ko ieškai, gali iniciatyvą įkurti pats!"
        : "Can't see something that interests you? You can create an initiative yourself!"
    }}
  </p>

  <a
    class="my-4"
    :href="
      $page.props.app.locale === 'lt'
        ? 'nauju-stud-org-ikurimas'
        : 'procedure-for-the-establishmen'
    "
  >
    <NButton type="primary" size="large" class="max-w-fit">
      {{
        $page.props.app.locale === "lt"
          ? "Sužinok kaip tai padaryti!"
          : "Find out how to do that here!"
      }}</NButton
    >
  </a>
  <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
    <NCard
      v-for="(item, index) in institutions"
      :key="index"
      class="max-w-prose shadow-lg"
    >
      <template #header>
        <div class="flex flex-col break-all">
          <div
            class="w-48 h-48 flex justify-start items-center overflow-hidden mb-4"
          >
            <!--TODO: somehow ensure equal scaling of all photos?-->
            <img
              :src="item.image_url"
              :alt="item.name"
              class="object-contain ease-in transition-all duration-300 hover:scale-110"
            />
          </div>
          <h4 v-if="$page.props.app.locale === 'lt'">{{ item.name }}</h4>
          <h4 v-else>{{ item.extra_attributes.en.name }}</h4>
        </div>
      </template>
      <div v-if="$page.props.app.locale === 'lt'">
        <p v-html="item.description"></p>
      </div>
      <div v-else>
        <p v-html="item.extra_attributes.en.description"></p>
      </div>
    </NCard>
  </div>
  <!-- <template v-if="$page.props.app.locale === 'lt'"></template>
  <template v-if="$page.props.app.locale === 'en'"> </template> -->
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { NButton, NCard } from "naive-ui";

defineProps<{
  institutions: Array<App.Entities.Institution>;
}>();
</script>
