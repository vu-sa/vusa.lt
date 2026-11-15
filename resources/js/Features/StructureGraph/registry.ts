import index from './graphs/index.json';
import type { GraphDef, Localized } from './types';

export interface GraphEntry {
  key: string;
  title: Localized;
  description: Localized;
  load: () => Promise<GraphDef>;
}

// JSON so the server can check a saved graph and node (`App\Support\StructureGraphs`).
const files = import.meta.glob<GraphDef>(['./graphs/*.json', '!./graphs/index.json'], { import: 'default' });

/** Every structure graph, in the order editors see them; each loads as its own chunk. */
export const graphRegistry: GraphEntry[] = (index as Omit<GraphEntry, 'load'>[]).map(entry => ({
  ...entry,
  load: () => files[`./graphs/${entry.key}.json`]!(),
}));

/** Rich-content blocks saved before the registry stored the old preset name. */
const aliases: Record<string, string> = { VusaStructure: 'vusa-structure' };

export function findGraph(key: string | null | undefined): GraphEntry | undefined {
  if (!key) return undefined;
  const resolved = aliases[key] ?? key;
  return graphRegistry.find(entry => entry.key === resolved);
}
