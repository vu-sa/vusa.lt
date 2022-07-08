<template>
  <AdminLayout :title="exam.subject_name + ' - ' + exam.created_at">
    <div class="main-card">
      <h3 class="mb-4">Bendra informacija</h3>
      <ul v-if="errors" class="mb-4 text-red-700">
        <li v-for="error in errors">{{ error }}</li>
      </ul>
      <form
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-4 grid-flow-row-dense"
      >
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
          <n-select
            v-model:value="exam.padalinys_id"
            :options="padaliniai_options"
          />
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

        <div class="lg:col-span-2">
          <label class="font-bold">Vieta</label>
          <n-input
            v-model:value="exam.place"
            type="text"
            placeholder="Įrašyti egzamino vietą..."
          />
        </div>

        <div class="lg:col-span-4">
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

        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
          <NMessageProvider
            ><DeleteModelButton
              :model="exam"
              model-route="saziningaiExams.destroy"
          /></NMessageProvider>
          <NMessageProvider
            ><UpsertModelButton
              :model="exam"
              model-route="saziningaiExams.update"
          /></NMessageProvider>
        </div>
      </form>
    </div>

    <div class="main-card">
      <h3 class="flex items-center">
        Srautai
        <NButton text style="margin-left: 0.5em" @click="manageFlowModal()">
          <NIcon>
            <AddCircle20Regular />
          </NIcon>
        </NButton>
      </h3>
      <ol>
        <template v-for="flow in flows" :key="flow.id">
          <n-popover>
            <template #trigger>
              <li
                class="inline-block list-disc"
                role="button"
                @click="manageFlowModal(flow.id, flow.start_time)"
              >
                {{ flow.start_time }}
              </li>
            </template>
            <span>Atnaujinti srauto laiką</span>
          </n-popover>
          <ul v-if="flow.observers" class="mb-2">
            <li v-for="observer in flow.observers" class="ml-4">
              {{ observer.name }}
            </li>
          </ul>
        </template>
      </ol>
    </div>
    <NModal
      v-model:show="showFlowModal"
      preset="dialog"
      closable="true"
      icon-placement="top"
      type="warning"
      :title="flow_id !== null ? 'Atnaujinti srauto laiką' : 'Pridėti srautą'"
      :positive-text="flow_id !== null ? 'Atnaujinti' : 'Pridėti'"
      negative-text="Atšaukti"
      @positive-click="submitFlow(flow_id, timestamp)"
    >
      <NDatePicker v-model:value="timestamp" type="datetime" />
    </NModal>
  </AdminLayout>
</template>

<script setup>
import { AddCircle20Regular } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NDatePicker,
  NIcon,
  NInput,
  NInputNumber,
  NMessageProvider,
  NModal,
  NPopover,
  NSelect,
} from "naive-ui";
import { reactive, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

import DeleteModelButton from "@/Components/Admin/DeleteModelButton.vue";
import UpsertModelButton from "@/Components/Admin/UpsertModelButton.vue";

const props = defineProps({
  exam: Object,
  padaliniai: Object,
  flows: Object,
  observers: Object,
  errors: Object,
});

const exam = reactive(props.exam);

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
    timestamp.value = Date.parse(_.replace(datetime, " ", "T"));
  }
  flow_id.value = id;
  showFlowModal.value = true;
};

const submitFlow = (flow_id, timestamp) => {
  if (flow_id) {
    console.log(flow_id, timestamp);
    Inertia.patch(
      route("saziningaiExamFlows.update", flow_id),
      {
        start_time: timestamp / 1000,
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          showFlowModal.value = false;
          // message.success("Srautas atnaujintas!");
        },
      }
    );
  } else {
    console.log(timestamp);
    Inertia.post(
      route("saziningaiExamFlows.store"),
      {
        exam_uuid: exam.uuid,
        start_time: timestamp / 1000,
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          showFlowModal.value = false;
          // message.success("Srautas pridėtas!");
        },
      }
    );
  }
};
</script>
