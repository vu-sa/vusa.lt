export type PermissionAbility = 'create' | 'read' | 'update' | 'delete' | 'forceDelete';
export type PermissionScope = 'own' | 'padalinys' | '*';

/** One row of the permission catalog, as `PermissionCatalog::toFrontend()` sends it. */
export interface CatalogResource {
  key: string;
  /** ModelEnum value; null for capabilities that are not a model (`settings`). */
  model: string | null;
  area: string;
  abilities: Partial<Record<PermissionAbility, PermissionScope[]>>;
  /** What every member may already do here without any responsibility. */
  baseline: string | null;
  /** Whether deleting moves to a trash; without one it is permanent at once. */
  softDeletes: boolean;
}

export interface PermissionCatalog {
  areas: string[];
  resources: CatalogResource[];
  /** Names every member already holds, so no responsibility may. */
  retired: string[];
}
