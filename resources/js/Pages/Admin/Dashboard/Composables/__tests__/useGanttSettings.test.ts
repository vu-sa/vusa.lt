import { beforeEach, describe, expect, it } from 'vitest';
import { defineComponent, nextTick } from 'vue';
import { mount } from '@vue/test-utils';

import { provideGanttSettings, type GanttSettings } from '../useGanttSettings';

function settings(): GanttSettings {
  let provided: GanttSettings | undefined;
  mount(defineComponent({
    setup() {
      provided = provideGanttSettings();
      return () => null;
    },
  }));
  return provided!;
}

describe('useGanttSettings', () => {
  beforeEach(() => localStorage.clear());

  it('keeps a stored "no padalinys sections" choice as ungrouped', () => {
    localStorage.setItem('gantt-settings', JSON.stringify({ showTenantHeaders: false }));

    expect(settings().groupBy.value).toBe('none');
  });

  it('groups by padalinys by default and remembers a new choice', async () => {
    const first = settings();
    expect(first.groupBy.value).toBe('tenant');

    first.groupBy.value = 'type';
    await nextTick();

    expect(JSON.parse(localStorage.getItem('gantt-settings')!)).toMatchObject({ groupBy: 'type' });
  });

  it('snaps a stored zoom to the nearest stop the slider can show', () => {
    localStorage.setItem('gantt-settings', JSON.stringify({ dayWidthPx: 12 }));

    expect(settings().dayWidthPx.value).toBe(9);
  });
});
