<template>
  <SheetForm
    v-model:open="open"
    :title="category ? $t('Redaguoti kategoriją') : $t('Nauja kategorija')"
    :description="$t('Kategorija sugrupuoja panašius išteklius, kad juos būtų lengviau rasti.')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="reset"
    @submit="submit"
  >
    <div class="space-y-2">
      <Label for="category-name">{{ $t('forms.fields.title') }}</Label>
      <MultiLocaleInput id="category-name" v-model:input="form.name" :placeholder="RESOURCE_CATEGORY_PLACEHOLDERS.name" />
      <p v-if="form.errors.name || form.errors['name.lt']" class="text-sm text-status-danger">
        {{ form.errors.name || form.errors['name.lt'] }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="category-description">
        {{ $t('forms.fields.description') }} <span class="text-muted-foreground">({{ $t('neprivaloma') }})</span>
      </Label>
      <MultiLocaleInput
        id="category-description"
        v-model:input="form.description"
        input-type="textarea"
        :placeholder="RESOURCE_CATEGORY_PLACEHOLDERS.description"
      />
      <p v-if="form.errors.description" class="text-sm text-status-danger">
        {{ form.errors.description }}
      </p>
    </div>

    <div class="space-y-2">
      <Label>{{ $t('Ikona') }} <span class="text-muted-foreground">({{ $t('neprivaloma') }})</span></Label>
      <Suspense>
        <FluentIconSelect :icon="form.icon" @update:icon="(value) => form.icon = value" />
      </Suspense>
    </div>
  </SheetForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, toRaw, watch } from 'vue';

import FluentIconSelect from '@/Components/FormItems/FluentIconSelect.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Label } from '@/Components/ui/label';
import { RESOURCE_CATEGORY_PLACEHOLDERS } from '@/Constants/I18n/Placeholders';

interface CategoryInput {
  id?: string | number;
  name: { lt: string; en: string };
  description: { lt: string; en: string } | null;
  icon: string | null;
}

const props = defineProps<{ open: boolean; category?: CategoryInput | null }>();
const emit = defineEmits<{ 'update:open': [value: boolean]; 'saved': [] }>();

const open = computed({ get: () => props.open, set: (value: boolean) => emit('update:open', value) });
const blank = (): CategoryInput => ({ name: { lt: '', en: '' }, description: { lt: '', en: '' }, icon: null });
const form = useForm<CategoryInput>(blank());

watch(() => props.category, (category) => {
  form.defaults(category ? structuredClone(toRaw(category)) : blank());
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
    onSuccess: () => {
      form.defaults();
      open.value = false;
      emit('saved');
    },
  };

  if (props.category?.id) {
    form.patch(route('resourceCategories.update', props.category.id), options);
  }
  else {
    form.post(route('resourceCategories.store'), options);
  }
}
</script>
