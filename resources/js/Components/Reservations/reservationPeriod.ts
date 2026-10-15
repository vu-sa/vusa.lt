import { formatDate, formatTime } from '@/Utils/dateTime';

/** "2026-10-02 09:00 – 10-04 17:00": the end drops the year it shares with the start. */
export function formatReservationPeriod(start: number, end: number): string {
  const startDate = formatDate(start);
  const endDate = formatDate(end);
  const endDay = endDate === startDate ? '' : `${endDate.slice(0, 4) === startDate.slice(0, 4) ? endDate.slice(5) : endDate} `;

  return `${startDate} ${formatTime(start)} – ${endDay}${formatTime(end)}`;
}
