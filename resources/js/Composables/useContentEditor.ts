import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router, usePage, type InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { toast } from 'vue-sonner';

import { apiRequest, type ApiResult } from '@/Composables/apiRequest';
import type { PageProps } from '@/Types/index';
import type { ImageData } from '@/Types/media';

import { recoveryStorage, type RecoveryCopy } from './contentEditorStorage';

export type ContentKind = 'pages' | 'news';
export interface ContentEditorData extends Record<string, unknown> {
  id?: number;
  title?: string;
  lang?: string;
  permalink?: string;
  tenant_id?: number | null;
  parent_id?: number | null;
  translated_parent_id?: number | null;
  is_active?: boolean;
  draft?: boolean;
  publish_time?: string | null;
  short?: string | null;
  image_media?: ImageData | null;
  highlights?: string[] | null;
  featured_image_media?: ImageData | null;
  meta_description?: string | null;
  tags?: number[];
  pairing_confirmation?: string;
  updated_at?: string;
  content_version?: string;
  other_lang_id?: number | null;
  content?: { parts: Array<{ id?: number; key?: string; [key: string]: unknown }> };
}

export function contentEditorRequestError(result: Pick<ApiResult<unknown>, 'status' | 'message' | 'body'>): string {
  if (result.status === 0) return `TypeError: ${result.message ?? 'Network error'}`;
  if (result.body === null) return 'SyntaxError: Unexpected end of JSON input';
  return result.message ?? `Request failed (${result.status})`;
}

export function editorSnapshot(data: Record<string, unknown>): ContentEditorData {
  const fields = ['id', 'title', 'permalink', 'lang', 'tenant_id', 'parent_id', 'is_active', 'layout', 'show_table_of_contents', 'show_title', 'show_breadcrumbs', 'highlights', 'featured_image_media', 'meta_description', 'draft', 'publish_time', 'short', 'image_media', 'tags', 'other_lang_id', 'pairing_confirmation', 'content_version', 'content'];
  return JSON.parse(JSON.stringify(Object.fromEntries(fields.filter(key => key in data).map(key => [key, data[key]]))));
}

export function cloneTranslation(data: ContentEditorData): ContentEditorData {
  const copy = editorSnapshot(data);
  delete copy.id;
  delete copy.content_version;
  delete copy.permalink;
  delete copy.pairing_confirmation;
  copy.lang = data.lang === 'lt' ? 'en' : 'lt';
  copy.other_lang_id = data.id ?? null;
  copy.draft = true;
  copy.is_active = false;
  copy.parent_id = data.translated_parent_id ?? null;
  for (const part of copy.content?.parts ?? []) {
    delete part.id;
    part.key = crypto.randomUUID();
  }
  return copy;
}

/** The fields "copy the other language" fills in; everything else a new version inherits as settings. */
export const TRANSLATION_TEXT_FIELDS = ['title', 'short', 'meta_description', 'highlights', 'content'] as const;

export function blankTranslation(data: ContentEditorData): ContentEditorData {
  const copy = cloneTranslation(data);
  const blank: ContentEditorData = { title: '', short: '', meta_description: '', highlights: [], content: { parts: [{ type: 'tiptap', json_content: {}, options: { is_active: true }, key: crypto.randomUUID() }] } };
  for (const field of TRANSLATION_TEXT_FIELDS) {
    if (field in copy) Object.assign(copy, { [field]: blank[field] });
  }
  return copy;
}

export function useContentEditor<T extends Record<string, unknown>>(kind: ContentKind, form: InertiaForm<T>, options: { stay?: boolean; identity?: string; onSaved?: (data: ContentEditorData) => void | Promise<void> } = {}) {
  const userId = usePage<PageProps>().props.auth?.user?.id ?? 'anonymous';
  const currentId = ref<number | undefined>(form.data().id as number | undefined);
  const pointer = `content-editor:${userId}:${kind}:new`;
  let identity = options.identity ?? (currentId.value ? `record-${currentId.value}` : `new-${crypto.randomUUID()}`);
  if (!currentId.value && !options.identity) {
    try {
      identity = localStorage.getItem(pointer) ?? identity;
      localStorage.setItem(pointer, identity);
    }
    catch { /* Server recovery remains available when browser storage is blocked. */ }
  }
  const storageKey = `${userId}:${kind}:${identity}`;
  const draftUrl = route('api.v1.admin.contentEditor.drafts.update', { kind, identity });
  const copies = ref<Array<RecoveryCopy & { source: 'local' | 'server' }>>([]);
  const ready = ref(false);
  const revision = ref(0);
  const status = ref<'idle' | 'local' | 'syncing' | 'synced' | 'offline' | 'conflict' | 'unavailable'>('idle');
  const saveError = ref('');
  const recordConflict = ref(false);
  let localTimer: ReturnType<typeof setTimeout> | undefined;
  let serverTimer: ReturnType<typeof setTimeout> | undefined;
  let syncing = false;
  let pendingSync: Promise<void> | undefined;
  let disposed = false;
  let baseline = JSON.stringify(editorSnapshot(form.data()));
  const savedRecord = ref(editorSnapshot(form.data()));
  const snapshot = () => editorSnapshot(form.data());
  const dirty = () => JSON.stringify(snapshot()) !== baseline;

  function acknowledgePairing(data: ContentEditorData) {
    const comparable = (value: ContentEditorData) => {
      const copy = editorSnapshot(value);
      delete copy.other_lang_id;
      delete copy.content_version;
      delete copy.pairing_confirmation;
      for (const field of ['featured_image_media', 'meta_description', 'short', 'image_media']) {
        if (field in copy) copy[field] ??= '';
      }
      // Like content_version on the server: conversions finishing must not read as an edit.
      for (const field of ['featured_image_media', 'image_media']) {
        const image = copy[field];
        if (image && typeof image === 'object') {
          const { id, focal_point, alt, author } = image as Record<string, unknown>;
          copy[field] = { id, focal_point, alt, author };
        }
      }
      if ('highlights' in copy) copy.highlights ??= [];
      if (copy.content) copy.content.parts = copy.content.parts.map(part => ({ id: part.id, type: part.type, json_content: part.json_content, options: part.options }));
      const ordered = (item: unknown): unknown => Array.isArray(item)
        ? item.map(ordered)
        : item && typeof item === 'object'
          ? Object.fromEntries(Object.entries(item).sort(([a], [b]) => a.localeCompare(b)).map(([key, child]) => [key, ordered(child)]))
          : item;
      return JSON.stringify(ordered(copy));
    };
    if (comparable(data) !== comparable(savedRecord.value)) return;
    const fields = { other_lang_id: data.other_lang_id, content_version: data.content_version };
    Object.assign(form, fields);
    form.defaults(fields as Partial<T>);
    baseline = JSON.stringify(editorSnapshot({ ...JSON.parse(baseline), ...fields }));
    savedRecord.value = data;
  }

  async function localBackup() {
    if (!ready.value || !dirty()) return;
    try {
      await recoveryStorage(storageKey, 'put', { snapshot: snapshot(), updated_at: new Date().toISOString(), revision: revision.value });
      if (status.value !== 'conflict') status.value = 'local';
    }
    catch {
      status.value = 'unavailable';
    }
  }

  function sync(): Promise<void> {
    if (pendingSync) return pendingSync;
    pendingSync = performSync().finally(() => {
      pendingSync = undefined;
    });
    return pendingSync;
  }

  async function performSync() {
    if (disposed || !ready.value || syncing || form.processing || !dirty() || status.value === 'conflict') return;
    syncing = true;
    status.value = 'syncing';
    const sent = snapshot();
    try {
      const result = await apiRequest<RecoveryCopy & { revision: number }>(draftUrl, {
        method: 'PUT',
        body: { snapshot: sent, revision: revision.value },
      });
      if (result.status === 409 && result.body !== null) {
        const server = (result.body as { data?: RecoveryCopy | null }).data;
        copies.value = [
          { snapshot: snapshot(), updated_at: new Date().toISOString(), source: 'local' },
          ...(server?.snapshot ? [{ ...server, source: 'server' as const }] : []),
        ];
        revision.value = server?.revision ?? 0;
        ready.value = false;
        status.value = 'conflict';
      }
      else if (result.ok && result.data) {
        revision.value = result.data.revision;
        status.value = 'synced';
        if (JSON.stringify(sent) !== JSON.stringify(snapshot())) serverTimer = setTimeout(sync, 2000);
      }
      else status.value = 'offline';
    }
    catch {
      status.value = 'offline';
    }
    finally {
      syncing = false;
    }
  }

  watch(() => form.data(), () => {
    if (!ready.value) return;
    clearTimeout(localTimer);
    clearTimeout(serverTimer);
    localTimer = setTimeout(localBackup, 300);
    serverTimer = setTimeout(sync, 2000);
  }, { deep: true });

  async function restore(index: number) {
    Object.assign(form, copies.value[index].snapshot);
    currentId.value = form.data().id as number | undefined;
    form.clearErrors();
    saveError.value = '';
    recordConflict.value = false;
    copies.value = [];
    ready.value = true;
    status.value = 'local';
    await localBackup();
    void sync();
  }

  async function discard() {
    try {
      const result = await apiRequest(draftUrl, { method: 'DELETE', body: { revision: revision.value } });
      if (!result.ok || result.body === null) {
        status.value = 'offline';
        return;
      }
      await recoveryStorage(storageKey, 'delete');
      revision.value = 0;
      copies.value = [];
      ready.value = true;
      status.value = 'idle';
    }
    catch {
      status.value = 'offline';
    }
  }

  async function save(stay = options.stay ?? false) {
    if (form.processing || !ready.value) return;
    form.processing = true;
    clearTimeout(serverTimer);
    await pendingSync;
    if (!ready.value) {
      form.processing = false;
      return;
    }
    await localBackup();
    const sent = snapshot();
    form.clearErrors();
    saveError.value = '';
    recordConflict.value = false;
    try {
      const url = route(`api.v1.admin.contentEditor.${kind}.${currentId.value ? 'update' : 'store'}`, currentId.value ? { [kind === 'pages' ? 'page' : 'news']: currentId.value } : undefined);
      const result = await apiRequest<ContentEditorData>(url, { method: currentId.value ? 'PATCH' : 'POST', body: sent });
      if (!result.ok || !result.data) {
        if (result.status === 422 && result.body !== null) {
          form.setError(Object.fromEntries(Object.entries(result.errors ?? {}).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value])) as Partial<Record<keyof T, string>>);
        }
        recordConflict.value = result.status === 409 && result.body !== null;
        saveError.value = contentEditorRequestError(result);
        await localBackup();
        return;
      }
      const saved = result.data;
      const unchanged = JSON.stringify(snapshot()) === JSON.stringify(sent);
      const committed = { ...sent, id: saved.id, content_version: saved.content_version, permalink: saved.permalink };
      for (const field of ['image_media', 'featured_image_media'] as const) {
        if (!(field in sent) || !(field in saved)) continue;
        const live = form.data()[field] as ContentEditorData[typeof field];
        const original = sent[field];
        const canonical = saved[field];
        committed[field] = canonical;
        if (JSON.stringify(live) === JSON.stringify(original)) {
          Object.assign(form, { [field]: canonical });
        }
        else if (live && original && canonical && live.id === original.id && live.url === original.url) {
          Object.assign(form, { [field]: { ...canonical, focal_point: live.focal_point, alt: live.alt, author: live.author } });
        }
      }
      for (const savedPart of saved.content?.parts ?? []) {
        const part = form.data().content as ContentEditorData['content'];
        const live = part?.parts.find(p => savedPart.key ? p.key === savedPart.key : p.id === savedPart.id);
        if (live) live.id = savedPart.id;
        const original = committed.content?.parts.find(p => savedPart.key ? p.key === savedPart.key : p.id === savedPart.id);
        if (original) original.id = savedPart.id;
      }
      Object.assign(form, { id: saved.id, content_version: saved.content_version });
      if (form.data().permalink === sent.permalink) Object.assign(form, { permalink: saved.permalink });
      form.defaults(committed as Partial<T>);
      baseline = JSON.stringify(editorSnapshot(committed));
      currentId.value = saved.id;
      savedRecord.value = saved;
      toast.success($t('Išsaugota'));
      await options.onSaved?.(saved);
      if (unchanged) {
        try {
          const cleanup = await apiRequest(draftUrl, { method: 'DELETE', body: { revision: revision.value } });
          if (cleanup.status === 409 && cleanup.body !== null) {
            const server = (cleanup.body as { data?: RecoveryCopy | null }).data;
            copies.value = server?.snapshot ? [{ ...server, source: 'server' }] : [];
            revision.value = server?.revision ?? 0;
            ready.value = copies.value.length === 0;
            status.value = 'conflict';
          }
          else if (cleanup.ok && cleanup.body !== null) {
            revision.value = 0;
            status.value = 'idle';
          }
          else status.value = 'offline';
        }
        catch {
          status.value = 'offline';
        }
        if (!dirty()) {
          try {
            await recoveryStorage(storageKey, 'delete');
          }
          catch {
            status.value = 'unavailable';
          }
        }
      }
      if (dirty()) await localBackup();
      if (!sent.id && !stay && !dirty()) {
        try {
          localStorage.removeItem(pointer);
        }
        catch { /* A blocked storage API must not turn a completed save into an error. */ }
        form.defaults();
        const redirect = result.meta?.redirect_url;
        router.visit(typeof redirect === 'string' ? redirect : route(`${kind}.edit`, saved.id));
      }
    }
    catch (error) {
      saveError.value = String(error);
      await localBackup();
    }
    finally {
      form.processing = false;
      if (dirty()) void sync();
    }
  }

  async function loadCurrent() {
    if (!currentId.value) return;
    try {
      const result = await apiRequest<ContentEditorData & { updated_at: string }>(route('api.v1.admin.contentEditor.show', { kind, record: currentId.value }));
      if (!result.ok || !result.data) {
        saveError.value = contentEditorRequestError(result);
        return;
      }
      const current = result.data;
      copies.value = [{ snapshot: snapshot(), source: 'local', updated_at: new Date().toISOString() }, { snapshot: current, source: 'server', updated_at: current.updated_at }];
      ready.value = false;
      // Choosing a copy acknowledges this exact server version; the user's body is retained.
      Object.assign(form, { content_version: current.content_version });
      copies.value[0].snapshot.content_version = current.content_version;
      recordConflict.value = false;
    }
    catch (error) { saveError.value = String(error); }
  }

  const unload = (event: BeforeUnloadEvent) => {
    if (dirty()) {
      event.preventDefault();
      void localBackup();
    }
  };
  onMounted(async () => {
    window.addEventListener('online', sync);
    window.addEventListener('beforeunload', unload);
    let local: RecoveryCopy | undefined;
    try {
      local = await recoveryStorage(storageKey, 'get');
      if (local && Date.now() - new Date(local.updated_at).getTime() > 30 * 86400000) {
        await recoveryStorage(storageKey, 'delete');
        local = undefined;
      }
    }
    catch { status.value = 'unavailable'; }
    let server: RecoveryCopy | undefined;
    try {
      const result = await apiRequest<RecoveryCopy>(draftUrl);
      if (result.ok && result.body !== null) {
        server = result.data ?? undefined;
        revision.value = server?.revision ?? 0;
      }
      else status.value = 'offline';
    }
    catch { status.value = 'offline'; }
    if (local && JSON.stringify(local.snapshot) !== baseline) copies.value.push({ ...local, source: 'local' });
    if (server && JSON.stringify(server.snapshot) !== baseline && JSON.stringify(server.snapshot) !== JSON.stringify(local?.snapshot)) copies.value.push({ ...server, source: 'server' });
    ready.value = copies.value.length === 0;
  });
  onBeforeUnmount(() => {
    void localBackup();
    disposed = true;
    clearTimeout(localTimer);
    clearTimeout(serverTimer);
    window.removeEventListener('online', sync);
    window.removeEventListener('beforeunload', unload);
  });
  return { copies, ready, status, saveError, recordConflict, restore, discard, save, loadCurrent, currentId, savedRecord, acknowledgePairing };
}
