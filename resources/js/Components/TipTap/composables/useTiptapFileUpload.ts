import { ref } from 'vue';
import type { Editor } from '@tiptap/core';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';
import { trans } from 'laravel-vue-i18n';

import { useToasts } from '@/Composables/useToasts';
import { uploadFiles } from '@/Composables/useFileUpload';

export function useTiptapFileUpload() {
  const toasts = useToasts();
  const uploadingFiles = ref(new Map<string, { fileName: string }>());
  const trackers = new Map<Editor, PluginKey<DecorationSet>>();
  let cleared = false;

  function tracker(editor: Editor) {
    const existing = trackers.get(editor);
    if (existing) return existing;
    const key = new PluginKey<DecorationSet>('contentUploads');
    editor.registerPlugin(new Plugin({
      key,
      state: {
        init: () => DecorationSet.empty,
        apply(transaction, decorations) {
          let next = decorations.map(transaction.mapping, transaction.doc);
          const action = transaction.getMeta(key) as { add?: Decoration[]; remove?: string } | undefined;
          if (action?.add) next = next.add(transaction.doc, action.add);
          if (action?.remove) next = next.remove(next.find(undefined, undefined, spec => spec.uploadId === action.remove));
          return next;
        },
      },
      props: { decorations: state => key.getState(state) },
    }));
    trackers.set(editor, key);
    return key;
  }

  async function handleFileDrop(editor: Editor, files: File[], pos = editor.state.selection.from) {
    const key = tracker(editor);
    const pending = files.map((file, index) => {
      const uploadId = crypto.randomUUID();
      uploadingFiles.value.set(uploadId, { fileName: file.name });
      const decoration = Decoration.widget(pos, () => {
        const label = document.createElement('span');
        label.className = 'text-sm text-muted-foreground';
        label.textContent = trans('editor.uploading', { name: file.name });
        return label;
      }, { uploadId, side: index + 1, key: uploadId });
      return { file, uploadId, decoration };
    });
    editor.view.dispatch(editor.state.tr.setMeta(key, { add: pending.map(item => item.decoration) }));
    for (const { file, uploadId } of pending) {
      if (cleared || editor.isDestroyed) break;
      try {
        const result = await uploadFiles([file], getUploadPath());
        const stored = result.uploaded[0];
        if (!stored) throw new Error(result.failed[0]?.reason ?? trans('editor.upload_failed'));
        if (cleared || editor.isDestroyed) continue;
        const marker = key.getState(editor.state)?.find(undefined, undefined, spec => spec.uploadId === uploadId)[0];
        if (!marker) continue;
        const content = file.type.startsWith('image/')
          ? { type: 'image', attrs: { src: stored.url, alt: file.name } }
          : file.type.startsWith('video/')
            ? { type: 'video', attrs: { src: stored.url } }
            : { type: 'text', text: stored.name, marks: [{ type: 'link', attrs: { href: stored.url, target: '_blank', rel: 'noopener noreferrer' } }] };
        editor.commands.insertContentAt(marker.from, content);
      }
      catch (error) {
        if (!cleared && !editor.isDestroyed) toasts.error(trans('editor.upload_failed'), { description: error instanceof Error ? error.message : String(error) });
      }
      finally {
        uploadingFiles.value.delete(uploadId);
        if (!editor.isDestroyed) editor.view.dispatch(editor.state.tr.setMeta(key, { remove: uploadId }));
      }
    }
  }

  function clearPendingUploads() {
    cleared = true;
    uploadingFiles.value.clear();
    for (const [editor, key] of trackers) {
      if (!editor.isDestroyed) editor.unregisterPlugin(key);
    }
    trackers.clear();
  }

  return { uploadingFiles, handleFileDrop, handleFilePaste: (editor: Editor, files: File[]) => handleFileDrop(editor, files), clearPendingUploads };
}

function getUploadPath(): string {
  const date = new Date();
  return `content/${date.getFullYear()}/${String(date.getMonth() + 1).padStart(2, '0')}`;
}
