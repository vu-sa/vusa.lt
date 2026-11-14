export interface Localized {
  lt: string;
  en: string;
}

export type GraphLocale = keyof Localized;

/** What a body is; its colour comes only from this (`nodeKinds.ts`), never from the data. */
export type NodeKind = 'assembly' | 'leader' | 'council' | 'body' | 'group';

export type HandleId =
  | 'left' | 'left-top' | 'left-bottom'
  | 'top' | 'top-left' | 'top-right'
  | 'right' | 'right-top' | 'right-bottom'
  | 'bottom' | 'bottom-left' | 'bottom-right';

export interface Point {
  x: number;
  y: number;
}

export interface GraphNodeDef {
  id: string;
  kind: NodeKind;
  label: Localized;
  description?: Localized;
  /** Absolute https:// address only. */
  url?: string;
  /** Omitted: every handle; a group without them gets one source and one target handle. */
  handles?: HandleId[];
  position: Point;
  /** Only where English needs a different spot; prefer a wider node. */
  positionEn?: Point;
  width?: number;
  /** Groups only. */
  height?: number;
  /** A `group` node this one sits inside; its position is then relative to the group. */
  parentNode?: string;
  /** Groups only: where edges leave and enter. */
  sourcePosition?: 'top' | 'right' | 'bottom' | 'left';
  targetPosition?: 'top' | 'right' | 'bottom' | 'left';
}

export interface GraphEdgeDef {
  id: string;
  source: string;
  target: string;
  sourceHandle?: HandleId;
  targetHandle?: HandleId;
  label?: Localized;
  /** `dashed` marks a weaker tie (advises, proposes) where atstovavimas used orange. */
  variant?: 'solid' | 'dashed';
  type?: 'smoothstep' | 'step' | 'default';
}

export interface GraphDef {
  nodes: GraphNodeDef[];
  edges: GraphEdgeDef[];
}
