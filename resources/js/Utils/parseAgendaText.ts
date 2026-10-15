export interface ParsedAgendaLine {
  title: string;
  /** `HH:MM`, when the line opened with a time. */
  startTime: string | null;
  endTime: string | null;
}

const HOUR = '([01]?\\d|2[0-3])';
const MINUTE = '([0-5]\\d)';
const LEADING_TIME = new RegExp(`^${HOUR}[:.]${MINUTE}(?:\\s*[-–—]\\s*${HOUR}[:.]${MINUTE})?(?:\\s*(?:val\\.?|h))?[\\s.:,–—-]+`, 'i');
const SIMPLE_NUMBERING = /^(?:\d+[.)]|[-*•–—])\s+/;
// "3.2 Title", tried after the time check ("10.00 Title" looks alike); a capital keeps "25.5 proc." whole.
const NESTED_NUMBERING = /^\d+(?:\.\d+)+\.?\s+(?=[A-ZĄČĘĖĮŠŲŪŽ„"])/;

const time = (hour?: string, minute?: string) => (hour && minute ? `${hour.padStart(2, '0')}:${minute}` : null);

/**
 * Invitation agendas arrive numbered, bulleted or as a timetable ("10.00–10.30 Studijų tvarka").
 * Each non-empty line becomes one item; numbering is dropped and a leading time range kept.
 */
export function parseAgendaText(text: string): ParsedAgendaLine[] {
  return text
    .split(/\r?\n/)
    .map(line => line.trim())
    .filter(line => line !== '')
    .map((line) => {
      let rest = line.replace(SIMPLE_NUMBERING, '');
      let startTime: string | null = null;
      let endTime: string | null = null;

      const timeMatch = rest.match(LEADING_TIME);
      if (timeMatch) {
        startTime = time(timeMatch[1], timeMatch[2]);
        endTime = time(timeMatch[3], timeMatch[4]);
        rest = rest.slice(timeMatch[0].length);
      }
      else {
        rest = rest.replace(NESTED_NUMBERING, '');
      }

      return { title: rest.trim(), startTime, endTime };
    })
    .filter(item => item.title !== '');
}
