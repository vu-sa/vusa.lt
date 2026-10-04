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
    ? suppliedSort.split(',').slice(1).join(',') || profile.defaultSort
    : profile.defaultSort;
  parameters.sort_by = suppliedSort && !suppliedSort.startsWith('_text_match')
    ? suppliedSort
    : query && query !== '*' ? `_text_match:desc,${relevanceTieBreak}` : profile.defaultSort;
  if (input.facet_by) {
    for (const field of String(input.facet_by).split(',')) {
      if (!profile.facetFields.includes(field)) throw new Error(`Invalid facet field: ${field}`);
    }
  }
  for (const expression of String(parameters.sort_by).split(',')) {
    const field = expression.split(':')[0];
    if (field !== '_text_match' && !profile.sortFields.includes(field)) throw new Error(`Invalid sort field: ${field}`);
  }
  return parameters;
}
