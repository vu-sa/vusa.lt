<template>
  <AdminLayout :title="exam.subject_name + ' - ' + exam.created_at">
    <div class="main-card">
      <h3 class="mb-4">Bendra informacija</h3>
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
          <n-input v-model:value="exam.phone" type="text" placeholder="Įrašyti telefoną..." />
        </div>

        <div>
          <label class="font-bold">El. paštas</label>
          <n-input v-model:value="exam.email" type="text" placeholder="Įrašyti el. paštą..." />
        </div>

        <div class="col-span-2">
          <label class="font-bold">Vieta</label>
          <n-input v-model:value="exam.place" type="text" placeholder="Įrašyti egzamino vietą..." />
        </div>

        <div class="col-span-4">
          <label class="font-bold">Trukmė</label>
          <n-input v-model:value="exam.duration" type="textarea" placeholder="Įrašyti trukmę..." />
        </div>

        <div>
          <label class="font-bold">Laikančiųjų skaičius</label>
          <n-input-number v-model:value="exam.exam_holders" />
        </div>

        <div>
          <label class="font-bold">Prašytas stebėtojų skaičius</label>
          <n-input-number v-model:value="exam.students_need" placeholder="Įrašyti skaičių..." />
        </div>

        <div class="col-start-3 col-span-2 flex justify-end items-center">
          <n-popconfirm
            positive-text="Ištrinti!"
            negative-text="Palikti"
            @positive-click="destroyModel()"
          >
            <template #trigger>
              <button type="button">
                <TrashIcon class="w-5 h-5 mr-2 stroke-red-800 hover:stroke-red-900 duration-200" />
              </button>
            </template>
            Ištrinto elemento nebus galima atkurti! Bus ištrinti ir srautai, ir
            stebėtojai.
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
      </form>
    </div>

    <div class="main-card">
      <h3 class="flex items-center">
        Srautai
        <NButton @click="manageFlowModal()" text style="margin-left: 0.5em">
          <NIcon>
            <AddCircle20Regular />
          </NIcon>
        </NButton>
      </h3>
      <ul>
        <li
          role="button"
          @click="manageFlowModal(flow.id, flow.start_time)"
          v-for="flow in flows"
          v-bind:key="flow.id"
        >{{ flow.start_time }}</li>
      </ul>
    </div>

    <div class="main-card" v-if="observers.length !== 0">
      <h3>Užsiregistravę stebėtojai</h3>
      <ol>
        <li v-for="observer in observers" v-bind:key="observer.id">{{ observer.name }}</li>
      </ol>
    </div>
    <NModal
      v-model:show="showFlowModal"
      preset="dialog"
      :title="flow_id !== null ? 'Atnaujinti srauto laiką' : 'Pridėti srautą'"
      :positive-text="flow_id !== null ? 'Atnaujinti' : 'Pridėti'"
      @positive-click="submitFlow(flow_id, timestamp)"
      negative-text="Atšaukti"
    >
      <NDatePicker type="datetime" v-model:value="timestamp" />
    </NModal>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from "vue";
import { NSpin, NInput, NSelect, NInputNumber, NPopconfirm, useMessage, NButton, NIcon, NModal, NDatePicker } from "naive-ui";
import { Inertia } from "@inertiajs/inertia";
import { TrashIcon } from "@heroicons/vue/outline";
import { AddCircle20Regular } from "@vicons/fluent"
import AdminLayout from "@/Layouts/AdminLayout.vue";

const message = useMessage();

const props = defineProps({
  exam: Object,
  padaliniai: Object,
  flows: Object,
  observers: Object,
});

const exam = reactive(props.exam);
const showSpin = ref(false);

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

////////////////////////////////////////////////////////////////////////////////
// Create examFlowsModal to create and update flows
const showFlowModal = ref(false);
const flow_id = ref(null);
const timestamp = ref(null);

const manageFlowModal = (id = null, datetime = null) => {
  // timestamp to unix time
  if (datetime !== null) {
    timestamp.value = Date.parse(_.replace(datetime, ' ', 'T'));
  }
  flow_id.value = id;
  showFlowModal.value = true;
}

const submitFlow = (flow_id, timestamp) => {
  if (flow_id) {
    console.log(flow_id, timestamp);
    Inertia.patch(route('saziningaiExamFlows.update', flow_id), {
      start_time: timestamp / 1000,
    }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        showFlowModal.value = false;
        message.success("Srautas atnaujintas!");
      },
    });
  } else {
    console.log(timestamp);
    Inertia.post(route('saziningaiExamFlows.store'), {
      exam_uuid: exam.uuid,
      start_time: timestamp / 1000,
    }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        showFlowModal.value = false;
        message.success("Srautas pridėtas!");
      },
    });
  }
}

////////////////////////////////////////////////////////////////////////////////

const updateModel = () => {
  showSpin.value = !showSpin.value;
  Inertia.patch(route("saziningaiExams.update", exam.id), exam, {
    onSuccess: () => {
      showSpin.value = !showSpin.value;
      message.success("Sėkmingai atnaujinta!");
    },
    preserveScroll: true,
  });
};

const destroyModel = () => {
  Inertia.delete(route("saziningaiExams.destroy", exam.id), {
    onSuccess: () => {
      message.success("Egzaminas ištrintas!");
    },
    preserveScroll: true,
  });
};
</script>