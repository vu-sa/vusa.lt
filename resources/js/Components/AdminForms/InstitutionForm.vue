<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title> Pagrindinė informacija </template>
        <template #description
          ><p class="mb-4">
            Institucija gali būti bet koks VU SA arba VU organas, pavyzdžiui, VU
            SA padalinys, darbo grupė, VU studijų programos komitetas ir pan.
          </p>
        </template>
        <NFormItem :label="$t('forms.fields.title')" :span="2">
          <NInput
            v-if="locale === 'lt'"
            v-model:value="form.name"
            type="text"
            placeholder="Vilniaus universiteto Studentų atstovybė"
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
          <NInput
            v-else
            v-model:value="form.extra_attributes.en.name"
            type="text"
            placeholder="Vilnius University Students' Representation"
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
        </NFormItem>

        <div class="grid gap-x-4 lg:grid-cols-2">
          <NFormItem label="Trumpas pavadinimas" :span="2">
            <NInput
              v-if="locale === 'lt'"
              v-model:value="form.short_name"
              placeholder="VU SA"
              ><template #suffix
                ><SimpleLocaleButton
                  v-model:locale="locale"
                ></SimpleLocaleButton></template
            ></NInput>
            <NInput
              v-else
              v-model:value="form.extra_attributes.en.short_name"
              placeholder="VU SR"
              ><template #suffix
                ><SimpleLocaleButton
                  v-model:locale="locale"
                ></SimpleLocaleButton></template
            ></NInput>
          </NFormItem>

          <NFormItem label="Padalinys, kuriam priklauso institucija" :span="2">
            <NSelect
              v-model:value="form.padalinys_id"
              :options="options"
              placeholder="VU SA X"
              clearable
            />
          </NFormItem>
        </div>
      </FormElement>
      <FormElement>
        <template #title>Institucijos pareigos</template>
        <template #description>
          <p class="mb-4">
            Kiekviena institucija gali turėti daug pareigų.
            <strong>Viena</strong> konkreti pareiga gali priklausyti tik
            <strong>vienai institucijai</strong>.
          </p>
          <p class="mb-4">
            Tai pavyzdžiui, „Studentų atstovas“ pareigybė gali būti kūriama daug
            kartų, kiekvienam studijų programos komitetui (taip išlaikoma
            konkrečios pareigybės istorija).
          </p>
          <p>Pareigas prie institucijos galima pridėti dviem būdais:</p>
          <div class="mt-2 flex flex-col gap-2">
            <a target="_blank" :href="route('duties.create')">
              <NButton size="tiny" tertiary>
                Sukuriant naują pareigą
                <template #icon
                  ><NIcon class="align-middle" :component="Add16Filled"></NIcon
                ></template>
              </NButton>
            </a>
            <a target="_blank" :href="route('duties.index')">
              <NButton size="tiny" tertiary>
                Surandant jau egzistuojančią pareigą, visų pareigų sąraše
                <template #icon
                  ><NIcon
                    class="align-middle"
                    :component="Search16Regular"
                  ></NIcon
                ></template>
              </NButton>
            </a>
          </div>
        </template>
        <NCard v-if="institution.duties" class="subtle-gray-gradient">
          <strong
            >Šiuo metu institucijai priklauso šios pareigos ir asmenys:</strong
          >
          <TransitionGroup name="list" tag="div">
            <div v-for="duty in form.duties" :key="duty.id">
              <NButtonGroup size="tiny" round class="my-1">
                <NButton @click="handleDutyClick(duty)">{{
                  duty?.name
                }}</NButton>
                <NButton secondary @click="reorderDuties('up', duty)"
                  ><NIcon :component="ArrowCircleUp24Regular"
                /></NButton>
                <NButton secondary @click="reorderDuties('down', duty)"
                  ><NIcon :component="ArrowCircleDown24Regular"
                /></NButton>
              </NButtonGroup>
              <div v-for="user in duty.users" :key="user.id" class="my-1">
                <UserPopover :user="user" show-name :size="24" />
              </div>
            </div>
          </TransitionGroup>
          <FadeTransition>
            <div v-if="dutiesWereReordered" class="mt-4">
              <NButton @click="saveReorderedDuties">Atnaujinti</NButton>
            </div>
          </FadeTransition>
        </NCard>
        <NCard v-else class="subtle-gray-gradient col-span-3 h-fit">
          Ši institucija <strong>neturi</strong> pareigų.
        </NCard>
      </FormElement>
      <FormElement>
        <template #title>Detalesnis aprašymas</template>
        <template #description
          >Ši informacija rodoma viešai, vusa.lt tinklapyje</template
        >
        <NFormItem label="Nuotrauka" :span="6">
          <NMessageProvider>
            <UploadImageWithCropper
              v-model:url="form.image_url"
              folder="institutions"
            />
          </NMessageProvider>
        </NFormItem>
        <NFormItem :span="6">
          <template #label
            ><div class="inline-flex items-center gap-2">
              Aprašymas
              <SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></div
          ></template>
          <TipTap
            v-if="locale === 'lt'"
            v-model="form.description"
            :search-files="$page.props.search.other"
          />
          <TipTap
            v-else
            v-model="form.extra_attributes.en.description"
            :search-files="$page.props.search.other"
          />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title> Papildoma informacija </template>
        <template #description
          >Dažniausiai šios informacijos nereikėtų keisti...</template
        >
        <NFormItem label="Institucijos tipas" :span="2">
          <NSelect
            v-model:value="form.types"
            :options="institutionTypes"
            label-field="title"
            value-field="id"
            placeholder="Studentų atstovų organas"
            clearable
            multiple
          />
        </NFormItem>
        <NFormItem label="Techninė žymė" :span="2">
          <NInput
            v-if="locale === 'lt'"
            v-model:value="form.alias"
            :disabled="modelRoute === 'institutions.update'"
            type="text"
            placeholder="vu-sa"
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
          <NInput
            v-else
            v-model:value="form.extra_attributes.en.alias"
            :disabled="modelRoute === 'institutions.update'"
            type="text"
            placeholder=""
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      ></DeleteModelButton>
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  Add16Filled,
  ArrowCircleDown24Regular,
  ArrowCircleUp24Regular,
  Search16Regular,
} from "@vicons/fluent";
import {
  NButton,
  NButtonGroup,
  NCard,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NMessageProvider,
  NSelect,
} from "naive-ui";
import { ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FadeTransition from "../Transitions/FadeTransition.vue";
import FormElement from "./FormElement.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UploadImageWithCropper from "../Buttons/UploadImageWithCropper.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";
import UserPopover from "../Avatars/UserPopover.vue";

const props = defineProps<{
  institution: App.Entities.Institution;
  institutionTypes: App.Entities.Type[];
  padaliniai: Array<App.Entities.Padalinys>;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const locale = ref("lt");

const form = useForm("institution", props.institution);

const options = props.padaliniai.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname,
}));

const dutiesWereReordered = ref(false);

// this function only reorders the array, but does not change the order value of the duties
// the order value is assigned in the backend, using the indexes of the array, which is the one manipulated here
const reorderDuties = (direction: "up" | "down", duty: App.Entities.Duty) => {
  const index = form.duties.indexOf(duty);
  if (index === -1) {
    return;
  }
  const newIndex = direction === "up" ? index - 1 : index + 1;
  if (newIndex < 0 || newIndex >= form.duties.length) {
    return;
  }

  const newDuties = [...form.duties];
  const temp = newDuties[index];
  newDuties[index] = newDuties[newIndex];
  newDuties[newIndex] = temp;
  form.duties = newDuties;

  dutiesWereReordered.value = true;
};

const handleDutyClick = (duty: App.Entities.Duty) => {
  window.open(route("duties.edit", duty.id), "_blank");
};

const saveReorderedDuties = () => {
  const newDuties = form.duties.map((duty, index) => {
    duty.order = index;
    return duty;
  });
  router.post(
    route("institutions.reorderDuties"),
    {
      duties: newDuties,
    },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        dutiesWereReordered.value = false;
      },
    }
  );
};
</script>
