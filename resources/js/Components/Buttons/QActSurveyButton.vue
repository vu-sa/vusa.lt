<template>
  <div>
    <QuickActionButton
      :icon="DocumentCheckmark24Regular"
      @click="showModal = true"
      >Organizuoti el. apklausą</QuickActionButton
    >
    <CardModal
      :title="`Organizuoti el. apklausą`"
      :show="showModal"
      @close="showModal = false"
    >
      <NForm ref="formRef" :model="model" :rules="rules">
        <FadeTransition>
          <SuggestionAlert
            :show-alert="showAlert"
            @alert-closed="showAlert = false"
          >
            <strong>
              Elektroninės apklausos yra puikus būdas įvertinti studentų nuomonę
              (kai tai daroma tinkamai).
            </strong>
            <p>
              Pradėjus apklausos organizavimą, bus sukurtas naujas veiksmas,
              kuriame susipažinsi su apklausos organizavimo procesu.
            </p>
            <p>Pradėkime!</p>
          </SuggestionAlert>
        </FadeTransition>
        <NGrid :cols="1">
          <NFormItemGi label="Pavadinimas" required path="name">
            <NInput v-model:value="model.title" />
          </NFormItemGi>
          <NFormItemGi label="Preliminari data" required path="date">
            <NDatePicker
              v-model:value="model.date"
              :first-day-of-week="0"
              placeholder="Datą bus galima pakeisti!"
              type="date"
              clearable
            />
          </NFormItemGi>
        </NGrid>
        <NButton type="primary" @click="handleValidateForm"> Pradėti! </NButton>
      </NForm>
      <ModalHelperButton v-if="!showAlert" @click="showAlert = true" />
    </CardModal>
  </div>
</template>

<script setup lang="tsx">
import { DocumentCheckmark24Regular } from "@vicons/fluent";
import {
  type FormInst,
  type FormRules,
  NButton,
  NDatePicker,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
} from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import CardModal from "../Modals/CardModal.vue";
import FadeTransition from "../Transitions/FadeTransition.vue";
import ModalHelperButton from "./ModalHelperButton.vue";
import QuickActionButton from "./QuickActionButton.vue";
import SuggestionAlert from "../Alerts/SuggestionAlert.vue";

const showModal = ref(false);
const showAlert = ref(true);

const formRef = ref<FormInst | null>(null);

const timeIn7Days = new Date(
  new Date().getTime() + 7 * 24 * 60 * 60 * 1000
).getTime();

const model = useForm<{
  title: string;
  date: number;
}>({
  title: "Studentų el. apklausa",
  date: timeIn7Days,
});

const rules: FormRules = {
  title: [
    {
      required: true,
      message: "Pavadinimas yra privalomas",
      trigger: "blur",
    },
  ],
  date: [
    {
      type: "number",
      required: true,
      message: "Data yra privaloma",
      trigger: "blur",
    },
  ],
};

const handleValidateForm = () => {
  console.log("handleValidateForm");
  formRef.value?.validate((errors) => {
    if (!errors) {
      model.post(route("doings.store"), {
        onSuccess: () => {
          showModal.value = false;
          showAlert.value = false;
        },
      });
    }
  });
};
</script>
