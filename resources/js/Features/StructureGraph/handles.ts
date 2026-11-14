import { Position } from '@vue-flow/core';
import type { Component, CSSProperties } from 'vue';

import type { HandleId } from './types';

export interface StructureNodeData {
  label: string | Component;
  description?: string;
  url?: string;
  handles?: HandleId[];
  nodeClass?: string;
  textClass?: string | string[];
  /** The wiki article this node stands for. */
  article?: { title: string; url: string };
  /** This node is the article being read. */
  highlighted?: boolean;
}

const handleDefs: Record<HandleId, { position: Position; style?: CSSProperties }> = {
  'left': { position: Position.Left },
  'left-top': { position: Position.Left, style: { top: '25%' } },
  'left-bottom': { position: Position.Left, style: { top: '75%' } },
  'top': { position: Position.Top },
  'top-left': { position: Position.Top, style: { left: '10%' } },
  'top-right': { position: Position.Top, style: { left: '90%' } },
  'right': { position: Position.Right },
  'right-top': { position: Position.Right, style: { top: '25%' } },
  'right-bottom': { position: Position.Right, style: { top: '75%' } },
  'bottom': { position: Position.Bottom },
  'bottom-left': { position: Position.Bottom, style: { left: '10%' } },
  'bottom-right': { position: Position.Bottom, style: { left: '90%' } },
};

/** A node's handles; none listed means all twelve. */
export function handlesFor(ids: HandleId[] | undefined): { id: HandleId; position: Position; style?: CSSProperties }[] {
  return (ids ?? (Object.keys(handleDefs) as HandleId[])).map(id => ({ id, ...handleDefs[id] }));
}
