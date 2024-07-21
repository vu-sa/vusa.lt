<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>
          {{ $t("forms.context.main_info") }}
        </template>
        <template #description>
          <p class="mb-4">
            Institucija gali būti bet koks VU SA arba VU organas, pavyzdžiui, VU
            SA padalinys, darbo grupė, VU studijų programos komitetas ir pan.
          </p>
        </template>
        <NFormItem :label="$t('forms.fields.title')">
          <MultiLocaleInput v-model:input="form.name" />
        </NFormItem>

        <div class="grid gap-x-4 lg:grid-cols-2">
          <NFormItem :label="$t('forms.fields.short_name')">
            <MultiLocaleInput v-model:input="form.short_name" />
          </NFormItem>

          <NFormItem label="tenant, kuriam priklauso institucija">
            <NSelect v-model:value="form.tenant_id" :options="options" placeholder="VU SA X" />
          </NFormItem>
        </div>
        <NFormItem label="Ar aktyvi institucija?">
          <NSwitch v-model:value="form.is_active" :checked-value="1" :unchecked-value="0" />
        </NFormItem>
      </FormElement>
      <FormElement is-closed>
        <template #title>
          Institucijos pareigos ir jų eiliškumas
        </template>
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
                <template #icon>
                  <IFluentAdd24Filled />
                </template>
              </NButton>
            </a>
            <a target="_blank" :href="route('duties.index')">
              <NButton size="tiny" tertiary>
                Surandant jau egzistuojančią pareigą, visų pareigų sąraše
                <template #icon>
                  <IFluentSearch20Filled />
                </template>
              </NButton>
            </a>
          </div>
        </template>
        <div v-if="institution.duties">
          <InfoText>
            Tempk
            <IFluentReOrderDotsVertical24Regular class="inline" /> ikoną, kad pakeistum pareigų eiliškumą.
          </InfoText>
          <SortableDutiesTable v-model="form.duties" class="mt-2" @update:model-value="dutiesWereReordered = true">
            <template #default="{ model }">
              <div class="grid grid-cols-2 items-center justify-center">
                <SmartLink :href="route('duties.edit', model.id)" class="my-2 border-r px-4">
                  <NButton style="white-space: normal; text-align: left" text icon-placement="right">
                    {{ model.name }}
                    <template #icon>
                      <IFluentEdit24Regular width="14" />
                    </template>
                  </NButton>
                </SmartLink>
                <div class="flex flex-col gap-1 p-2">
                  <template v-for="user in model.current_users" :key="user.id">
                    <UserPopover :user show-name :size="24" />
                  </template>
                </div>
              </div>
            </template>
          </SortableDutiesTable>
          <div class="mt-4">
            <NButton type="primary" :disabled="!dutiesWereReordered" @click="saveReorderedDuties">
              Atnaujinti eiliškumą
            </NButton>
          </div>
        </div>
        <div v-else class="col-span-3 h-fit">
          Ši institucija <strong>neturi</strong> pareigų.
        </div>
      </FormElement>
      <FormElement>
        <template #title>
          Institucijos tipas ir papildoma informacija
        </template>
        <template #description>
          Priklausomai nuo institucijos tipo, gali būti įmanoma užpildyti papildomą informaciją.
        </template>
        <NFormItem label="Institucijos tipas">
          <NSelect v-model:value="form.types" :options="institutionTypes" label-field="title" value-field="id"
            placeholder="Studentų atstovų organas" clearable multiple />
        </NFormItem>
        <template v-if="showMoreOptions">
          <div class="flex flex-row gap-4">
            <NFormItem label="Nuotrauka">
              <UploadImageWithCropper v-model:url="form.image_url" folder="institutions" />
            </NFormItem>
            <NFormItem label="Logotipas">
              <UploadImageWithCropper v-model:url="form.logo_url" folder="institutions" />
            </NFormItem>
          </div>
          <div class="grid grid-cols-2 gap-x-4">
            <NFormItem label="El. paštas">
              <NInput v-model:value="form.email" type="email" placeholder="" />
            </NFormItem>
            <NFormItem label="Telefonas">
              <NInput v-model:value="form.phone" type="text" placeholder="" />
            </NFormItem>
            <NFormItem label="Adresas">
              <MultiLocaleInput v-model:input="form.address" />
            </NFormItem>
            <NFormItem label="Svetainė">
              <NInput v-model:value="form.website" type="text" placeholder="" />
            </NFormItem>
            <NFormItem label="Facebook">
              <NInput v-model:value="form.facebook_url" type="text" placeholder="" />
            </NFormItem>
            <NFormItem label="Instagram">
              <NInput v-model:value="form.instagram_url" type="text" placeholder="" />
            </NFormItem>
          </div>
        </template>
        <NFormItem>
          <template #label>
            <div class="inline-flex items-center gap-2">
              Aprašymas
              <SimpleLocaleButton v-model:locale="locale" />
            </div>
          </template>
          <TipTap v-if="locale === 'lt'" v-model="form.description.lt" html />
          <TipTap v-else v-model="form.description.en" html />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>
          Papildoma informacija
        </template>
        <NFormItem label="Techninė žymė">
          <MultiLocaleInput v-model:input="form.alias" />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <DeleteModelButton v-if="deleteModelRoute" :form="form" :model-route="deleteModelRoute" />
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import InfoText from "../SmallElements/InfoText.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import SmartLink from "../Public/SmartLink.vue";
import SortableDutiesTable from "../Tables/SortableDutiesTable.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UploadImageWithCropper from "../Buttons/UploadImageWithCropper.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";
import UserPopover from "../Avatars/UserPopover.vue";

const props = defineProps<{
  institution: App.Entities.Institution;
  institutionTypes: App.Entities.Type[];
  assignableTenants: Array<App.Entities.Tenant>;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const locale = ref("lt");
const dutiesWereReordered = ref(false);

const form = useForm("institution", props.institution);

if (Array.isArray(form.address)) {
  form.address = { lt: "", en: "" };
}

const options = props.assignableTenants.map((tenant) => ({
  value: tenant.id,
  label: tenant.shortname,
}));

const showMoreOptions = computed(() => {
  // HACK: manually added types to check
  const typesToCheck = ["pkp", "padaliniai"];
  const typeIds = props.institutionTypes
    ?.filter((type) => typesToCheck.includes(type.slug))
    .map((type) => type.id);

  return form.types?.some((type) => typeIds.includes(type));
});

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
