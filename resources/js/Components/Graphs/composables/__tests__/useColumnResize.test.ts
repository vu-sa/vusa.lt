import { afterEach, describe, expect, it, vi } from 'vitest';
import { effectScope } from 'vue';

import { useColumnResize } from '../useColumnResize';

function pointer(type: string, init: { clientX?: number; buttons?: number; button?: number } = {}): PointerEvent {
  return new PointerEvent(type, { bubbles: true, pointerId: 1, button: 0, buttons: 1, ...init });
}

function startOn(resize: ReturnType<typeof useColumnResize>, clientX: number): HTMLElement {
  const handle = document.createElement('div');
  document.body.append(handle);
  handle.addEventListener('pointerdown', resize.startResize);
  handle.dispatchEvent(pointer('pointerdown', { clientX }));
  return handle;
}

describe('useColumnResize', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  it('resizes while the pointer moves and releases on pointerup, even when pointerdown was cancelled', () => {
    const setWidth = vi.fn();
    const resize = useColumnResize(setWidth, () => 200);
    const handle = startOn(resize, 100);

    document.dispatchEvent(pointer('pointermove', { clientX: 150 }));
    expect(setWidth).toHaveBeenLastCalledWith(250);

    handle.dispatchEvent(pointer('pointerup', { clientX: 150, buttons: 0 }));
    setWidth.mockClear();
    document.dispatchEvent(pointer('pointermove', { clientX: 200 }));

    expect(resize.isResizing.value).toBe(false);
    expect(setWidth).not.toHaveBeenCalled();
    expect(document.body.style.cursor).toBe('');
  });

  it('ends a drag whose release happened outside the window', () => {
    const setWidth = vi.fn();
    const resize = useColumnResize(setWidth, () => 200);
    startOn(resize, 100);

    document.dispatchEvent(pointer('pointermove', { clientX: 180, buttons: 0 }));

    expect(resize.isResizing.value).toBe(false);
    expect(setWidth).not.toHaveBeenCalled();
  });

  it.each(['manual', 'scope'] as const)('stops an active resize on %s cleanup', (mode) => {
    const addListener = vi.spyOn(document, 'addEventListener');
    const removeListener = vi.spyOn(document, 'removeEventListener');
    const setWidth = vi.fn();
    const scope = effectScope();
    const createResize = () => useColumnResize(setWidth, () => 200);
    const resize = mode === 'scope' ? scope.run(createResize)! : createResize();
    startOn(resize, 100);

    if (mode === 'scope') scope.stop();
    else resize.cleanup();
    document.dispatchEvent(pointer('pointermove', { clientX: 200 }));

    expect(resize.isResizing.value).toBe(false);
    expect(setWidth).not.toHaveBeenCalled();
    expect(document.body.style.userSelect).toBe('');
    for (const [event, listener] of addListener.mock.calls) {
      expect(removeListener).toHaveBeenCalledWith(event, listener);
    }
    scope.stop();
  });
});
