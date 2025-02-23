<template>
  <PageContent :title="`${$page.props.auth?.user?.name}`">
    <NCard>
      <!-- <p>{{ salutation }}</p> -->
      <div class="mb-4">
        <NForm :model="form">
          <FormElement>
            <template #title>
              {{ $t("Nustatymai") }}
            </template>
            <template #description>
              Kitus nustatymus gali tvarkyti komunikacijos ir atstovų koordinatoriai.
            </template>
            <NFormItem :label="$t('forms.fields.name_and_surname')">
              <div class="flex grow flex-col gap-1">
                <NInput v-model:value="form.name" :disabled="user.name_was_changed" />
                <InfoText v-if="!user.name_was_changed">
                  Paskyros vardą galima pakeisti tik VIENĄ kartą!
                </InfoText>
              </div>
            </NFormItem>
            <div class="grid gap-4 lg:grid-cols-2">
              <NFormItem :label="$t('forms.fields.phone')">
                <NInput v-model:value="form.phone" placeholder="+370 612 34 567" />
              </NFormItem>
              <NFormItem :label="$t('forms.fields.facebook_url')">
                <NInput v-model:value="form.facebook_url" placeholder="https://www.facebook.com/..." />
              </NFormItem>
            </div>
            <NFormItem :label="$t('forms.fields.picture')">
              <UploadImageWithCropper v-model:url="form.profile_photo_path" folder="contacts" />
            </NFormItem>

            <div class="grid gap-4 lg:grid-cols-2">
              <NFormItem :label="$t('forms.fields.pronouns')">
                <MultiLocaleInput v-model:input="form.pronouns" :placeholder="{ lt: 'Jie/jų', en: 'They/them' }" />
              </NFormItem>
              <NFormItem :label="$t('forms.fields.show_pronouns')">
                <NSwitch v-model:value="form.show_pronouns" :disabled="form.pronouns === ''">
                  <template #checked>
                    <span>Įvardžiai rodomi viešai</span>
                  </template>
                  <template #unchecked>
                    <span>Įvardžiai nerodomi viešai</span>
                  </template>
                </NSwitch>
              </NFormItem>
            </div>
            <NButton :loading type="primary" @click="handleSubmit">
              {{ $t("Išsaugoti") }}
              <template #icon>
                <IMdiContentSave />
              </template>
            </NButton>
          </FormElement>

          <h2>{{ $t("Tavo rolės") }}</h2>
          <ul class="list-inside">
            <li v-for="(role, index) in user.roles" :key="role.id">
              <strong>{{ $t(role.name) }}</strong>
            </li>
            <template v-for="duty in user.current_duties">
              <li v-for="role in duty.roles" :key="role.id">
                <strong>{{ $t(role.name) }}</strong> ({{
                  `iš pareigybės „${duty.name}“, kuri yra iš ${duty.institution?.tenant?.shortname ?? "nežinomo\
                padalinio"}` }})
              </li>
            </template>
          </ul>
        </nform>
      </div>
    </NCard>
    <p class="mt-4 flex items-center justify-center gap-2">
      <a class="inline-flex items-center" target="_blank" href="https://github.com/vu-sa/vusa.lt/">
        <NButton text><template #icon>
            <IMdiGithub />
          </template>{{ $t("Projekto puslapis") }}</NButton>
      </a>
    </p>
  </PageContent>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import FormElement from "@/Components/AdminForms/FormElement.vue";
import MultiLocaleInput from "@/Components/FormItems/MultiLocaleInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UploadImageWithCropper from "@/Components/Buttons/UploadImageWithCropper.vue";
import InfoText from "@/Components/SmallElements/InfoText.vue";

const props = defineProps<{
  user: App.Entities.User;
}>();

const loading = ref(false);

const form = useForm({
  name: props.user.name,
  phone: props.user.phone,
  facebook_url: props.user.facebook_url,
  picture: props.user.profile_photo_path,
  profile_photo_path: props.user.profile_photo_path,
  pronouns: props.user.pronouns,
  show_pronouns: props.user.show_pronouns,
});

const handleSubmit = () => {
  loading.value = true;
  form.patch(route("profile.update", props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      loading.value = false;
    },
  });
};
</script>
