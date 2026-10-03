import { inject, provide, ref, type InjectionKey, type Ref } from 'vue';

interface StartFmContext {
  isOpen: Ref<boolean>;
  open: () => void;
  close: () => void;
}

const START_FM_KEY: InjectionKey<StartFmContext> = Symbol('start-fm');

export function createStartFmProvider(): StartFmContext {
  const isOpen = ref(false);
  const context = {
    isOpen,
    open: () => { isOpen.value = true; },
    close: () => { isOpen.value = false; },
  };
  provide(START_FM_KEY, context);

  return context;
}

export function useStartFm(): StartFmContext {
  const context = inject(START_FM_KEY, null);
  if (!context) {
    const isOpen = ref(false);
    return {
      isOpen,
      open: () => { isOpen.value = true; },
      close: () => { isOpen.value = false; },
    };
  }

  return context;
}
