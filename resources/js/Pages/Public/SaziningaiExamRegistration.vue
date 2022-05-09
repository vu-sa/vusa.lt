<template>
  <PublicLayout>
    <PageArticle>
      <template #title>Egzamino ar kolokviumo stebėjimo registracijos forma</template>
      <div class="prose">
        <strong class="text-red-600">
          Registracijos forma šiuo metu yra uždaryta, greitu metu ją vėl atidarysime.
          Prašome kreiptis į
          <a href="mailto:saziningai@vusa.lt">saziningai@vusa.lt</a> dėl stebėjimo
          registracijos.
        </strong>
        <p>
          Taip pat, prašome atsiskaitymą registruoti likus bent 3 d.d. iki jo pradžios,
          kad būtų laiku surasti stebėtojai. Kitu atveju, kreipkitės į
          <a href="mailto:saziningai@vusa.lt">saziningai@vusa.lt</a>
        </p>
        <NForm
          disabled
          ref="formRef"
          :label-width="80"
          :model="formValue"
          :rules="rules"
          size="medium"
        >
          <NFormItem label="Vardas ir pavardė" path="user.name">
            <NInput
              disabl
              v-model:value="formValue.user.name"
              placeholder=""
              type="text"
            />
          </NFormItem>
          <NFormItem label="El. paštas" path="user.email">
            <NInput v-model:value="formValue.user.email" placeholder="" type="email" />
          </NFormItem>
          <NFormItem label="Telefono numeris" path="user.phone">
            <NInput v-model:value="formValue.user.phone" placeholder="" type="tel" />
          </NFormItem>
          <NFormItem label="Atsiskaitymo pobūdis" path="exam.type">
            <NSelect
              v-model:value="formValue.exam.type"
              :options="examTypes"
              placeholder=""
            />
          </NFormItem>
          <NFormItem label="Atsiskaitymą laikančiųjų padalinys" path="exam.unit">
            <NSelect
              v-model:value="formValue.exam.unit"
              :options="padaliniaiOptions"
              placeholder=""
            />
          </NFormItem>
          <NFormItem label="Atsiskaitomo dalyko pavadinimas" path="exam.subject_name">
            <NInput v-model:value="formValue.exam.subject_name" placeholder="" />
          </NFormItem>
          <NFormItem label="Atsiskaitymą laikančių studentų skaičius" path="exam.holders">
            <NInput v-model:value="formValue.exam.holders" placeholder="" />
          </NFormItem>
          <NFormItem label="Reikalingas stebėtojų skaičius" path="exam.students_need">
            <NInput v-model:value="formValue.exam.students_need" placeholder="" />
          </NFormItem>
          <NFormItem label="Atsiskaitymo srautai" path="exam.flows">
            <NDynamicInput v-model:value="formValue.exam.flows" :min="1" :max="4">
              <template #create-button-default> Pridėti srautą</template>
              <template #default="{ value }">
                <NDatePicker
                  v-model:formatted-value="formattedTime"
                  value-format="yyyy-MM-dd HH:mm"
                  type="datetime"
                  placeholder="Pasirinkti srauto laiką..."
                  clearable
                >
                </NDatePicker>
              </template>
            </NDynamicInput>
          </NFormItem>
          <NFormItem
            label="Atsiskaitymo trukmė (jei laikoma srautais, parašyti srautų skaičių ir kiek laiko skiriama vienam srautui)"
            path="exam.duration"
          >
            <NInput v-model:value="formValue.exam.duration" placeholder="" />
          </NFormItem>
          <NFormItem>
            <NButton @click="handleValidateClick"> Pateikti </NButton>
          </NFormItem>
        </NForm>
      </div>
    </PageArticle>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";
import PageArticle from "../../Components/Public/PageArticle.vue";
import { ref } from "vue";
import {
  NForm,
  NFormItem,
  NButton,
  NInput,
  NSelect,
  NDynamicInput,
  NDatePicker,
  useMessage,
} from "naive-ui";

const props = defineProps({
  padaliniaiOptions: Array,
});
const message = useMessage();
const formRef = ref(null);

const formValue = ref({
  user: {
    name: "",
    email: "",
    phone: "",
  },
  exam: {
    type: "",
    unit: null,
    subject_name: "",
    duration: "",
    place: "",
    holders: "",
    students_need: "",
    flows: [""],
  },
});

const rules = {
  user: {
    name: {
      required: true,
      message: "Įrašykite savo vardą ir pavardę",
      trigger: "blur",
    },
    email: {
      required: true,
      message: "Įrašykite savo el. paštą",
      trigger: "blur",
    },
    phone: {
      required: true,
      message: "Įrašykite savo telefono numerį",
      trigger: "blur",
    },
  },
  exam: {
    type: {
      required: true,
      message: "Atsiskaitymo pobūdis",
      trigger: "blur",
    },
    unit: {
      required: true,
      message: "Atsiskaitymą laikančiųjų padalinys",
      trigger: "blur",
    },
    subject_name: {
      required: true,
      message: "Atsiskaitomo dalyko pavadinimas",
      trigger: "blur",
    },
    duration: {
      required: true,
      message: "Atsiskaitymo data ir laikas",
      trigger: "blur",
    },
    place: {
      required: true,
      message: "Atsiskaitymo vieta: padalinys ir auditorija",
      trigger: "blur",
    },
    holders: {
      required: true,
      message: "Atsiskaitymą laikančių studentų skaičius",
      trigger: "blur",
    },
    students_need: {
      required: true,
      message: "Reikalingas stebėtojų skaičius",
      trigger: "blur",
    },
    flows: {
      required: true,
      message: "Atsiskaitymą laikančių studentų skaičius",
      trigger: "blur",
    },
  },
};

const examTypes = [
  {
    value: "koliokviumas",
    label: "Koliokviumas",
  },
  {
    value: "egzaminas",
    label: "Egzaminas",
  },
];

// map padaliniaiOptions to options for NSelect
const padaliniaiOptions = props.padaliniaiOptions.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname_vu,
}));

const handleValidateClick = (e) => {
  e.preventDefault();
  formRef.value?.validate((errors) => {
    if (!errors) {
      message.success("Valid");
    } else {
      console.log(errors);
      message.error("Invalid");
    }
  });
};
</script>
