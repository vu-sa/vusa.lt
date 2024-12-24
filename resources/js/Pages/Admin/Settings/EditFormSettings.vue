<template>
  <PageContent title="Formų nustatymai" :back-url="'/'">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            Narių registracijos forma
          </template>
          <template #description>
            Pasirinkti, kuri registracijos forma iš duombazės bus naudojama narių registracijai. Jeigu registracijos
            forma turi padalinio laukelį, automatiškai bus siunčiami laiškai užsiregistravusiems ir taip pat padalinių
            pirmininkams.
          </template>
          <NFormItem>
            <template #label>
              <span class="inline-flex items-center gap-1">
                <NIcon :component="Icons.TITLE" />
                Forma
              </span>
            </template>
            <NSelect v-model:value="form.member_registration_form_id" :options="forms" label-field="name"
              value-field="id" placeholder="Pasirinkti formą" />
          </NFormItem>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";
import Icons from "@/Types/Icons/regular";

const { member_registration_form_id } = defineProps<{
  forms: App.Entities.Form[];
  member_registration_form_id: string | null;
}>();

const form = useForm({ member_registration_form_id: member_registration_form_id });

const handleFormSubmit = () => {
  form.post(route("forms.settings.update"));
};
</script>
