import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, ref } from 'vue';

import { usePreviewApi } from '../usePreviewApi';

const api = vi.hoisted(() => ({ execute: vi.fn(), abort: vi.fn() }));

vi.mock('@/Composables/useApi', () => ({
  useApi: () => ({ data: ref(null), error: ref(null), execute: api.execute, abort: api.abort }),
}));

const Host = defineComponent({
  props: { disabled: Boolean },
  setup(props) {
    usePreviewApi('/api/preview/1', !props.disabled);
    return () => h('div');
  },
});

beforeEach(() => {
  vi.useFakeTimers();
  api.execute.mockClear();
  api.abort.mockClear();
});

afterEach(() => {
  vi.useRealTimers();
});

describe('usePreviewApi', () => {
  it('fetches once the selection has settled', () => {
    mount(Host);
    expect(api.execute).not.toHaveBeenCalled();

    vi.advanceTimersByTime(150);
    expect(api.execute).toHaveBeenCalledOnce();
  });

  it('never fetches for a row the arrow keys only passed over', () => {
    mount(Host).unmount();
    vi.advanceTimersByTime(500);

    expect(api.execute).not.toHaveBeenCalled();
    expect(api.abort).toHaveBeenCalled();
  });

  it('stays idle when the pane needs no extra data', () => {
    mount(Host, { props: { disabled: true } });
    vi.advanceTimersByTime(500);

    expect(api.execute).not.toHaveBeenCalled();
  });
});
