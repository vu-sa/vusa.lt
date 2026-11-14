interface FocusNode {
  id: string;
  parentNode?: string;
}

interface FocusEdge {
  id: string;
  source: string;
  target: string;
}

/** The given nodes and everything nested inside them. */
function withMembers(ids: Iterable<string>, nodes: FocusNode[]): Set<string> {
  const kept = new Set(ids);
  for (let grew = true; grew;) {
    grew = false;
    for (const node of nodes) {
      if (node.parentNode && kept.has(node.parentNode) && !kept.has(node.id)) {
        kept.add(node.id);
        grew = true;
      }
    }
  }
  return kept;
}

/**
 * What stays visible when one node is in focus: the node and its members, whatever an
 * edge ties them to (a group with all its members), and the groups all of those sit in.
 */
export function focusOn(id: string, nodes: FocusNode[], edges: FocusEdge[]): { nodes: Set<string>; edges: Set<string> } {
  const parentOf = new Map(nodes.map(node => [node.id, node.parentNode]));
  const inside = withMembers([id], nodes);

  const tied = new Set<string>();
  const keptEdges = new Set<string>();
  for (const edge of edges) {
    if (inside.has(edge.source) || inside.has(edge.target)) {
      keptEdges.add(edge.id);
      tied.add(edge.source);
      tied.add(edge.target);
    }
  }

  const keptNodes = new Set([...inside, ...withMembers(tied, nodes)]);
  for (const nodeId of [...keptNodes]) {
    for (let parent = parentOf.get(nodeId); parent; parent = parentOf.get(parent)) keptNodes.add(parent);
  }

  return { nodes: keptNodes, edges: keptEdges };
}
