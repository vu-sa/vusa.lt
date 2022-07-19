<template>
  <AdminLayout :title="`${page.title} - ${page.padalinys.shortname}`">
    <form>
      <div class="main-card">
        <h2 class="mb-4">Parinktys</h2>
        <div class="mb-4">
          <label class="font-bold">Pavadinimas</label>
          <NInput
            v-model:value="page.title"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>
        <div class="mb-4">
          <label class="font-bold">Nuoroda</label>
          <NInput
            v-model:value="page.permalink"
            disabled
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="mb-4">
          <label class="font-bold">Kalba</label>
          <NSelect
            v-model:value="page.lang"
            :options="languages"
            placeholder="Pasirinkti kalbą..."
          />
        </div>
        <div class="mb-4">
          <label class="font-bold">Kitos kalbos puslapis</label>
          <NSelect
            v-model:value="page.other_lang_page"
            disabled
            filterable
            placeholder="Ieškoti puslapio..."
            :options="otherLangPageOptions"
            clearable
            remote
            @search="getOtherLangPages"
          />
        </div>
      </div>
      <div class="main-card">
        <h2 class="font-bold text-xl mb-2 inline-block">Turinys</h2>
        <div class="py-4">
          <TipTap
            v-model="page.text"
            :search-files="$page.props.search.other"
          />
        </div>
        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
          <UpsertModelButton :model="page" model-route="pages.update" />
          <DeleteModelButton :model="page" model-route="pages.destroy" />
        </div>
      </div>
    </form>
  </AdminLayout>
</template>

<script setup>
import { Inertia } from "@inertiajs/inertia";
import {
  // NButton,
  NInput,
  // NPopconfirm,
  NSelect,
  // NSpin,
  // createDiscreteApi,
} from "naive-ui";
import { reactive, ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import DeleteModelButton from "@/Components/Admin/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap.vue";
import UpsertModelButton from "@/Components/Admin/UpsertModelButton.vue";

const props = defineProps({
  page: Object,
});

const page = reactive(props.page);
const otherLangPageOptions = ref([]);
// const { message } = createDiscreteApi(["message"]);

const getOtherLangPages = _.debounce((input) => {
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

const languages = [
  {
    value: "lt",
    label: "Lietuvių",
  },
  {
    value: "en",
    label: "English",
  },
];

const categories = [
  {
    value: "red",
    label: "Akademinė informacija",
  },
  {
    value: "yellow",
    label: "Socialinė informacija",
  },
  {
    value: "grey",
    label: "Kita informacija",
  },
];
</script>
