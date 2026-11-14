import { MarkerType, Position, type Edge, type Node } from '@vue-flow/core';

import { nodeKindClasses } from './nodeKinds';
import type { GraphDef, GraphLocale, GraphNodeDef } from './types';

const positions = { top: Position.Top, right: Position.Right, bottom: Position.Bottom, left: Position.Left } as const;

export interface GraphArticle {
  title: string;
  url: string;
}

export interface FlowOptions {
  /** The node the current article stands for. */
  highlight?: string | null;
  /** Articles linked to nodes, by node id. */
  articles?: Record<string, GraphArticle>;
}

function toNode(node: GraphNodeDef, locale: GraphLocale, options: FlowOptions): Node {
  const classes = nodeKindClasses[node.kind];
  const isGroup = node.kind === 'group';

  return {
    id: node.id,
    type: isGroup ? 'parent' : 'multiple-handle',
    position: (locale === 'en' && node.positionEn) || node.position,
    data: {
      label: node.label[locale],
      description: node.description?.[locale],
      url: node.url,
      handles: node.handles,
      nodeClass: classes.node,
      textClass: classes.text,
      // The article being read needs no link to itself.
      article: options.highlight === node.id ? undefined : options.articles?.[node.id],
      highlighted: options.highlight === node.id,
    },
    style: {
      lineHeight: '1.25',
      ...(node.width ? { width: `${node.width}px` } : {}),
      ...(node.height ? { height: `${node.height}px` } : {}),
    },
    ...(node.parentNode ? { parentNode: node.parentNode, extent: 'parent' as const } : {}),
    ...(isGroup ? {
      sourcePosition: positions[node.sourcePosition ?? 'bottom'],
      targetPosition: positions[node.targetPosition ?? 'top'],
      // Groups sit behind their children and are not dragged apart from them.
      zIndex: -1,
    } : {}),
  };
}

/** A graph definition as vue-flow nodes and edges, in one language. */
export function toFlow(graph: GraphDef, locale: GraphLocale, options: FlowOptions = {}): { nodes: Node[]; edges: Edge[] } {
  return {
    nodes: graph.nodes.map(node => toNode(node, locale, options)),
    edges: graph.edges.map(edge => ({
      id: edge.id,
      source: edge.source,
      target: edge.target,
      sourceHandle: edge.sourceHandle,
      targetHandle: edge.targetHandle,
      type: edge.type ?? 'smoothstep',
      label: edge.label?.[locale],
      markerEnd: { type: MarkerType.ArrowClosed },
      class: edge.variant === 'dashed' ? 'structure-edge--dashed' : undefined,
    })),
  };
}
