import { afterEach, describe, expect, it, vi } from 'vitest';
import { effectScope } from 'vue';

import { useColumnResize } from '../useColumnResize';

describe('useColumnResize cleanup', () => {
  afterEach(() => vi.restoreAllMocks());

  it.each(['manual', 'scope'] as const)('stops an active resize on %s cleanup', (mode) => {
    const warn = vi.spyOn(console, 'warn');
    const addListener = vi.spyOn(document, 'addEventListener');
    const removeListener = vi.spyOn(document, 'removeEventListener');
    const setWidth = vi.fn();
    const scope = effectScope();
    const createResize = () => useColumnResize(setWidth, () => 200);
    const resize = mode === 'scope' ? scope.run(createResize)! : createResize();
    resize.startResize(new MouseEvent('mousedown', { clientX: 100 }));
    document.dispatchEvent(new MouseEvent('mousemove', { clientX: 150 }));
    expect(setWidth).toHaveBeenLastCalledWith(250);
    setWidth.mockClear();

    if (mode === 'scope') scope.stop();
    else resize.cleanup();
    document.dispatchEvent(new MouseEvent('mousemove', { clientX: 200 }));

    expect(resize.isResizing.value).toBe(false);
    expect(setWidth).not.toHaveBeenCalled();
    expect(document.body.style.userSelect).toBe('');
    expect(document.body.style.cursor).toBe('');
    for (const [event, listener] of addListener.mock.calls) {
      expect(removeListener).toHaveBeenCalledWith(event, listener);
    }
    expect(warn).not.toHaveBeenCalled();
    resize.cleanup();
    scope.stop();
  });
});
