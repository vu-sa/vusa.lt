<template>
  <NForm :model="form" label-placement="top">
    <NGrid :span="24" :x-gap="24">
      <NFormItemGi label="Pavadinimas" :span="12">
        <NInput
          v-model:value="form.title"
          type="text"
          placeholder="Įrašyti pavadinimą..."
        />
      </NFormItemGi>

      <NFormItemGi label="Nuoroda" :span="12">
        <NInput
          v-model:value="form.permalink"
          type="text"
          placeholder="Įrašyti nuorodą..."
        />
      </NFormItemGi>

      <NFormItemGi label="Kalba" :span="12">
        <NSelect
          v-model:value="form.lang"
          :options="languageOptions"
          placeholder="Pasirinkti kalbą..."
        />
      </NFormItemGi>

      <NFormItemGi label="Kitos kalbos puslapis" :span="12">
        <NSelect
          v-model:value="form.other_lang_page"
          disabled
          filterable
          placeholder="Ieškoti puslapio..."
          :options="otherLangPageOptions"
          clearable
          remote
          @search="getOtherLangPages"
        />
      </NFormItemGi>

      <NFormItemGi label="Turinys" :span="24">
        <TipTap v-model="form.text" :search-files="$page.props.search.other" />
        <template #label>
          <span class="text-lg font-bold">Pagrindinis tekstas</span>
        </template>
      </NFormItemGi>
    </NGrid>
    <div class="flex justify-end">
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
import { Inertia } from "@inertiajs/inertia";
import { NForm, NFormItemGi, NGrid, NInput, NSelect } from "naive-ui";
import { debounce } from "lodash";
import { ref } from "vue";
import { useForm, usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import TipTap from "@/components/TipTap.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  page: App.Models.Page;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("page", props.page);

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

const otherLangPageOptions = ref([]);
// const { message } = createDiscreteApi(["message"]);

const getOtherLangPages = debounce((input) => {
  // get other lang
  if (input.length > 2) {
    // message.loading("Ieškoma...");
    const other_lang = page.lang === "lt" ? "en" : "lt";
    Inertia.post(
      route("pages.search"),
      {
        data: {
          title: input,
          lang: other_lang,
        },
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          otherLangPageOptions.value = usePage().props.value.search.pages.map(
            (page) => {
              return {
                value: page.id,
                label: `${page.title} (${page.padalinys.shortname})`,
              };
            }
          );
        },
      }
    );
  }
}, 500);
</script>
