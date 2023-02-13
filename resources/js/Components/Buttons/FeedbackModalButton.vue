<template>
  <NBadge dot processing :offset="[-1, -1]">
    <NButton text @click="showModal = true"
      ><template #icon
        ><NIcon
          :component="PersonFeedback24Regular"
          size="24"
        ></NIcon></template
    ></NButton>
  </NBadge>
  <CardModal
    :title="`${$t('Palik grįžtamąjį ryšį')}`"
    :show="showModal"
    @close="showModal = false"
  >
    <template v-if="$page.props.app.locale === 'lt'">
      <p class="mb-4 text-xs">
        <strong>mano.vusa.lt</strong> yra nuolat tobulinamas studentų projektas,
        į kurio plėtimą norime įtraukti visus!
      </p>
      <p class="mb-4 text-xs text-zinc-600 dark:text-zinc-300">
        Jei turi bendrų pastebėjimų ar pasiūlymų šiai platformai, parašyk žemiau
        esančiame laukelyje. Tekstas bus nusiųstas puslapio administratoriui.
      </p>
    </template>
    <template v-else>
      <p class="mb-4 text-xs">
        <strong>mano.vusa.lt</strong> is a constantly improving student project
        that we want to involve everyone in!
      </p>
      <p class="mb-4 text-xs text-zinc-600 dark:text-zinc-300">
        If you have any general comments or suggestions for this platform, write
        them in the field below. The text will be sent to the site
        administrator.
      </p>
    </template>
    <NForm>
      <NFormItem :show-label="false">
        <NInput
          v-model:value="form.feedback"
          :autosize="{
            minRows: 3,
            maxRows: 5,
          }"
          :placeholder="`${$t('Parašyk pastebėjimų, pasiūlymų')}...`"
          show-count
          class="mt-2"
          type="textarea"
        />
      </NFormItem>
      <NFormItem :show-feedback="false" :show-label="false"
        ><NCheckbox v-model:checked="form.anonymous">{{
          $t("Siųsti anonimiškai")
        }}</NCheckbox></NFormItem
      >
    </NForm>
    <NButton
      :loading="loading"
      type="primary"
      class="mt-4"
      :disabled="!form.feedback"
      @click="handleSend"
      ><template #icon><NIcon :component="Send20Filled"></NIcon></template
      >{{ $t("forms.submit") }}
    </NButton>
  </CardModal>
</template>

<script setup lang="tsx">
import {
  NBadge,
  NButton,
  NCheckbox,
  NForm,
  NFormItem,
  NIcon,
  NInput,
} from "naive-ui";
import { PersonFeedback24Regular, Send20Filled } from "@vicons/fluent";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import CardModal from "../Modals/CardModal.vue";

const showModal = ref(false);
const loading = ref(false);
const form = useForm({
  feedback: null,
  anonymous: false,
});

const handleSend = () => {
  loading.value = true;
  form.post(route("sendFeedback"), {
    onSuccess: () => {
      showModal.value = false;
      loading.value = false;
      form.reset();
    },
  });
};
</script>
