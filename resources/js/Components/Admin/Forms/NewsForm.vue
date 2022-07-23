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
          :value="
            modelRoute == 'news.create' ? createdPermalink : form.permalink
          "
          disabled
          type="text"
          placeholder="Sugeneruojama nuoroda"
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
          v-model:value="form.other_lang_news"
          disabled
          filterable
          placeholder="Ieškoti puslapio..."
          :options="otherLangNewsOptions"
          clearable
          remote
          @search="getOtherLangNews"
        />
      </NFormItemGi>

      <NFormItemGi label="Naujienos paskelbimo laikas" :span="8">
        <NDatePicker
          v-model:formatted-value="form.publish_time"
          placeholder="Data..."
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
        />
      </NFormItemGi>

      <NFormItemGi label="Ar juodraštis?" :span="8">
        <NSwitch
          v-model:value="form.draft"
          :checked-value="1"
          :unchecked-value="0"
        >
        </NSwitch>
      </NFormItemGi>

      <NFormItemGi :span="24"
        ><NDivider> Naujienos nuotrauka</NDivider>
      </NFormItemGi>

      <NFormItemGi label="Nuotrauka" :span="24">
        <UploadImageButtons
          v-model="form.image"
          :path="'news'"
        ></UploadImageButtons>
      </NFormItemGi>

      <NFormItemGi label="Nuotraukos autorius" :span="12">
        <NInput
          v-model:value="form.image_author"
          type="text"
          placeholder="Žmogus arba organizacija.."
        />
      </NFormItemGi>
      <NFormItemGi :span="24"
        ><NDivider> Straipsnio turinys</NDivider>
      </NFormItemGi>
      <NFormItemGi :span="24">
        <TipTap v-model="form.short" :search-files="$page.props.search.other" />
        <template #label>
          <span class="text-lg font-bold">Įvadas</span>
        </template>
      </NFormItemGi>

      <NFormItemGi :span="24" class="mt-2">
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
import { Inertia } from "@inertiajs/inertia";
import {
  NDatePicker,
  NDivider,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NSelect,
  NSwitch,
} from "naive-ui";
import { computed, ref } from "vue";
import { debounce } from "lodash";
import { useForm, usePage } from "@inertiajs/inertia-vue3";
import latinize from "latinize";
import route from "ziggy-js";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import TipTap from "@/components/TipTap.vue";
import UploadImageButtons from "@/Components/Admin/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  news: App.Models.News;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("news", props.news);

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

const otherLangNewsOptions = ref([]);

const createdPermalink = computed(() => {
  let latinizedTitle = latinize(form.title);

  return latinizedTitle
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-+/, "")
    .replace(/-+$/, "")
    .substring(0, 30);
});

const getOtherLangNews = debounce((input) => {
  // get other lang
  if (input.length > 2) {
    // message.loading("Ieškoma...");
    const other_lang = news.lang === "lt" ? "en" : "lt";
    Inertia.post(
      route("news.search"),
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
          otherLangnewsOptions.value = usePage().props.value.search.news.map(
            (news) => {
              return {
                value: news.id,
                label: `${news.title} (${news.padalinys.shortname})`,
              };
            }
          );
        },
      }
    );
  }
}, 500);
</script>
