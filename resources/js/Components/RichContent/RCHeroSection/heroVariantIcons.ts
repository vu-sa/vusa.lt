import { h } from 'vue';

/** Schematics of each hero variant, shared by the form picker and the toolbar popover. */
export const SplitVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 6, y: 12, width: 34, height: 8, rx: 2 }),
  h('rect', { x: 6, y: 26, width: 34, height: 14, rx: 2 }),
  h('rect', { x: 6, y: 46, width: 16, height: 8, rx: 2 }),
  h('rect', { x: 50, y: 8, width: 40, height: 48, rx: 3 }),
]);

export const CenteredVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 28, y: 12, width: 40, height: 8, rx: 2 }),
  h('rect', { x: 20, y: 26, width: 56, height: 10, rx: 2 }),
  h('rect', { x: 38, y: 42, width: 20, height: 8, rx: 2 }),
]);

export const BannerVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 4, y: 26, width: 60, height: 12, rx: 2 }),
  h('rect', { x: 72, y: 26, width: 20, height: 12, rx: 2 }),
]);

export const PanelVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 2, y: 2, width: 92, height: 60, rx: 8 }),
  h('rect', { x: 10, y: 16, width: 32, height: 32, rx: 6 }),
  h('rect', { x: 50, y: 18, width: 36, height: 8, rx: 2 }),
  h('rect', { x: 50, y: 30, width: 36, height: 14, rx: 2 }),
]);
