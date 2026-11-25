import { h } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

const frame = { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 };

const CardHeroIcon = () => h('svg', frame, [
  h('rect', { x: 4, y: 4, width: 88, height: 56, rx: 6 }),
  h('path', { d: 'M4 40 h88' }),
  h('path', { d: 'M12 48 h20' }),
  h('path', { d: 'M12 30 l10 -8 l12 10 l8 -6 l14 10' }),
  h('circle', { cx: 74, cy: 16, r: 4 }),
]);

const SplitHeroIcon = () => h('svg', frame, [
  h('rect', { x: 4, y: 4, width: 88, height: 56, rx: 6 }),
  h('path', { d: 'M40 4 v56' }),
  h('path', { d: 'M48 16 h32 M48 26 h24 M48 36 h32' }),
]);

const MinimalHeroIcon = () => h('svg', frame, [
  h('path', { d: 'M8 20 h80' }),
  h('path', { d: 'M8 32 h56' }),
  h('path', { d: 'M8 44 h32' }),
  h('circle', { cx: 82, cy: 44, r: 6 }),
]);

/** The public event page's header layouts, each with a sketch of its shape. */
export function calendarHeroStyleOptions() {
  return [
    { value: 'card', label: $t('Didelė kortelė'), description: $t('Nuotrauka fone'), icon: CardHeroIcon },
    { value: 'split', label: $t('Nuotrauka šalia'), description: $t('Kortelė su nuotrauka'), icon: SplitHeroIcon },
    { value: 'minimal', label: $t('Minimalus'), description: $t('Be nuotraukos'), icon: MinimalHeroIcon },
  ];
}
