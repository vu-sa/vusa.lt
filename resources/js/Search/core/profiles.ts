export interface SearchProfile {
  version: number;
  parameters: Record<string, string | number | boolean>;
  defaultSort: string;
  facetFields: string[];
  sortFields: string[];
}

export interface SearchProfileConfig {
  searchProfiles?: Record<string, SearchProfile>;
  searchProfileVersion?: number;
  searchLocale?: string;
}

export function splitSortExpressions(value: string): string[] {
  const result: string[] = [];
  let depth = 0;
  let quoted = false;
  let start = 0;
  for (let index = 0; index < value.length; index++) {
    const character = value[index];
    if (character === '`' && value[index - 1] !== '\\') quoted = !quoted;
    if (quoted) continue;
    if (character === '(' || character === '[') depth++;
    if (character === ')' || character === ']') depth--;
    if (character === ',' && depth === 0) {
      result.push(value.slice(start, index));
      start = index + 1;
    }
  }
  result.push(value.slice(start));
  return result;
}

export function buildProfileParams(profile: SearchProfile | undefined, input: Record<string, unknown>, locale = 'lt'): Record<string, unknown> {
  if (!profile) return { ...input };
  const parameters = { ...input, ...profile.parameters };
  const fields = String(parameters.query_by).split(',');
  const aligned = ['query_by_weights', 'infix', 'num_typos'];
  const order = fields.map((field, index) => ({ field, index })).sort((a, b) => {
    const preferred = (field: string) => field.endsWith(`_${locale}`) ? -1 : 0;
    return preferred(a.field) - preferred(b.field);
  });
  parameters.query_by = order.map(item => item.field).join(',');
  for (const key of aligned) {
    const values = String(parameters[key]).split(',');
    parameters[key] = order.map(item => values[item.index]).join(',');
  }
  const query = String(input.q ?? '*').trim();
  const suppliedSort = String(input.sort_by ?? '');
  const relevanceTieBreak = suppliedSort.startsWith('_text_match')
    ? splitSortExpressions(suppliedSort).slice(1).join(',') || profile.defaultSort
    : profile.defaultSort;
  parameters.sort_by = suppliedSort && !suppliedSort.startsWith('_text_match')
    ? suppliedSort
    : query && query !== '*' ? `_text_match:desc,${relevanceTieBreak}` : profile.defaultSort;
  if (input.facet_by) {
    for (const field of String(input.facet_by).split(',')) {
      if (!profile.facetFields.includes(field)) throw new Error(`Invalid facet field: ${field}`);
    }
  }
  for (const expression of splitSortExpressions(String(parameters.sort_by))) {
    const promotion = expression.match(/^_eval\((\w+):=\[.*\]\):desc$/);
    if (promotion && profile.facetFields.includes(promotion[1])) continue;
    const field = expression.split(':')[0];
    if (field !== '_text_match' && !profile.sortFields.includes(field)) throw new Error(`Invalid sort field: ${field}`);
  }
  return parameters;
}
