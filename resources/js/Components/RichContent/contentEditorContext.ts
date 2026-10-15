import type { InjectionKey, Ref } from 'vue';

import type { ContentEditorData, ContentKind, useContentEditor } from '@/Composables/useContentEditor';

export interface ContentEditorContext {
  kind: ContentKind;
  form: ContentEditorData & { processing: boolean; isDirty: boolean; errors: Record<string, string> };
  save: (stay?: boolean) => Promise<void>;
  acknowledgePairing?: (data: ContentEditorData) => void;
  ready: Ref<boolean>;
  recovery?: ReturnType<typeof useContentEditor>;
  error: Ref<string>;
}

export const CONTENT_EDITOR_CONTEXT: InjectionKey<ContentEditorContext> = Symbol('content-editor-context');
