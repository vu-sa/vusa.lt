import { describe, expect, it } from 'vitest';

import { parseAgendaText } from '../parseAgendaText';

describe('parseAgendaText', () => {
  it('turns every non-empty line into an item and drops the numbering', () => {
    expect(parseAgendaText('1. Studijų tvarka\n\n2) Biudžetas\n- Kiti klausimai\n3.2 Priedas').map(item => item.title))
      .toEqual(['Studijų tvarka', 'Biudžetas', 'Kiti klausimai', 'Priedas']);
  });

  it('keeps a leading time range from a pasted timetable', () => {
    expect(parseAgendaText('1. 10.00–10.30 Studijų tvarka\n9:05 - 9:15. Pertrauka\n14.00 val. Kiti klausimai')).toEqual([
      { title: 'Studijų tvarka', startTime: '10:00', endTime: '10:30' },
      { title: 'Pertrauka', startTime: '09:05', endTime: '09:15' },
      { title: 'Kiti klausimai', startTime: '14:00', endTime: null },
    ]);
  });

  it('does not mistake a year or a figure in the title for a time', () => {
    expect(parseAgendaText('2026 m. biudžeto projektas\n25.5 proc. stipendijų fondo')).toEqual([
      { title: '2026 m. biudžeto projektas', startTime: null, endTime: null },
      { title: '25.5 proc. stipendijų fondo', startTime: null, endTime: null },
    ]);
  });
});
