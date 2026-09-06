import type { InjectionKey, Ref } from 'vue';

export const SMART_TIPTAP_TOOLBAR_PORTAL_KEY: InjectionKey<Ref<HTMLElement | null>> = Symbol('smart-tiptap-toolbar-portal');
