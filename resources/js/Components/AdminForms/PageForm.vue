<template>
  <NForm :model="form" label-placement="top">
    <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
      <NFormItemGi label="Pavadinimas" :span="2">
        <NInput
          v-model:value="form.title"
          type="text"
          placeholder="Įrašyti pavadinimą..."
        />
      </NFormItemGi>

      <NFormItemGi label="Nuoroda" :span="2">
        <NInput
          :value="form.permalink"
          disabled
          type="text"
          placeholder="Sugeneruojama nuoroda..."
        />
      </NFormItemGi>

      <NFormItemGi label="Kalba" :span="2">
        <NSelect
          v-model:value="form.lang"
          filterable
          :options="languageOptions"
          placeholder="Pasirinkti kalbą..."
        />
      </NFormItemGi>

      <NFormItemGi label="Kitos kalbos puslapis" :span="2">
        <NSelect
          v-model:value="form.other_lang_id"
          filterable
          :disabled="modelRoute === 'pages.store'"
          placeholder="Pasirinkti kitos kalbos puslapį... (tik tada, kai jau sukūrėte puslapį)"
          :options="otherPageOptions"
          clearable
        />
      </NFormItemGi>

      <NFormItemGi label="Turinys" :span="6">
        <TipTap v-model="form.text" :search-files="$page.props.search.other" />
        <template #label>
          <span class="text-lg font-bold">Pagrindinis tekstas</span>
        </template>
      </NFormItemGi>
    </NGrid>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      ></DeleteModelButton>
      <UpsertModelButton :form="form" :model-route="modelRoute"
        >Sukurti</UpsertModelButton
      >
    </div>
  </NForm>
</template>

<script setup lang="ts">
import { NForm, NFormItemGi, NGrid, NInput, NSelect } from "naive-ui";
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import latinize from "latinize";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  page: App.Entities.Page;
  otherLangPages?: App.Entities.Page[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("page", props.page);

const otherPageOptions = computed(() => {
  if (props.modelRoute === "pages.store") {
    return [];
  }

  if (props.otherLangPages === undefined) {
    return [];
  }

  return props.otherLangPages
    .map((page) => ({
      value: page.id,
      label: `${page.title} (${page.padalinys?.shortname})`,
    }))
    .reverse();
});

const languageOptions = [
  {
    value: "lt",
    label: "Lietuvių",
  },
  {
    value: "en",
    label: "English",
  },
];

// watch form.title and update form.permalink

if (props.modelRoute == "pages.store") {
  watch(
    () => form.title,
    (title) => {
      let latinizedTitle = latinize(title);
      form.permalink = latinizedTitle
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/-+/g, "-")
        .replace(/^-+/, "")
        .replace(/-+$/, "")
        .substring(0, 30);
    }
  );
}
</script>
