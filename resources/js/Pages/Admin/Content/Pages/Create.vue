<template>
  <AdminLayout :title="page.title ? page.title : 'Naujas puslapis'">
    <form>
      <div class="main-card">
        <h2 class="mb-4">Parinktys</h2>
        <ul v-if="errors" class="mb-4 text-red-700">
          <li v-for="error in errors">{{ error }}</li>
        </ul>
        <div class="mb-4">
          <label class="font-bold">Pavadinimas</label>
          <NInput v-model:value="page.title" placeholder="Įrašyti pavadinimą..." />
        </div>
        <div class="mb-4">
          <label class="font-bold">Nuoroda</label>
          <NInput
            disabled
            v-model:value="page.permalink"
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
            :disabled="!page.lang"
            v-model:value="page.other_lang_page"
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
          <TipTap v-model="page.text" :searchFiles="$page.props.search.other" />
        </div>
        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
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
import { ref, reactive, computed } from "vue";
import { NInput, NSelect, useMessage, NSpin, NPopconfirm, NButton } from "naive-ui";
import { TrashIcon } from "@heroicons/vue/outline";
import { Inertia } from "@inertiajs/inertia";
import { usePage } from "@inertiajs/inertia-vue3";
// import { map } from "lodash";

const props = defineProps({
  errors: Object,
});

const showSpin = ref(false);

const page = reactive({});
const otherLangPageOptions = ref([]);
const message = useMessage();

// compute news.permalink with snake case of title, also limit length to 25 and add random hexanumeric hash
page.permalink = computed(() => {
  if (page.title) {
    return page.title
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/-+/g, "-")
      .replace(/^-+/, "")
      .replace(/-+$/, "")
      .substring(0, 30);
    // .concat("-", Math.random().toString(36).substring(2, 5));
  }
});

const getOtherLangPages = _.debounce((input) => {
  // get other lang
  if (input.length > 2) {
    message.loading("Ieškoma...");
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
          otherLangPageOptions.value = usePage().props.value.search.pages.map((page) => {
            return {
              value: page.id,
              label: `${page.title} (${page.padalinys.shortname})`,
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
  Inertia.post(route("pages.store"), page, {
    onSuccess: () => {
      showSpin.value = !showSpin.value;
      message.success("Puslapis sėkmingai pridėtas!");
    },
    onError: () => {
      showSpin.value = !showSpin.value;
    },
    preserveScroll: true,
  });
};
</script>
