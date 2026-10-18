/** Day widths (px) the meetings timeline zooms through; finer at the low end, where a step changes the visible span most. */
export const GANTT_ZOOM_STOPS = [1, 1.25, 1.5, 2, 2.5, 3, 3.5, 4, 5, 6, 7, 8, 9] as const;

export const DEFAULT_DAY_WIDTH = 3;

export function snapToZoomStop(width: number | undefined): number {
  const target = width ?? DEFAULT_DAY_WIDTH;

  return GANTT_ZOOM_STOPS.reduce((best, stop) => (Math.abs(stop - target) < Math.abs(best - target) ? stop : best), GANTT_ZOOM_STOPS[0]);
}

/** The stop `steps` away from `width`, clamped to the ends. */
export function stepZoomStop(width: number, steps: number): number {
  const index = GANTT_ZOOM_STOPS.indexOf(snapToZoomStop(width) as typeof GANTT_ZOOM_STOPS[number]);
  const next = Math.min(GANTT_ZOOM_STOPS.length - 1, Math.max(0, index + steps));

  return GANTT_ZOOM_STOPS[next]!;
}
