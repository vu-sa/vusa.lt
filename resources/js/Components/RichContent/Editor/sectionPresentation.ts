import type { InjectionKey, Ref } from 'vue';

export const SECTION_PRESENTATION_DISABLED: InjectionKey<Ref<boolean | undefined>> = Symbol('section-presentation-disabled');
