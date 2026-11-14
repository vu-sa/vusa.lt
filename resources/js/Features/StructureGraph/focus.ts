interface FocusNode {
  id: string;
  parentNode?: string;
}

interface FocusEdge {
  id: string;
  source: string;
  target: string;
}

/**
 * What stays visible when one node is in focus: the node, what it contains, what is
 * joined to either by an edge, and the groups all of those sit in.
 */
export function focusOn(id: string, nodes: FocusNode[], edges: FocusEdge[]): { nodes: Set<string>; edges: Set<string> } {
  const parentOf = new Map(nodes.map(node => [node.id, node.parentNode]));
  const inside = new Set([id]);
  for (let grew = true; grew;) {
    grew = false;
    for (const node of nodes) {
      if (node.parentNode && inside.has(node.parentNode) && !inside.has(node.id)) {
        inside.add(node.id);
        grew = true;
      }
    }
  }

  const keptNodes = new Set(inside);
  const keptEdges = new Set<string>();
  for (const edge of edges) {
    if (inside.has(edge.source) || inside.has(edge.target)) {
      keptEdges.add(edge.id);
      keptNodes.add(edge.source);
      keptNodes.add(edge.target);
    }
  }
  for (const nodeId of [...keptNodes]) {
    for (let parent = parentOf.get(nodeId); parent; parent = parentOf.get(parent)) keptNodes.add(parent);
  }

  return { nodes: keptNodes, edges: keptEdges };
}
