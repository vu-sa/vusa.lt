<template>
  <PageContent :title="navigation.name">
    <NCard>
      <h3 class="mb-4">Bendra informacija</h3>

      <form
        class="mb-4 grid grid-flow-row-dense grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4"
      >
        <div class="lg:col-span-2">
          <label class="font-bold">Pavadinimas</label>
          <n-input
            v-model:value="navigation.name"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <!-- <div class="lg:col-span-2">
          <label class="font-bold">Trumpinys</label>
          <n-input
            disabled
            v-model:value="navigation.alias"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div> -->

        <div class="lg:col-span-4">
          <label class="font-bold">Nuoroda</label>
          <n-input
            v-model:value="navigation.url"
            type="text"
            placeholder="Įrašyti nuorodą..."
          />
        </div>

        <div
          class="flex items-center justify-end md:col-start-2 lg:col-span-2 lg:col-start-3"
        >
          <UpsertModelButton
            :model="navigation"
            model-route="navigation.update"
          />
        </div>
      </form>
    </NCard>
  </PageContent>
</template>

<script setup lang="ts">
import { NCard, NInput } from "naive-ui";
import { reactive } from "vue";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  navigation: App.Entities.Navigation;
}>();

const navigation = reactive(props.navigation);
</script>
