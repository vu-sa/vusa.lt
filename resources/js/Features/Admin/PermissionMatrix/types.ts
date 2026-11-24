export type PermissionAbility = 'create' | 'read' | 'update' | 'delete' | 'forceDelete';
export type PermissionScope = 'own' | 'padalinys' | '*';

/** One row of the permission catalog, as `PermissionMatrixPresenter::toFrontend()` sends it. */
export interface CatalogResource {
  key: string;
  /** ModelEnum value; null for capabilities that are not a model (`settings`). */
  model: string | null;
  area: string;
  abilities: Partial<Record<PermissionAbility, PermissionScope[]>>;
  /** Access available here without a responsibility. */
  baseline: string | null;
  baselineLabels: Partial<Record<PermissionAbility, string>> | null;
  baselineSegments?: { text: string; strong: boolean }[];
  scopeLabels?: Partial<Record<PermissionScope, string>>;
  scopeDescriptions?: Partial<Record<PermissionAbility, Partial<Record<PermissionScope, string>>>>;
  /** Whether deleting moves to a trash; without one it is permanent at once. */
  softDeletes: boolean;
}

export interface PermissionCatalog {
  areas: string[];
  resources: CatalogResource[];
  /** Permission names replaced by baseline access. */
  retired: string[];
}
