<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import TenantSelectField from '@/Components/AdminForms/TenantSelectField.vue';
import { ConfirmDialog, SheetForm } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import type { LearningSectionOption, LearningSectionScopes } from './types';

const props = defineProps<{
  sections: LearningSectionOption[];
  scopes: LearningSectionScopes;
  canCreate: boolean;
  locale: 'lt' | 'en';
}>();
const open = defineModel<boolean>('open', { required: true });
const editing = ref(false);
const editingId = ref<string | null>(null);
const scope = computed(() => props.scopes[editingId.value ? 'update' : 'create']);
const deleting = ref<LearningSectionOption | null>(null);
const confirmOpen = ref(false);
const form = useForm({ title: { lt: '', en: '' }, description: { lt: '', en: '' }, order: 0, tenant_id: null as number | null });
watch(open, value => { if (!value) editing.value = false; });
function edit(section: LearningSectionOption | null) {
  editingId.value = section?.id ?? null;
  form.title = { lt: section?.title.lt ?? '', en: section?.title.en ?? '' };
  form.description = { lt: section?.description.lt ?? '', en: section?.description.en ?? '' };
  form.order = section?.order ?? props.sections.length;
  form.tenant_id = section ? section.tenant_id : (!scope.value.canTargetEverywhere && scope.value.tenants.length === 1 ? scope.value.tenants[0]!.id : null);
  form.clearErrors();
  form.defaults();
  editing.value = true;
}
function submit() {
  if (!editing.value) return;
  const options = { preserveScroll: true, onSuccess: () => { form.defaults(); editing.value = false; } };
  if (editingId.value) form.put(route('learningSections.update', editingId.value), options);
  else form.post(route('learningSections.store'), options);
}
function destroy() {
  if (!deleting.value) return;
  router.delete(route('learningSections.destroy', deleting.value.id), { preserveScroll: true, onSuccess: () => { confirmOpen.value = false; deleting.value = null; } });
}
</script>

<template>
  <SheetForm v-model:open="open" :title="$t('learning_library.section_manager.title')" :dirty="editing && form.isDirty" :processing="form.processing" :disabled="!editing || (!scope.canTargetEverywhere && form.tenant_id === null)" @submit="submit">
    <template v-if="!editing" #footer>
      <Button type="button" variant="outline" voice="sentence" class="min-h-11" @click="open = false">{{ $t('Uždaryti') }}</Button>
    </template>
    <ul v-if="!editing" class="divide-y divide-border">
      <li v-for="section in sections" :key="section.id" class="flex flex-wrap items-start gap-2 py-3" data-slot="managed-section">
        <div class="min-w-0 flex-1 basis-44">
          <p class="break-words text-sm font-semibold">{{ section.title[locale] || section.title.lt || section.title.en }}</p>
          <p v-if="section.tenant" class="text-xs text-muted-foreground">{{ section.tenant.shortname }}</p>
        </div>
        <Button v-if="section.canUpdate" type="button" variant="ghost" voice="sentence" class="min-h-11" :aria-label="`${$t('Redaguoti')}: ${section.title[locale] || section.title.lt || section.title.en}`" @click="edit(section)">{{ $t('Redaguoti') }}</Button>
        <Button v-if="section.canDelete" type="button" variant="ghost" voice="sentence" class="min-h-11" :aria-label="`${$t('Ištrinti')}: ${section.title[locale] || section.title.lt || section.title.en}`" @click="deleting = section; confirmOpen = true">{{ $t('Ištrinti') }}</Button>
      </li>
    </ul>
    <Button v-if="canCreate && !editing" type="button" variant="outline" voice="sentence" class="min-h-11" data-testid="create-section" @click="edit(null)">{{ $t('learning.sections.create') }}</Button>
    <div v-if="editing" class="space-y-5 border-t border-border pt-5" data-slot="section-editor">
      <template v-for="language in (['lt', 'en'] as const)" :key="language">
        <FormFieldWrapper :id="`section-title-${language}`" :label="$t(`learning_library.section_manager.title_${language}`)" :required="language === 'lt'" :error="form.errors[`title.${language}`]">
          <Input :id="`section-title-${language}`" v-model="form.title[language]" />
        </FormFieldWrapper>
        <FormFieldWrapper :id="`section-description-${language}`" :label="$t(`learning_library.audiences.description_${language}`)" :error="form.errors[`description.${language}`]">
          <Textarea :id="`section-description-${language}`" v-model="form.description[language]" rows="3" />
        </FormFieldWrapper>
      </template>
      <FormFieldWrapper id="section-order" :label="$t('learning_library.fields.order')" :error="form.errors.order">
        <Input id="section-order" v-model.number="form.order" type="number" min="0" />
      </FormFieldWrapper>
      <TenantSelectField v-if="!scope.canTargetEverywhere" id="section-tenant" v-model="form.tenant_id" :tenants="scope.tenants" :error="form.errors.tenant_id" />
    </div>
  </SheetForm>
  <ConfirmDialog v-model:open="confirmOpen" :title="$t('learning_library.section_manager.delete_title')" :description="$t('learning_library.section_manager.delete_description')" :confirm-label="$t('Ištrinti')" destructive @confirm="destroy" />
</template>
