<template>
  <AdminLayout :title="calendar.title">
    <div class="main-card">
      <h3 class="mb-4">Bendra informacija</h3>
      <ul v-if="errors" class="mb-4 text-red-700">
        <li v-for="error in errors">{{ error }}</li>
      </ul>
      <form
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-4 grid-flow-row-dense"
      >
        <div class="lg:col-span-2">
          <label class="font-bold">Pavadinimas</label>
          <n-input
            v-model:value="calendar.title"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Data ir laikas</label>
          <n-date-picker
            v-model:formatted-value="calendar.date"
            placeholder="Įrašyti laiką..."
            value-format="yyyy-MM-dd HH:mm:ss"
            type="datetime"
          />
        </div>

        <div class="lg:col-span-4">
          <label class="font-bold">Aprašymas (HTML)</label>
          <n-input
            v-model:value="calendar.description"
            type="textarea"
            placeholder="Įrašyti aprašymą..."/>
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Kategorija</label>
          <n-select 
            v-model:value="calendar.category" 
            :options="options"
            placeholder="Įrašyti kategoriją..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Nuoroda</label>
          <n-input
            v-model:value="calendar.url"
            type="text"
            placeholder="Įrašyti nuorodą..."
          />
        </div>

        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
          <n-popconfirm @positive-click="updateModel()">
            <template #trigger>
              <NSpin :show="showSpin" size="small">
                <n-button>Sukurti</n-button>
              </NSpin>
            </template>
            Ar tikrai sukurti?
          </n-popconfirm>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from "vue";
import {
  NSpin,
  NInput,
  NSelect,
  NInputNumber,
  NPopconfirm,
  useMessage,
  NButton,
  NDatePicker,
} from "naive-ui";
import { Inertia } from "@inertiajs/inertia";
import { TrashIcon } from "@heroicons/vue/outline";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const message = useMessage();

const props = defineProps({
  errors: Object
});

const calendar = reactive({});
const showSpin = ref(false);

const options = [
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

////////////////////////////////////////////////////////////////////////////////

const updateModel = async () => {
  showSpin.value = !showSpin.value;
  Inertia.post(route("calendar.store"), calendar, {
    onSuccess: () => {
      showSpin.value = !showSpin.value;
      message.success("Renginys sukurtas!");
    },
    onError: () => {
      showSpin.value = !showSpin.value;
    },
    preserveScroll: true,
  });
};

</script>
