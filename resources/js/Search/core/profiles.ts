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

const explicitShaPattern = /(?<![\p{L}\p{M}\p{N}])ša(?![\p{L}\p{M}\p{N}])/iu;
const explicitVuSaGlobal = /(?<![\p{L}\p{M}\p{N}])vu\s+ša(?![\p{L}\p{M}\p{N}])/giu;

function quoteExplicitVuSa(query: string): string {
  return query.trim().replace(/\s+/gu, ' ').split('"')
    .map((part, index) => index % 2 ? part : part.replace(explicitVuSaGlobal, '"vu ša"'))
    .join('"');
}

function preserveAccentedAcronym(parameters: Record<string, unknown>): Record<string, unknown> {
  const query = String(parameters.q ?? '').normalize('NFC');
  if (!explicitShaPattern.test(query)) return parameters;

  const phraseQuery = quoteExplicitVuSa(query);
  const fields = String(parameters.query_by ?? '').split(',').map(field => field.trim()).filter(Boolean);

  // Default Typesense fields fold š -> s at index time; search_text_lt uses locale: lt and preserves š.
  if (fields.includes('search_text_lt')) {
    return {
      ...parameters,
      q: phraseQuery,
      query_by: 'search_text_lt',
      query_by_weights: '1',
      infix: 'off',
      num_typos: '2',
      ...(parameters.highlight_fields ? { highlight_fields: 'search_text_lt' } : {}),
      drop_tokens_threshold: 0,
      split_join_tokens: 'off',
      enable_synonyms: false,
      enable_curations: false,
      pinned_hits: undefined,
    };
  }

  // Fallback for profiles without search_text_lt: never fold š -> s into VU SA.
  return {
    ...parameters,
    q: phraseQuery.toLowerCase(),
    pre_segmented_query: true,
    drop_tokens_threshold: 0,
    num_typos: 0,
    infix: 'off',
    split_join_tokens: 'off',
    enable_synonyms: false,
    enable_curations: false,
    pinned_hits: undefined,
  };
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
  if (!profile) return preserveAccentedAcronym({ ...input });
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
  return preserveAccentedAcronym(parameters);
}
