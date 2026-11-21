// The full config with every rule but the blocking ones switched off, so CI can block on them
// while the rest of `npm run lint` still carries legacy findings.
import config from './eslint.config.mjs';

const BLOCKING_RULES = new Set([
  'import-x/no-restricted-paths',
  'html-source/require-comment',
]);

export default config.map(block => (block.rules
  ? { ...block, rules: Object.fromEntries(Object.entries(block.rules).filter(([name]) => BLOCKING_RULES.has(name))) }
  : block));
