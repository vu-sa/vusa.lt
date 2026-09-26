import { describe, expect, it } from 'vitest';
import * as d3 from 'd3';

import { renderCadenceBands } from '../renderers/renderCadenceBands';
import { renderCollapsedGroupBars } from '../renderers/renderCollapsedGroupBars';
import { renderDutiableBars } from '../renderers/renderDutiableBars';
import { getTimelineColors } from '../timelineColors';
import type { ParsedCadence, ParsedRow, TimelineGroup, TimelineLayoutRow } from '../types';

import { getGanttColors } from '@/Components/Graphs/ganttColors';

/**
 * The renderers draw from a d3 scale, not from layout, so jsdom sees the same attributes a
 * browser would. Only the drag gesture and the page's height need a real browser.
 */
const timelineColors = getTimelineColors(false);
const colors = getGanttColors(false);

const x = d3.scaleTime()
  .domain([new Date(2024, 0, 1), new Date(2026, 0, 1)])
  .range([0, 24 * 64]);

const group: TimelineGroup = { key: 'duty:duty-1', kind: 'duty', label: 'Pirmininkas' };

function makeRow(overrides: Partial<ParsedRow> = {}): ParsedRow {
  return {
    id: 'row-1',
    group_key: group.key,
    duty_id: 'duty-1',
    duty_name: 'Pirmininkas',
    institution_id: 'inst-1',
    institution_name: 'Parlamentas',
    holder_id: 'u1',
    holder_name: 'Vardas Pavardė',
    holder_photo: null,
    tenant_id: null,
    tenant_shortname: null,
    cadence_id: 'cad-1',
    extras: null,
    start_date: '2024-07-01',
    end_date: '2025-06-30',
    via_dutiable_id: null,
    source: null,
    derived_ids: [],
    is_derived: false,
    editable: true,
    edit_url: '/mano/dutiables/row-1/edit',
    startDate: new Date(2024, 6, 1, 12),
    endDate: new Date(2025, 5, 30, 12),
    ...overrides,
  };
}

function lane(row: ParsedRow, top = 26): TimelineLayoutRow {
  return { key: row.id, type: 'row', top, height: 22, group, row };
}

function svgGroup() {
  const svg = d3.select(document.createElementNS('http://www.w3.org/2000/svg', 'svg'));

  return svg.append('g');
}

function drawBars(rows: ParsedRow[]) {
  const g = svgGroup();

  renderDutiableBars({
    g,
    x,
    colors,
    timelineColors,
    layoutRows: rows.map((row, index) => lane(row, 26 + index * 22)),
    innerWidth: 24 * 64,
    selectedIds: new Set(),
    staged: new Map(),
  });

  return g.node()!;
}

describe('renderDutiableBars', () => {
  it('draws square bars', () => {
    const node = drawBars([makeRow()]);

    expect(node.querySelector('rect.bar-body')!.hasAttribute('rx')).toBe(false);
  });

  it('notches a start date that is not on a month boundary', () => {
    const node = drawBars([makeRow({ start_date: '2024-05-18', startDate: new Date(2024, 4, 18, 12) })]);

    expect(node.querySelectorAll('g.off-boundary')).toHaveLength(1);
  });

  it('draws no notch for a term that starts on the 1st and ends on the last day of a month', () => {
    const node = drawBars([makeRow()]);

    expect(node.querySelectorAll('g.off-boundary')).toHaveLength(0);
  });

  /** Ex officio is carried by the dashed edge alone, so it must actually be stroked. */
  it('outlines an ex-officio bar with a dashed stroke', () => {
    const node = drawBars([makeRow({ is_derived: true, end_date: null, endDate: null })]);
    const body = node.querySelector('rect.bar-body')!;

    expect(body.getAttribute('stroke')).toBe(timelineColors.derivedStroke);
    expect(body.getAttribute('stroke-dasharray')).toBe('4,3');
  });
});

describe('renderCollapsedGroupBars', () => {
  it('summarises a collapsed duty with one square bar across its span', () => {
    const g = svgGroup();
    const header: TimelineLayoutRow = { key: group.key, type: 'tenant', top: 0, height: 26, group };

    renderCollapsedGroupBars({
      g,
      x,
      timelineColors,
      layoutRows: [header],
      collapsed: new Set([group.key]),
      summaries: new Map([[group.key, { count: 5, start: new Date(2024, 6, 1), end: new Date(2025, 5, 30) }]]),
      innerWidth: 24 * 64,
    });

    const bar = g.node()!.querySelector('rect.collapsed-group-bar')!;

    expect(Number(bar.getAttribute('width'))).toBeGreaterThan(2);
    expect(bar.hasAttribute('rx')).toBe(false);
  });

  it('draws nothing for a group that is expanded', () => {
    const g = svgGroup();
    const header: TimelineLayoutRow = { key: group.key, type: 'tenant', top: 0, height: 26, group };

    renderCollapsedGroupBars({
      g,
      x,
      timelineColors,
      layoutRows: [header],
      collapsed: new Set(),
      summaries: new Map([[group.key, { count: 5, start: new Date(2024, 6, 1), end: null }]]),
      innerWidth: 24 * 64,
    });

    expect(g.node()!.querySelectorAll('rect.collapsed-group-bar')).toHaveLength(0);
  });
});

describe('renderCadenceBands', () => {
  const cadences: ParsedCadence[] = [
    { id: 'cad-1', label: '2024–2025', start_date: '2024-07-01', end_date: '2025-06-30', institution_id: null, is_global: true, startDate: new Date(2024, 6, 1), endDate: new Date(2025, 5, 30) },
    { id: 'cad-2', label: '2025–2026', start_date: '2025-07-01', end_date: '2026-06-30', institution_id: null, is_global: true, startDate: new Date(2025, 6, 1), endDate: new Date(2026, 5, 30) },
  ];

  it('draws one neutral band per cadence, alternating like table rows', () => {
    const g = svgGroup();

    renderCadenceBands({ g, x, innerHeight: 200, colors, timelineColors, cadences });

    const bands = [...g.node()!.querySelectorAll('rect.cadence-band')];

    expect(bands).toHaveLength(2);
    expect(bands.map(band => band.getAttribute('fill'))).toEqual(timelineColors.cadenceBand);
  });

  it('marks the filtered cadence and pushes the rest back', () => {
    const g = svgGroup();

    renderCadenceBands({ g, x, innerHeight: 200, colors, timelineColors, cadences, highlightedIds: new Set(['cad-2']) });

    const fills = [...g.node()!.querySelectorAll('rect.cadence-band')].map(band => band.getAttribute('fill'));

    expect(fills).toEqual([timelineColors.cadenceBandDim, timelineColors.cadenceBandHighlight]);
  });
});
