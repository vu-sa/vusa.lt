import { describe, expect, it } from 'vitest';
import * as d3 from 'd3';

import { lightModeColors } from '../../ganttColors';
import { renderBackground, type BackgroundRenderContext } from '../renderBackground';

function setup(overrides: Partial<BackgroundRenderContext> = {}) {
  const container = document.createElement('div');
  const svg = d3.select(container).append('svg');
  const g = svg.append('g') as unknown as d3.Selection<SVGGElement, unknown, null, undefined>;
  const minTime = new Date(2026, 0, 1);
  const maxTime = new Date(2027, 0, 1);
  const x = d3.scaleTime().domain([minTime, maxTime]).range([0, 1095]);

  renderBackground({
    g,
    x,
    layoutRows: [
      { key: 'group', type: 'group', top: 0, height: 28 },
      { key: 'a', type: 'institution', top: 28, height: 28 },
    ],
    innerWidth: 1095,
    innerHeight: 56,
    colors: lightModeColors,
    minTime,
    maxTime,
    rowTop: key => (key === 'group' ? 0 : 28),
    rowHeightFor: () => 28,
    dayWidthPx: 3,
    ...overrides,
  });

  const marks = (cls: string) => Array.from(container.querySelectorAll(`.${cls}`)).map(el => Number(el.getAttribute('x')));

  return { x, marks };
}

describe('renderBackground day marks', () => {
  it('marks the 1st of every month but January at low zoom, without Sundays', () => {
    const { x, marks } = setup({ markMonthStarts: true });

    const expected = d3.range(1, 12).map(month => x(new Date(2026, month, 1)));
    expect(marks('month-start-mark')).toEqual(expected);
    expect(marks('sunday-mark')).toEqual([]);
  });

  it('leaves month starts inside a vacation period to the vacation shading', () => {
    const { x, marks } = setup({
      markMonthStarts: true,
      vacationPeriods: [{ start: new Date(2026, 6, 1), end: new Date(2026, 7, 31), type: 'summer' }],
    });

    expect(marks('month-start-mark')).not.toContain(x(new Date(2026, 6, 1)));
    expect(marks('month-start-mark')).not.toContain(x(new Date(2026, 7, 1)));
    expect(marks('month-start-mark')).toContain(x(new Date(2026, 8, 1)));
  });

  it('draws one mark when a month starts on a Sunday at high zoom', () => {
    // 2026-02-01 is a Sunday.
    const { x, marks } = setup({ markMonthStarts: true, dayWidthPx: 20 });
    const feb1 = x(new Date(2026, 1, 1));

    expect(marks('sunday-mark').length).toBeGreaterThan(40);
    expect([...marks('sunday-mark'), ...marks('month-start-mark')].filter(value => value === feb1)).toHaveLength(1);
  });

  it('draws no day marks at low zoom when the chart has its own month grid', () => {
    const { marks } = setup();

    expect(marks('month-start-mark')).toEqual([]);
    expect(marks('sunday-mark')).toEqual([]);
  });
});
