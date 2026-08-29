import { describe, expect, it } from 'vitest';

import { applicableCadences, bandLadder } from '../cadencePools';
import type { ParsedCadence, ParsedRow, TimelineScope } from '../types';

function cadence(id: string, institutionId: string | null): ParsedCadence {
  return {
    id,
    label: id,
    start_date: '2024-07-01',
    end_date: '2025-06-30',
    institution_id: institutionId,
    is_global: institutionId === null,
    startDate: new Date(2024, 6, 1, 12),
    endDate: new Date(2025, 5, 30, 12),
  };
}

function row(institutionId: string | null): ParsedRow {
  return { institution_id: institutionId } as ParsedRow;
}

const GLOBAL = cadence('global-1', null);
const PARLAMENTAS = cadence('parl-1', 'inst-parl');

function scope(institutionId: string | null): TimelineScope {
  return { type: 'institution', id: 'inst-parl', institution_id: institutionId };
}

describe('applicableCadences', () => {
  it('never falls back to the global ladder once an institution defines its own', () => {
    expect(applicableCadences([GLOBAL, PARLAMENTAS], 'inst-parl')).toEqual([PARLAMENTAS]);
  });

  it('falls back to the global ladder for an institution with no cadences of its own', () => {
    expect(applicableCadences([GLOBAL, PARLAMENTAS], 'inst-other')).toEqual([GLOBAL]);
  });

  it('treats a row with no institution as global', () => {
    expect(applicableCadences([GLOBAL, PARLAMENTAS], null)).toEqual([GLOBAL]);
  });
});

describe('bandLadder', () => {
  it('draws only the override ladder for an institution that has one', () => {
    const drawn = bandLadder([GLOBAL, PARLAMENTAS], scope('inst-parl'), [row('inst-parl')]);

    // The bug this guards: drawing both stacks two translucent greens over the whole
    // domain, so the alternating tints — the thing that makes a boundary readable — vanish.
    expect(drawn).toEqual([PARLAMENTAS]);
  });

  it('falls back to the rows own institution when the scope names none', () => {
    const drawn = bandLadder([GLOBAL, PARLAMENTAS], scope(null), [row('inst-parl')]);

    expect(drawn).toEqual([PARLAMENTAS]);
  });

  it('draws the global ladder when the rows span institutions on different ladders', () => {
    const drawn = bandLadder(
      [GLOBAL, PARLAMENTAS],
      scope(null),
      [row('inst-parl'), row('inst-other')],
    );

    expect(drawn).toEqual([GLOBAL]);
  });
});
