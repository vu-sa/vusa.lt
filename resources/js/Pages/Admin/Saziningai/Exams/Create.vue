<template>
  <AdminLayout :title="title">
    <div class="main-card">
      <h3 class="mb-4">Bendra informacija</h3>
      <ul v-if="errors" class="mb-4 text-red-700">
        <li v-for="error in errors">{{ error }}</li>
      </ul>
      <form class="grid grid-cols-4 gap-8 mb-4 grid-flow-row-dense">
        <div>
          <label class="font-bold">Pavadinimas</label>
          <n-input
            v-model:value="exam.subject_name"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div>
          <label class="font-bold">Egzamino tipas</label>
          <n-select v-model:value="exam.exam_type" :options="exam_types" />
        </div>

        <div>
          <label class="font-bold">Padalinys</label>
          <n-select v-model:value="exam.padalinys_id" :options="padaliniai_options" />
        </div>

        <div>
          <label class="font-bold">Užregistravusio vardas</label>
          <n-input
            v-model:value="exam.name"
            type="text"
            placeholder="Įrašyti užsiregistravusio vardą..."
          />
        </div>

        <div>
          <label class="font-bold">Telefonas</label>
          <n-input
            v-model:value="exam.phone"
            type="text"
            placeholder="Įrašyti telefoną..."
          />
        </div>

        <div>
          <label class="font-bold">El. paštas</label>
          <n-input
            v-model:value="exam.email"
            type="text"
            placeholder="Įrašyti el. paštą..."
          />
        </div>

        <div class="col-span-2">
          <label class="font-bold">Vieta</label>
          <n-input
            v-model:value="exam.place"
            type="text"
            placeholder="Įrašyti egzamino vietą..."
          />
        </div>

        <div class="col-span-4">
          <label class="font-bold">Trukmė</label>
          <n-input
            v-model:value="exam.duration"
            type="textarea"
            placeholder="Įrašyti trukmę..."
          />
        </div>

        <div>
          <label class="font-bold">Laikančiųjų skaičius</label>
          <n-input-number v-model:value="exam.exam_holders" />
        </div>

        <div>
          <label class="font-bold">Prašytas stebėtojų skaičius</label>
          <n-input-number
            v-model:value="exam.students_need"
            placeholder="Įrašyti skaičių..."
          />
        </div>

        <div class="col-start-3 col-span-2 flex justify-end items-center">
          <n-popconfirm @positive-click="updateModel()">
            <template #trigger>
              <NSpin :show="showSpin" size="small">
                <n-button>Išsaugoti</n-button>
              </NSpin>
            </template>
            Ar tikrai išsaugoti?
          </n-popconfirm>
        </div>
      </form>
    </div>

    <!-- <div class="main-card">
      <h3>Srautai</h3>
      <ol>
        <li v-for="flow in flows" v-bind:key="flow.id">
          {{ flow.start_time }}
        </li>
      </ol>
    </div>-->

    <!-- <div class="main-card" v-if="observers.length !== 0">
      <h3>Užsiregistravę stebėtojai</h3>
      <ol>
        <li v-for="observer in observers" v-bind:key="observer.id">
          {{ observer.name }}
        </li>
      </ol>
    </div>-->
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import { ref, reactive, computed } from "vue";
import {
  NCheckbox,
  NSpin,
  NInput,
  NSelect,
  NInputNumber,
  NButton,
  NPopconfirm,
  useMessage,
} from "naive-ui";

import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
  padaliniai: Object,
  flows: Object,
  observers: Object,
  errors: Object,
});

const message = useMessage();

const exam = reactive({});

const padaliniai_options = props.padaliniai.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname_vu,
}));

const exam_types = [
  {
    value: "koliokviumas",
    label: "Koliokviumas",
  },
  {
    value: "egzaminas",
    label: "Egzaminas",
  },
];

const showSpin = ref(false);

const title = computed(() => {
  return "Naujas egzaminas";
});

// Form sending

const updateModel = async () => {
  showSpin.value = !showSpin.value;
  Inertia.post(route("saziningaiExams.store", exam.id), exam, {
    onSuccess: () => {
      showSpin.value = !showSpin.value;
      message.success("Egzaminas sukurtas!");
    },
    onError: () => {
      showSpin.value = !showSpin.value;
    },
  });
};
</script>
