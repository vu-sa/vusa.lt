<template>
  <NForm
    ref="formRef"
    :label-width="80"
    :model="formValue"
    :rules="rules"
    size="medium"
  >
    <section class>
      <p class="font-bold">
        Registracija aktyvi iki rugpjūčio 10 d. (ketvirtadienis) 12 val. 00
        min., stovyklos kaina – 54 €.
      </p>
      <p class="my-4">
        Jei tavo finansinė padėtis sudėtinga, yra galimybė stovykloje dalyvauti
        su 100 proc. nuolaida - tokiu susisiek su
        <a href="mailto:pirmininkas@mif.vusa.lt">pirmininkas@mif.vusa.lt</a> ar
        tel. numeriu <a href="tel:+37062873060">+370 628 73 060</a>.
        Organizatoriai, atsižvelgdami į norinčių dalyvauti studentų kiekį ir
        ribotą vietų skaičių, pasilieka teisę vykdyti atranką ir susisiekti su į
        atranką patekusiais žmonėmis.
      </p>
    </section>
    <NFormItem class="w-1/2" label="Vardas ir pavardė" path="name">
      <NInput
        v-model:value="formValue.name"
        placeholder=""
        type="text"
        :input-props="{
          autocomplete: 'name',
        }"
      />
    </NFormItem>
    <NFormItem
      label="Naudojami įvardžiai (pronouns) ji/jos, jis/jo, jie/jų ar kiti (prašome tiksliai įvardyti)"
      path="pronouns"
    >
      <NInput v-model:value="formValue.pronouns" placeholder="" type="text" />
    </NFormItem>
    <NFormItem label="Telefono numeris" path="phone">
      <NInput
        v-model:value="formValue.phone"
        placeholder=""
        :input-props="{ type: 'tel' }"
      />
    </NFormItem>
    <NFormItem label="El. paštas" path="email">
      <NInput
        v-model:value="formValue.email"
        placeholder=""
        :input-props="{ type: 'email' }"
      />
    </NFormItem>
    <!-- Add date picker -->
    <NFormItem label="Gimimo data" path="birthDate">
      <NDatePicker
        v-model:value="formValue.birthDate"
        placeholder="Gimimo data"
        type="date"
        :input-props="{
          autocomplete: 'birthDate',
        }"
      />
    </NFormItem>
    <!-- Add select -->
    <NFormItem label="Studijų programa" path="studyProgram">
      <NSelect
        v-model:value="formValue.studyProgram"
        placeholder="Studijų programa"
        :options="studyProgramsOptions"
      />
    </NFormItem>
    <NFormItem
      class="my-8"
      label="Primename, kad 4 dienų stovyklos kaina – 54 eurai. Į šią kainą įskaičiuota nakvynė, maitinimas, kelionė autobusais."
      path="reminderForPrice"
    >
      <NCheckbox v-model:checked="formValue.reminderForPrice"
        >Patvirtinu, kad esu susipažinęs su šia informacija</NCheckbox
      >
      <template #label
        ><p class="mb-0">
          Primename, kad <strong>4 dienų stovyklos kaina</strong> – 54 eurai. Į
          šią kainą įskaičiuota nakvynė, maitinimas, kelionė autobusais.
        </p></template
      >
    </NFormItem>
    <NFormItem
      class="mt-4"
      label="Ar dalyvausi stovykloje visas keturias dienas? Jei ne, nurodyk kuriomis dienomis dalyvausi stovykloje. Primename, jog stovykla vyks rugpjūčio 18–21 dienomis (penktadienis–pirmadienis)"
      path="all4Days"
    >
      <NInput
        v-model:value="formValue.all4Days"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem label="Kaip planuoji vykti į stovyklą?" path="transport">
      <NRadioGroup v-model:value="formValue.transport" name="Transportas">
        <NSpace>
          <NRadio value="Autobusu nuo Vilniaus">Autobusu nuo Vilniaus</NRadio>
          <NRadio value="Savu transportu">Savu transportu</NRadio>
        </NSpace>
      </NRadioGroup>
    </NFormItem>
    <NFormItem label="Tu esi:" path="vegetarian">
      <NRadioGroup v-model:value="formValue.vegetarian" name="Valgymo ypatumai">
        <NSpace>
          <NRadio value="Visavalgis">Visavalgis</NRadio>
          <NRadio value="Vegetaras">Vegetaras</NRadio>
          <NRadio value="Veganas">Veganas</NRadio>
        </NSpace>
      </NRadioGroup>
    </NFormItem>
    <NFormItem label="Ar turi alergijų? Jei taip, kokių?" path="allergies">
      <NInput
        v-model:value="formValue.allergies"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem label="Ar turi specialiųjų poreikių?" path="specialNeeds">
      <NInput
        v-model:value="formValue.specialNeeds"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem
      label="Papildoma informacija apie Tave, kurią turėtų žinoti organizatoriai!"
      path="moreInfo"
    >
      <NInput
        v-model:value="formValue.moreInfo"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem label="Įdomiausias faktas apie Tave:" path="special1">
      <NInput
        v-model:value="formValue.special1"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem label="Mėgstamiausias Eurovizijos pasirodymas?" path="special2">
      <NInput
        v-model:value="formValue.special2"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem
      label="Kas bus kitų metų Eurovizijos laimėtojas ir kodėl?"
      path="special3"
    >
      <NInput
        v-model:value="formValue.special3"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem
      label="Jei būtum Eurovizijoje pasirodantis atlikėjas, koks būtų tavo sceninis vardas?"
      path="special4"
    >
      <NInput
        v-model:value="formValue.special4"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem
      class="mt-6"
      label="Jeigu bėgdamas lenktynėse aplenki paskutinį bėgantį, kelintas dabar bėgi?"
      path="special5"
    >
      <NInput
        v-model:value="formValue.special5"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem
      class="mt-6"
      label="Jeigu ant Saulės dabar lyja, o Marse yra -39 laipsniai šalčio ir tu nesi rasistas, kokia tikimybė, kad iš rozetės pradės groti Verkos Serdiučkos muzika?"
      path="special6"
    >
      <NInput
        v-model:value="formValue.special6"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem path="acceptGDPR" class="text-sm"
      ><NCheckbox v-model:checked="formValue.acceptGDPR">
        Susipažinau su
        <a
          target="_blank"
          href="https://vusa.lt/uploads/Dokumentų šablonai/Asmens_duomenu_tvarkymo_VUSA_tvarkos_aprasas.pdf"
          @click.stop
          >Asmens duomenų tvarkymo Vilniaus universiteto Studentų atstovybėje
          tvarkos aprašu</a
        >
        ir sutinku</NCheckbox
      ></NFormItem
    >
    <NFormItem path="acceptDataManagement">
      <NCheckbox v-model:checked="formValue.acceptDataManagement"
        >Sutinku, kad organizatoriai naudotų mano pateiktus duomenis renginio
        organizavimo tikslais, bet ne ilgiau nei mėnesį po jo.</NCheckbox
      >
    </NFormItem>
    <section class="text-sm">
      <p>Duomenų valdytojas yra Vilniaus universiteto Studentų atstovybė.</p>
      <ul>
        <li>Adresas: Universiteto g. 3, Observatorijos kiemelis, Vilnius,</li>
        <li>Telefono numeris: <a href="tel:852687144">+37052687144</a>,</li>
        <li>
          El. paštas:
          <a href="mailto:dag@vusa.lt">dag@vusa.lt</a>
        </li>
      </ul>
      <p>Jūsų pateikti duomenys bus naudojami renginio organizavimo tikslu.</p>

      <p>
        Duomenų subjektas turi teisę susipažinti su savo asmens duomenimis,
        teisę reikalauti ištaisyti neteisingus, neišsamius, netikslius savo
        asmens duomenis ir kitas teisės aktais numatytas teises. Kilus
        klausimams ir norint realizuoti savo, kaip duomenų subjekto, teises,
        galite kreiptis į
        <a href="mailto:dag@vusa.lt">dag@vusa.lt</a>.
      </p>
    </section>

    <NFormItem :show-feedback="false"
      ><NButton type="primary" @click="handleValidateClick">
        Pateikti
      </NButton></NFormItem
    >
  </NForm>
</template>

<script setup lang="tsx">
import {
  type FormInst,
  type FormItemRule,
  type FormRules,
  type FormValidationError,
  NButton,
  NCheckbox,
  NForm,
  NFormItem,
  NInput,
  NRadio,
  NRadioGroup,
  NSelect,
  NSpace,
  useMessage,
} from "naive-ui";
import { defineAsyncComponent, ref } from "vue";
import { useForm } from "@inertiajs/vue3";

const NDatePicker = defineAsyncComponent(() =>
  import("naive-ui/es/date-picker").then((module) => module.NDatePicker),
);

const formRef = ref<FormInst | null>(null);

const message = useMessage();

const formValue = useForm("RegistrationFormMif", {
  name: null,
  pronouns: null,
  phone: null,
  email: null,
  birthDate: null,
  studyProgram: null,
  reminderForPrice: false,
  all4Days: null,
  transport: null,
  vegetarian: null,
  allergies: null,
  specialNeeds: null,
  moreInfo: null,
  special1: null,
  special2: null,
  special3: null,
  special4: null,
  special5: null,
  special6: null,
  acceptGDPR: false,
  acceptDataManagement: false,
});

const rules: FormRules = {
  name: {
    required: true,
    message: "Įrašykite savo vardą ir pavardę",
    trigger: "blur",
  },
  pronouns: {
    required: true,
    message: "Įrašykite savo įvardžius",
    trigger: "blur",
  },
  phone: {
    required: true,
    // message: "Įrašykite savo telefono numerį",
    trigger: "blur",
    type: "number",
    // validate phone number with + sign and spaces
    validator(rule: FormItemRule, value: string) {
      if (!value) {
        return new Error("Įrašykite savo telefono numerį");
      }
      if (!/^\+?[0-9\s]*$/i.test(value)) {
        return new Error("Neteisingas telefono numerio formatas");
      }
      return true;
    },
  },
  email: {
    required: true,
    trigger: "blur",
    validator(rule: FormItemRule, value: string) {
      if (!value) {
        return new Error("Įrašykite savo el. paštą");
      }
      if (!/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i.test(value)) {
        return new Error("Neteisingas el. pašto formatas");
      }
      return true;
    },
  },
  birthDate: {
    type: "number",
    required: true,
    message: "Pasirinkite savo gimimo datą",
    trigger: "blur",
  },
  studyProgram: {
    required: true,
    message: "Pasirinkite savo studijų programą",
    trigger: "blur",
  },
  reminderForPrice: {
    required: true,
    message: "Patvirtinkite, kad esate susipažinę",
    trigger: "blur",
    validator(rule, value) {
      return value;
    },
  },
  all4Days: {
    required: true,
    message: "Įrašyk dienas, kuriomis dalyvausi",
    trigger: "blur",
  },
  transport: {
    required: true,
    message: "Pasirinkite transportą",
    trigger: "change",
  },
  vegetarian: {
    required: true,
    message: "Pasirinkite",
    trigger: "change",
  },
  allergies: {
    required: true,
    message: "Įrašykite",
    trigger: "blur",
  },
  specialNeeds: {
    required: true,
    message: "Įrašykite",
    trigger: "blur",
  },
  moreInfo: {
    required: false,
    message: "Įrašykite",
    trigger: "blur",
  },
  special1: {
    required: true,
    message: "Įrašykite",
    trigger: "blur",
  },
  special2: {
    required: true,
    message: "Įrašykite",
    trigger: "blur",
  },
  special3: {
    required: true,
    message: "Įrašykite",
    trigger: "blur",
  },
  special4: {
    required: true,
    message: "Įrašykite",
    trigger: "blur",
  },
  special5: {
    required: true,
    message: "Įrašykite",
    trigger: "blur",
  },
  special6: {
    required: true,
    message: "Įrašykite",
    trigger: "blur",
  },
  acceptGDPR: {
    required: true,
    message: "Turite sutikti su GDPR taisyklėmis",
    trigger: "blur",
    validator(rule, value) {
      return value;
    },
  },
  acceptDataManagement: {
    required: true,
    message: "Turite sutikti su duomenų tvarkymu",
    trigger: "blur",
    // * Error if not defined
    validator(rule, value) {
      return value;
    },
  },
};

const handleValidateClick = (e: MouseEvent) => {
  e.preventDefault();
  formRef.value?.validate((errors: Array<FormValidationError> | undefined) => {
    if (!errors) {
      formValue.submit(
        "post",
        route("memberRegistration.store", {
          registrationForm: 3,
          subdomain: "www",
          lang: "lt",
        }),
        {
          onSuccess: () => {
            formValue.reset();
            message.success(
              `Sėkmingai užsiregistravote į VU MIF pirmakursių stovyklą! Laukite laiško iš VU SA MIF komandos (pasistengsime greitu metu 😊)!`,
              { duration: 15000 },
            );
            // showModal.value = false;
            // formValue.reset();
          },
        },
      );
    } else {
      message.error("Užpildykite visus laukelius.");
    }
  });
};

const studyPrograms = [
  "Bioinformatika",
  "Duomenų mokslas",
  "Finansų ir draudimo matematika",
  "Informacinės technologijos",
  "Informacinių sistemų inžinerija",
  "Informatika",
  "Matematika ir matematikos taikymai",
  "Matematikos mokymas ir edukometrija",
  "Programų sistemos",
  "Verslo duomenų analitika",
];

const studyProgramsOptions = studyPrograms.map((program) => {
  return { value: program, label: program };
});
</script>

<style scoped>
a {
  @apply underline;
}
</style>
