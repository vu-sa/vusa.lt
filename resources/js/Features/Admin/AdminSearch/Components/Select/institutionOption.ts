/** Minimal shape of the duty-assignable institutions passed by forms. */
export interface InstitutionOption {
  id: string;
  name: string;
  is_active?: boolean;
  alias?: string | null;
  tenant?: { id: number; shortname: string } | null;
}
