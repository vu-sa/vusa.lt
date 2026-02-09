<template>
  <Button v-bind="$attrs" variant="outline" @click="showModal = true">
    <IFluentPersonFeedback24Filled />
    <slot />
  </Button>
  <CardModal :title="`${$t('Palik grįžtamąjį ryšį')}`" :show="showModal" @close="showModal = false">
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
    <div class="space-y-4">
      <Textarea v-model="form.feedback" :rows="3" :placeholder="`${$t('Parašyk pastebėjimų, pasiūlymų')}...`" class="mt-2" />
      <div class="flex items-center gap-2">
        <Checkbox :checked="form.anonymous" @update:checked="val => form.anonymous = val" />
        <Label>{{ $t("Siųsti anonimiškai") }}</Label>
      </div>
      <Button :disabled="!form.feedback || loading" @click="handleSend">
        <Spinner v-if="loading" />
        <IFluentSend24Filled v-else />
        {{ $t("forms.submit") }}
      </Button>
    </div>
  </CardModal>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

import CardModal from '../Modals/CardModal.vue';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';

const showModal = ref(false);
const loading = ref(false);

const form = useForm({
  feedback: null,
  anonymous: false,
  href: window.location.href,
  selectedText: null,
});

const handleSend = () => {
  loading.value = true;
  form.post(route('feedback.send'), {
    onSuccess: () => {
      showModal.value = false;
      loading.value = false;
      form.reset();
    },
  });
};
</script>
