export interface AssignableTenantRow {
  tenant_id: number;
  quota: number | null;
}

export interface FormerNameRow {
  name: { lt: string; en: string };
  used_until: string;
  /** Added because the name was just changed; follows the rename rather than being edited. */
  auto?: boolean;
}

/** A delegated admin sees only their own unit's seat; whoever edits the duty sees every unit. */
export function visibleAssignableTenantRows(rows: AssignableTenantRow[], canEditDuty: boolean, actingTenantIds: number[]): AssignableTenantRow[] {
  if (canEditDuty) return rows;
  const acting = new Set(actingTenantIds);
  return rows.filter(row => acting.has(row.tenant_id));
}
