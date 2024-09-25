<template>
  <div>
    <QuickActionButton @click="showModal = true">{{ $t("Organizuoti el. apklausą") }}
      <template #icon>
        <IFluentDocumentCheckmark24Regular />
      </template>
    </QuickActionButton>
    <CardModal :title="$t('Organizuoti el. apklausą')" :show="showModal" @close="showModal = false">
      <SpecialDoingForm :show-alert="showAlert" :form-template="doingTemplate" @alert-closed="showAlert = false"
        @submit:form="handleSubmitForm">
        <template #suggestion-content>
          <p class="mb-4">
            <strong> Elektroninės apklausos </strong> yra puikus būdas įvertinti
            studentų nuomonę (kai tai daroma tinkamai).
          </p>
          <p>
            Pradėjus apklausos organizavimą, bus sukurtas naujas veiksmas,
            <strong>kuriame <u>susipažinsi</u> su apklausos organizavimo
              procesu</strong>.
          </p>
          <p class="mt-4">
            Pradėkime! ✊
          </p>
        </template>
      </SpecialDoingForm>
      <ModalHelperButton v-if="!showAlert" @click="showAlert = true" />
      <template #footer>
        <InfoText>
          Sukurtą el. apklausos organizavimo šabloną galėsi bet kada ištrinti!
        </InfoText>
      </template>
    </CardModal>
  </div>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import CardModal from "../Modals/CardModal.vue";
import InfoText from "../SmallElements/InfoText.vue";
import ModalHelperButton from "./ModalHelperButton.vue";
import QuickActionButton from "./QuickActionButton.vue";
import SpecialDoingForm from "../AdminForms/Special/SpecialDoingForm.vue";

const timeIn7Days = new Date(
  new Date().getTime() + 7 * 24 * 60 * 60 * 1000
).getTime();

const institutionNameForTemplate = () => {
  const { auth } = usePage().props;

  let user = auth?.user;

  if (!user?.current_duties) {
    return null;
  }

  // check user.duties[].institution, and return only one if its not null
  let institution = user.current_duties
    .map((duty) => duty.institution)
    .filter((institution) => institution !== null)[0];

  return institution?.name;
};

const doingTemplate = {
  title: `${$t(
    "Studentų el. apklausa"
  )} (${institutionNameForTemplate()}, ${formatStaticTime(timeIn7Days, {
    year: "numeric",
    month: "long",
  })})`,
  date: timeIn7Days,
  type: "el-apklausa",
};

const showModal = ref(false);
const showAlert = ref(true);

const handleSubmitForm = (model: Record<string, any>) => {
  router.post(route("doings.store"), model, {
    onSuccess: () => {
      showModal.value = false;
      showAlert.value = false;
    },
  });
};
</script>
