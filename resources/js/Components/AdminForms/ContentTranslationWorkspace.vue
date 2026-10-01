<template>
  <Dialog v-model:open="open">
    <DialogContent class="fixed inset-0 left-0 top-0 flex h-[100dvh] w-screen max-w-none translate-x-0 translate-y-0 flex-col gap-0 rounded-none p-0 sm:max-w-none" :show-close-button="false">
      <div class="flex min-h-14 items-center justify-between gap-3 border-b border-border px-4">
        <DialogTitle>{{ $t('editor.translation_workspace') }}</DialogTitle>
        <div class="flex gap-2 lg:hidden"><Button v-for="locale in ['lt', 'en']" :key="locale" :data-testid="`translation-locale-${locale}`" type="button" variant="outline" class="min-h-11" :aria-pressed="activeLocale === locale" @click="activeLocale = locale">{{ locale.toUpperCase() }}</Button></div>
        <Button type="button" variant="ghost" class="min-h-11" @click="open = false">{{ $t('Uždaryti') }}</Button>
      </div>
      <p v-if="error" role="alert" class="p-4 text-destructive">{{ error }}</p>
      <div v-if="target && source" class="grid min-h-0 flex-1 grid-cols-1 lg:grid-cols-2">
        <ContentTranslationPane :kind="source.kind" :existing="sourcePane" :class="[activeLocale !== source.form.lang && 'hidden lg:flex', 'min-w-0 border-r border-border']" />
        <ContentTranslationPane :kind="source.kind" :initial="target" :source :draft-identity="target.id ? undefined : draftIdentity" :class="[activeLocale !== target.lang && 'hidden lg:flex', 'min-w-0']" :after-save="paired" />
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, inject, ref, watch } from 'vue';
import { contentEditorHttp as http } from '@/Composables/contentEditorHttp';
import { trans as $t } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { CONTENT_EDITOR_CONTEXT } from '@/Components/RichContent/contentEditorContext';
import { blankTranslation, type ContentEditorData } from '@/Composables/useContentEditor';
import ContentTranslationPane from './ContentTranslationPane.vue';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
const props = defineProps<{ counterpartId?: number | null }>();
const open = defineModel<boolean>('open', { default: false });
const source = inject(CONTENT_EDITOR_CONTEXT, null);
const pairingInProgress = ref(false);
const sourcePane = source ? { ...source, ready: computed(() => source.ready.value && !pairingInProgress.value) } : undefined;
const target = ref<ContentEditorData | null>(null);
const error = ref('');
const activeLocale = ref(source?.form.lang ?? 'lt');
const userId = (usePage().props.auth as { user?: { id: string } })?.user?.id;
const draftPointer = `content-translation:${userId}:${source?.kind}:${source?.form.id ?? 'new'}`;
let draftIdentity = source?.form.id
  ? `new-00000000-0000-4000-8000-${String(source.form.id).padStart(12, '0')}`
  : `new-${crypto.randomUUID()}`;
try {
  draftIdentity = localStorage.getItem(draftPointer) ?? draftIdentity;
  localStorage.setItem(draftPointer, draftIdentity);
}
catch { /* Server recovery remains available when browser storage is blocked. */ }
watch(() => source?.form.id, (id) => {
  if (!id) return;
  try { localStorage.setItem(`content-translation:${userId}:${source?.kind}:${id}`, draftIdentity); }
  catch { /* The existing recovery identity still belongs to this editor session. */ }
});
watch(open, async (value) => {
  if (!value || !source) return;
  error.value = '';
  try {
    target.value = props.counterpartId
      ? (await http.get(route('api.v1.admin.contentEditor.show', { kind: source.kind, record: props.counterpartId }))).data.data
      : blankTranslation(source.form);
    if (!props.counterpartId && source.kind === 'pages' && source.form.parent_id) {
      const { data } = await http.get(route('api.v1.admin.contentEditor.show', { kind: 'pages', record: source.form.parent_id }));
      target.value!.parent_id = data.data.other_lang_id ?? null;
    }
  }
  catch (failure) { error.value = http.isError(failure) ? failure.response?.data?.message ?? failure.message : String(failure); }
}, { immediate: true });
async function paired() {
  if (!source?.form.id) return;
  pairingInProgress.value = true;
  try {
    const { data: response } = await http.get(route('api.v1.admin.contentEditor.show', { kind: source.kind, record: source.form.id }));
    source.acknowledgePairing?.(response.data);
  }
  catch { error.value = $t('editor.counterpart_unavailable'); }
  finally { pairingInProgress.value = false; }
}
</script>
