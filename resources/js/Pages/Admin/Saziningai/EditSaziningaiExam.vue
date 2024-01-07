<template>
  <PageContent :title="exam.subject_name + ' - ' + exam.created_at">
    <UpsertModelLayout :errors="$page.props.errors" :model="exam">
      <SaziningaiExamForm
        :exam="exam"
        :padaliniai="padaliniai"
        model-route="saziningaiExams.update"
        delete-model-route="saziningaiExams.destroy"
      ></SaziningaiExamForm>

      <NCard class="mt-4">
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
              <li
                v-for="observer in flow.observers"
                :key="observer.id"
                class="ml-4"
              >
                {{ observer.name }}
              </li>
            </ul>
          </template>
        </ol>
      </NCard>
      <NModal
        v-model:show="showFlowModal"
        preset="dialog"
        :closable="true"
        icon-placement="top"
        type="warning"
        :title="flow_id !== null ? 'Atnaujinti srauto laiką' : 'Pridėti srautą'"
        :positive-text="flow_id !== null ? 'Atnaujinti' : 'Pridėti'"
        negative-text="Atšaukti"
        @positive-click="submitFlow(flow_id, timestamp)"
      >
        <NDatePicker v-model:value="timestamp" type="datetime" />
      </NModal>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { AddCircle20Regular } from "@vicons/fluent";
import { router } from "@inertiajs/vue3";
import { NButton, NCard, NDatePicker, NIcon, NModal, NPopover } from "naive-ui";
import { reactive, ref } from "vue";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import SaziningaiExamForm from "@/Components/AdminForms/SaziningaiExamForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  exam: App.Entities.SaziningaiExam;
  padaliniai: Array<App.Entities.Padalinys>;
  flows: Array<App.Entities.SaziningaiExamFlow>;
}>();

const exam = reactive(props.exam);

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
    router.patch(
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
    router.post(
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
