<template>
  <PageContent title="Duplikatų suliejimas" :heading-icon="Icons.USER">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            Sulieti duplikatus
          </template>
          <template #description>
            <div class="typography">
              <p>
                Pasirinkus narius, <strong>tik</strong> egzistuojantys ryšiai bus perduoti vienam vartotojui, t.y.: 
              </p>
              <ul>
                <li> Pareigos </li>
                <li> Užduotys </li>
                <li> Veiklos </li>
                <li> Reservacijos </li>
                <li> Narystės </li>
              </ul>
              Šią funkciją naudoti <strong>tik tada, kai duombazėje atsirado to paties žmogaus duplikatai.</strong> Nenaudoti, kai norima perduoti pareigybes kitam žmogui.
              <p> Šis veiksmas yra <strong>neatstatomas</strong>! </p>
            </div>
          </template>
          <NFormItem required>
            <template #label>
              <span class="inline-flex items-center gap-1">
                <NIcon :component="Icons.USER" />
                Prijungiamas vartotojas
              </span>
            </template>
            <NSelect v-model:value="form.merged_user_id" filterable :options="users" label-field="name" value-field="id"
              :render-label="renderOptionLabel" placeholder="Pasirinkite vartotoją" />
          </NFormItem>
          <NFormItem required>
            <template #label>
              <span class="inline-flex items-center gap-1">
                <NIcon :component="Icons.USER" />
                Prijungti prie...
              </span>
            </template>
            <NSelect v-model:value="form.kept_user_id" filterable :options="users" value-field="id" label-field="name"
              :render-label="renderOptionLabel" placeholder="Pasirinkite vartotoją" />
          </NFormItem>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { type SelectRenderLabel } from "naive-ui";
import { useForm } from "@inertiajs/vue3";
import IconEye from "~icons/fluent/eye16-regular";

import { Button } from "@/Components/ui/button";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

const { users } = defineProps<{
  users: App.Entities.Role[];
}>();

const form = useForm({
  kept_user_id: null,
  merged_user_id: null,
});

const renderOptionLabel: SelectRenderLabel = (option) => {
  return (
    <div class="flex items-center gap-2">
      <UserAvatar size={24} user={{ name: option.name, profile_photo_path: option.profile_photo_path }} />
      <span class="inline-flex gap-2">
        {option.name}

        <a target="_blank" href={route("users.edit", option.id)}>
          <Button variant="ghost" size="icon-xs">
            <IconEye />
          </Button>
        </a>
      </span>
    </div>
  );
};

function handleFormSubmit() {
  form.post(route("users.mergeUsers"), {
    onSuccess: () => {
      form.reset();
    },
  });
}
</script>
