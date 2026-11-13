import { h, type FunctionalComponent } from 'vue';

/** A three-tier fir in Lucide's stroke; Lucide's own `TreePine` reads as a bell at 16 px. */
export const FirIcon: FunctionalComponent = () => h('svg', {
  'viewBox': '0 0 24 24',
  'fill': 'none',
  'stroke': 'currentColor',
  'stroke-width': 2,
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round',
  'aria-hidden': 'true',
}, [h('path', { d: 'M12 2 8 8h2.5l-4 5.5H9L5 19h14l-4-5.5h2.5l-4-5.5H16Z' }), h('path', { d: 'M12 19v3' })]);
