<template>
  <AdminLayout :title="`${news.title} - ${news.padalinys.shortname}`">
    <form>
      <div class="main-card">
        <h2 class="mb-4">Parinktys</h2>
        <div class="mb-4">
          <label class="font-bold">Pavadinimas</label>
          <NInput v-model:value="news.title" placeholder="Įrašyti pavadinimą..." />
        </div>
        <div class="mb-4">
          <label class="font-bold">Nuoroda</label>
          <NInput
            disabled
            v-model:value="news.permalink"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="mb-4">
          <label class="font-bold">Kalba</label>
          <NSelect
            v-model:value="news.lang"
            :options="languages"
            placeholder="Pasirinkti kalbą..."
          />
        </div>
        <div class="mb-4">
          <label class="font-bold">Kitos kalbos puslapis</label>
          <NSelect
            disabled
            v-model:value="news.other_lang_news"
            filterable
            placeholder="Ieškoti puslapio..."
            :options="otherLangnewsOptions"
            clearable
            remote
            @search="getOtherLangNews"
          />
        </div>
        <div class="mb-4">
          <label class="font-bold">Naujienos paskelbimo laikas</label>
          <NDatePicker
            type="datetime"
            v-model:formatted-value="news.publish_time"
            value-format="yyyy-MM-dd HH:mm:ss"
          />
        </div>
        <NCheckbox
          class="mb-4"
          v-model:checked="news.draft"
          :checked-value="1"
          :unchecked-value="0"
        >
          Ar juodraštis?
        </NCheckbox>
      </div>
      <div class="main-card">
        <h2 class="font-bold text-xl mb-2 inline-block">Nuotrauka</h2>
        <UploadImage v-model="news.image" :path="'news'"></UploadImage>
        <div class="mb-4">
          <label class="font-bold">Nuotraukos autorius</label>
          <NInput v-model:value="news.image_author" />
        </div>
      </div>
      <div class="main-card">
        <h2 class="font-bold text-xl mb-2 inline-block">Įvadas</h2>
        <div class="py-4">
          <TipTap v-model="news.short" :searchFiles="$page.props.search.other" />
        </div>
      </div>
      <div class="main-card">
        <h2 class="font-bold text-xl mb-2 inline-block">Turinys</h2>
        <div class="py-4">
          <TipTap v-model="news.text" :searchFiles="$page.props.search.other" />
        </div>
        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
          <n-popconfirm
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
          </n-popconfirm>
          <n-popconfirm @positive-click="updateModel()">
            <template #trigger>
              <NSpin :show="showSpin" size="small">
                <n-button>Atnaujinti</n-button>
              </NSpin>
            </template>
            Ar tikrai atnaujinti?
          </n-popconfirm>
        </div>
      </div>
    </form>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import TipTap from "@/Components/TipTap.vue";
import { ref, reactive } from "vue";
import {
  NInput,
  NSelect,
  useMessage,
  NSpin,
  NPopconfirm,
  NButton,
  NCheckbox,
  NDatePicker,
  NUpload,
} from "naive-ui";
import { TrashIcon } from "@heroicons/vue/outline";
import { Inertia } from "@inertiajs/inertia";
import { usePage } from "@inertiajs/inertia-vue3";
// import { map } from "lodash";
import UploadImage from "@/Components/Admin/UploadImage.vue";

const props = defineProps({
  news: Object,
  // uploaded_image_path: String,
});

const message = useMessage();

const showSpin = ref(false);

const news = reactive(props.news);
const otherLangnewsOptions = ref([]);

const getOtherLangNews = _.debounce((input) => {
  // get other lang
  if (input.length > 2) {
    message.loading("Ieškoma...");
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
          otherLangnewsOptions.value = usePage().props.value.search.news.map((news) => {
            return {
              value: news.id,
              label: `${news.title} (${news.padalinys.shortname})`,
            };
          });
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

const updateModel = () => {
  showSpin.value = !showSpin.value;
  Inertia.patch(route("news.update", news.id), news, {
    onSuccess: () => {
      showSpin.value = !showSpin.value;
      message.success("Sėkmingai atnaujinta!");
    },
    onError: () => {
      showSpin.value = !showSpin.value;
    },
    preserveScroll: true,
  });
};
</script>
