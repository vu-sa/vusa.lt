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
      <SpecialDoingForm
        :show-alert="showAlert"
        :form-template="doingTemplate"
        @alert-closed="showAlert = false"
        @submit:form="handleSubmitForm"
      >
        <template #suggestion-content>
          <p class="mt-0">
            <strong> Elektroninės apklausos </strong> yra puikus būdas įvertinti
            studentų nuomonę (kai tai daroma tinkamai).
          </p>
          <p>
            Pradėjus apklausos organizavimą, bus sukurtas naujas veiksmas,
            <strong
              >kuriame <u>susipažinsi</u> su apklausos organizavimo
              procesu</strong
            >.
          </p>
          <p class="mb-0">Pradėkime! ✊</p>
        </template>
      </SpecialDoingForm>
      <ModalHelperButton v-if="!showAlert" @click="showAlert = true" />
      <template #footer
        ><span class="flex items-center gap-2 text-xs text-zinc-400">
          <NIcon :component="Info24Regular"></NIcon>Sukurtą el. apklausos
          organizavimo šabloną galėsi bet kada ištrinti!
        </span></template
      >
    </CardModal>
  </div>
</template>

<script setup lang="tsx">
import { DocumentCheckmark24Regular, Info24Regular } from "@vicons/fluent";
import { ref } from "vue";

import { NIcon } from "naive-ui";
import CardModal from "../Modals/CardModal.vue";
import ModalHelperButton from "./ModalHelperButton.vue";
import QuickActionButton from "./QuickActionButton.vue";
import SpecialDoingForm from "../AdminForms/Special/SpecialDoingForm.vue";

const timeIn7Days = new Date(
  new Date().getTime() + 7 * 24 * 60 * 60 * 1000
).getTime();

const doingTemplate = {
  title: "Studentų el. apklausa",
  date: timeIn7Days,
};

const showModal = ref(false);
const showAlert = ref(true);

const handleSubmitForm = (model: Record<string, any>) => {
  model.post(route("doings.store"), {
    onSuccess: () => {
      showModal.value = false;
      showAlert.value = false;
    },
  });
};
</script>
