<template>
  <SheetForm
    required-note
    v-model:open="open"
    :created-at="tag?.created_at"
    :updated-at="tag?.updated_at"
    :title="tag ? $t('Redaguoti žymą') : $t('Nauja žyma')"
    :description="$t('Žyma padeda susieti panašų svetainės turinį.')"
    :processing="form.processing"
    @cancel="reset"
    @submit="submit"
  >
    <div class="space-y-2">
      <Label for="tag-name">{{ $t('Pavadinimas') }} <span class="text-destructive">*</span></Label>
      <MultiLocaleInput id="tag-name" v-model:input="form.name" />
      <p v-if="form.errors.name" class="text-sm text-status-danger">
        {{ form.errors.name }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="tag-alias">{{ $t('Alias') }}</Label>
      <Input id="tag-alias" v-model="form.alias" :placeholder="$t('Pvz: stipendijos')" />
      <p class="text-sm text-muted-foreground">
        {{ $t('Trumpa nuorodos dalis naudojama ten, kur reikia pastovaus adreso.') }}
      </p>
      <p v-if="form.errors.alias" class="text-sm text-status-danger">
        {{ form.errors.alias }}
      </p>
    </div>

    <div class="space-y-2">
      <Label>{{ $t('Aprašymas') }}</Label>
      <MultiLocaleTiptapFormItem v-model:input="form.description" />
    </div>

    <label for="tag-is-topic" class="flex min-h-11 items-center gap-3 border-y border-border py-3 text-sm">
      <Checkbox id="tag-is-topic" v-model="form.is_topic" />
      <span>
        <span class="block font-medium">{{ $t('Teminė žyma') }}</span>
        <span class="block text-muted-foreground">{{ $t('Rodoma kaip turinio tema svetainėje.') }}</span>
      </span>
    </label>
  </SheetForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, watch } from 'vue';

import SheetForm from '@/Components/Patterns/SheetForm.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import MultiLocaleTiptapFormItem from '@/Components/FormItems/MultiLocaleTiptapFormItem.vue';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';

interface TagInput {
  created_at?: string | null;
  updated_at?: string | null;
  id?: string | number;
  name: { lt: string; en: string };
  description: { lt: string; en: string } | null;
  alias: string | null;
  is_topic: boolean;
}

const props = defineProps<{ open: boolean; tag?: TagInput | null }>();
const emit = defineEmits<{ 'update:open': [value: boolean]; 'saved': [] }>();

const open = computed({ get: () => props.open, set: (value: boolean) => emit('update:open', value) });
const blank = (): TagInput => ({ name: { lt: '', en: '' }, description: { lt: '', en: '' }, alias: null, is_topic: false });
const form = useForm<TagInput>(blank());

watch(() => props.tag, (tag) => {
  form.defaults(tag
    ? {
        id: tag.id,
        name: { ...tag.name },
        description: { lt: tag.description?.lt ?? '', en: tag.description?.en ?? '' },
        alias: tag.alias,
        is_topic: tag.is_topic,
      }
    : blank());
  form.reset();
  form.clearErrors();
}, { immediate: true });

function reset(): void {
  form.reset();
  form.clearErrors();
}

function submit(): void {
  const options = {
    preserveScroll: true,
    headers: { 'X-Tag-Sheet': 'true' },
    onSuccess: () => {
      form.defaults();
      open.value = false;
      emit('saved');
    },
  };

  if (props.tag?.id) {
    form.patch(route('tags.update', props.tag.id), options);
  }
  else {
    form.post(route('tags.store'), options);
  }
}
</script>
