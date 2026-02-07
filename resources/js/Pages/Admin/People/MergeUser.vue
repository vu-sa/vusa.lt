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
          <FormFieldWrapper id="merged_user_id" label="Prijungiamas vartotojas" required>
            <Select v-model="mergedUserIdString">
              <SelectTrigger>
                <SelectValue placeholder="Pasirinkite vartotoją" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="user in users" :key="user.id" :value="String(user.id)">
                  <span class="flex items-center gap-2">
                    <UserAvatar :user="user" :size="24" />
                    {{ user.name }}
                    <a :href="route('users.edit', user.id)" target="_blank" @click.stop>
                      <Button variant="ghost" size="icon-xs">
                        <IconEye />
                      </Button>
                    </a>
                  </span>
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>
          <FormFieldWrapper id="kept_user_id" label="Prijungti prie..." required>
            <Select v-model="keptUserIdString">
              <SelectTrigger>
                <SelectValue placeholder="Pasirinkite vartotoją" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="user in users" :key="user.id" :value="String(user.id)">
                  <span class="flex items-center gap-2">
                    <UserAvatar :user="user" :size="24" />
                    {{ user.name }}
                    <a :href="route('users.edit', user.id)" target="_blank" @click.stop>
                      <Button variant="ghost" size="icon-xs">
                        <IconEye />
                      </Button>
                    </a>
                  </span>
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import IconEye from "~icons/fluent/eye16-regular";

import { Button } from "@/Components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";
import FormFieldWrapper from "@/Components/AdminForms/FormFieldWrapper.vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

const { users } = defineProps<{
  users: App.Entities.Role[];
}>();

const form = useForm({
  kept_user_id: null as number | null,
  merged_user_id: null as number | null,
});

// Bridge string <-> number for Select
const mergedUserIdString = computed({
  get: () => form.merged_user_id != null ? String(form.merged_user_id) : '',
  set: (val: string) => { form.merged_user_id = val ? Number(val) : null; },
});

const keptUserIdString = computed({
  get: () => form.kept_user_id != null ? String(form.kept_user_id) : '',
  set: (val: string) => { form.kept_user_id = val ? Number(val) : null; },
});

function handleFormSubmit() {
  form.post(route("users.mergeUsers"), {
    onSuccess: () => {
      form.reset();
    },
  });
}
</script>
