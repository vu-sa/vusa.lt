import { afterEach, describe, expect, it, vi } from 'vitest';
import { computed, effectScope, ref } from 'vue';
import * as d3 from 'd3';

import { useDragSelection } from '../useDragSelection';

describe('useDragSelection cleanup', () => {
  afterEach(() => vi.restoreAllMocks());

  it.each(['manual', 'scope', 'detach'] as const)('cancels an active selection and removes handlers on %s cleanup', (mode) => {
    const warn = vi.spyOn(console, 'warn');
    const addListener = vi.spyOn(document, 'addEventListener');
    const removeListener = vi.spyOn(document, 'removeEventListener');
    const container = document.createElement('div');
    const containerRef = ref<HTMLElement | null>(container);
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    const complete = vi.fn();
    const scope = effectScope();
    const createSelection = () => useDragSelection(
      containerRef,
      ref(svg),
      ref(d3.scaleTime().domain([new Date(2026, 0, 1), new Date(2026, 11, 31)]).range([0, 1000])),
      computed(() => [{ key: 1, type: 'institution' as const, institutionId: 1, top: 0, height: 50 }]),
      { onDragComplete: complete },
    );
    const selection = mode === 'scope' ? scope.run(createSelection)! : createSelection();
    const detach = selection.attachDragHandler();
    container.dispatchEvent(new MouseEvent('mousedown', { shiftKey: true, clientX: 100, clientY: 20 }));
    document.dispatchEvent(new MouseEvent('mousemove', { shiftKey: true, clientX: 200, clientY: 20 }));
    expect(selection.state.value.isDragging).toBe(true);

    containerRef.value = null;
    if (mode === 'scope') scope.stop();
    if (mode === 'manual') selection.cleanup();
    if (mode === 'detach') detach();
    containerRef.value = container;
    document.dispatchEvent(new MouseEvent('mouseup'));
    container.dispatchEvent(new MouseEvent('mousedown', { shiftKey: true, clientX: 100, clientY: 20 }));
    document.dispatchEvent(new MouseEvent('mousemove', { shiftKey: true, clientX: 200, clientY: 20 }));
    document.dispatchEvent(new MouseEvent('mouseup'));

    expect(complete).not.toHaveBeenCalled();
    expect(selection.state.value.isDragging).toBe(false);
    expect(selection.state.value.institutionId).toBeNull();
    for (const [event, listener] of addListener.mock.calls) {
      expect(removeListener).toHaveBeenCalledWith(event, listener);
    }
    expect(warn).not.toHaveBeenCalled();
    detach();
    selection.cleanup();
    scope.stop();
  });
});
