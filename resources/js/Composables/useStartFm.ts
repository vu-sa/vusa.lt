import { inject, onScopeDispose, provide, ref, shallowRef, type InjectionKey } from 'vue';

export type StartFmStatus = 'idle' | 'connecting' | 'playing' | 'paused' | 'error';

const STREAMS = [
  'https://eteris.startfm.lt/startfm.mp3',
  'https://eteris.startfm.lt/startfm.m4a',
];

function createStartFmContext() {
  const isOpen = ref(false);
  const status = ref<StartFmStatus>('idle');
  const volume = ref(0.35);
  const muted = ref(false);
  const volumeSupported = ref(true);
  const bottomInset = ref(0);
  const desktopHeight = ref(0);
  const audio = shallowRef<HTMLAudioElement | null>(null);
  let attempt = 0;
  let streamIndex = 0;
  let connectionTimeout: ReturnType<typeof setTimeout> | undefined;

  function clearConnectionTimeout() {
    clearTimeout(connectionTimeout);
    connectionTimeout = undefined;
  }

  function waitForConnection() {
    clearConnectionTimeout();
    connectionTimeout = setTimeout(() => {
      attempt++;
      status.value = 'error';
      release();
    }, 15_000);
  }

  function release() {
    if (!audio.value?.hasAttribute('src')) return;
    audio.value.pause();
    audio.value.removeAttribute('src');
    audio.value.load();
  }

  function close() {
    clearConnectionTimeout();
    attempt++;
    isOpen.value = false;
    status.value = 'idle';
    release();
  }

  function attachAudio(element: HTMLAudioElement | null) {
    if (audio.value === element) return;
    close();
    audio.value = element;
    if (element) {
      element.volume = volume.value;
      volumeSupported.value = Math.abs(element.volume - volume.value) < 0.01;
      element.muted = muted.value;
    }
  }

  async function play() {
    const element = audio.value;
    if (!element) return;
    const currentAttempt = ++attempt;
    status.value = 'connecting';
    waitForConnection();
    element.src = STREAMS[streamIndex];
    try {
      await element.play();
      if (currentAttempt !== attempt || !isOpen.value) return;
      clearConnectionTimeout();
      status.value = 'playing';
    }
    catch (error) {
      if (currentAttempt !== attempt || !isOpen.value) return;
      if (error instanceof DOMException && error.name === 'NotSupportedError') {
        onError();
      }
      else {
        clearConnectionTimeout();
        status.value = 'error';
      }
    }
  }

  function open() {
    isOpen.value = true;
    if (status.value === 'playing' || status.value === 'connecting') return;
    streamIndex = 0;
    void play();
  }

  function toggle() {
    if (status.value === 'playing' || status.value === 'connecting') {
      clearConnectionTimeout();
      attempt++;
      status.value = 'paused';
      audio.value?.pause();
    }
    else {
      open();
    }
  }

  function setVolume(value: number) {
    if (!Number.isFinite(value)) return;
    volume.value = Math.max(0, Math.min(1, value));
    muted.value = false;
    if (audio.value) {
      audio.value.volume = volume.value;
      audio.value.muted = false;
    }
  }

  function toggleMute() {
    muted.value = !muted.value;
    if (audio.value) audio.value.muted = muted.value;
  }

  function onPlaying() {
    if (!isOpen.value || !['connecting', 'playing'].includes(status.value)) {
      audio.value?.pause();
      return;
    }
    clearConnectionTimeout();
    status.value = 'playing';
  }

  function onWaiting() {
    if (isOpen.value && status.value === 'playing') {
      status.value = 'connecting';
      waitForConnection();
    }
  }

  function onPause() {
    if (isOpen.value && status.value === 'playing') status.value = 'paused';
  }

  function onError() {
    if (!isOpen.value || status.value === 'paused' || status.value === 'error') return;
    if (streamIndex < STREAMS.length - 1) {
      streamIndex++;
      void play();
    }
    else {
      clearConnectionTimeout();
      attempt++;
      status.value = 'error';
    }
  }

  onScopeDispose(close);

  return {
    isOpen, status, volume, muted, volumeSupported, bottomInset, desktopHeight,
    open, close, toggle, setVolume, toggleMute, attachAudio,
    onPlaying, onWaiting, onPause, onError,
  };
}

export type StartFmContext = ReturnType<typeof createStartFmContext>;
const START_FM_KEY: InjectionKey<StartFmContext> = Symbol('start-fm');

export function createStartFmProvider(): StartFmContext {
  const context = createStartFmContext();
  provide(START_FM_KEY, context);
  return context;
}

export function useStartFm(): StartFmContext {
  return inject(START_FM_KEY, null) ?? createStartFmContext();
}
