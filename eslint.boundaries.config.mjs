// The full config with every rule but the tier boundaries switched off, so CI can block on them
// while the rest of `npm run lint` still carries legacy findings.
import config from './eslint.config.mjs';

const BOUNDARY_RULE = 'import-x/no-restricted-paths';

export default config.map(block => (block.rules
  ? { ...block, rules: Object.fromEntries(Object.entries(block.rules).filter(([name]) => name === BOUNDARY_RULE)) }
  : block));
