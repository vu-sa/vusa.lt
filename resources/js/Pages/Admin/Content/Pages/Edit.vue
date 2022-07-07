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
          <NMessageProvider
            ><TipTap
              v-model="page.text"
              :search-files="$page.props.search.other"
          /></NMessageProvider>
        </div>
        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
          <!-- <n-popconfirm
            positive-text="Ištrinti!"
            negative-text="Palikti"
            @positive-click="destroyModel()"
          >
            <template #trigger>
              <button type="button">
                <TrashIcon
                  class="w-5 h-5 mr-2 stroke-red-800 hover:stroke-red-900 duration-200"
                />
              </button>
            </template>
            Ištrinto elemento nebus galima atkurti!
          </n-popconfirm> -->
          <NMessageProvider
            ><UpdateModel :model="page" model-update-route="pages.update"
          /></NMessageProvider>
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
  NMessageProvider,
  // NPopconfirm,
  NSelect,
  // NSpin,
  // useMessage,
} from "naive-ui";
import { TrashIcon } from "@heroicons/vue/outline";
import { reactive, ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import TipTap from "@/Components/TipTap.vue";
import UpdateModel from "@/Components/Admin/UpdateModel.vue";

const props = defineProps({
  page: Object,
});

const page = reactive(props.page);
const otherLangPageOptions = ref([]);
// const message = useMessage();

const getOtherLangPages = _.debounce((input) => {
  // get other lang
  if (input.length > 2) {
    // message.loading("Ieškoma...");
    const other_lang = page.lang === "lt" ? "en" : "lt";
    Inertia.post(
      // eslint-disable-next-line no-undef
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
