export type ResponsibilityScopeValue = 'tenant' | 'institution_type' | 'institution';
export type GranteeType = 'duty' | 'duty_type' | 'institution';

/** One responsibility a duty holds, given to it directly or through its type or institution. */
export interface DutyResponsibilityItem {
  id: string;
  role_id: string;
  title: string;
  purpose: string | null;
  purpose_label: string | null;
  source: GranteeType;
  /** The type or institution it comes through; null when given to the duty itself. */
  source_name: string | null;
  scope_type: ResponsibilityScopeValue | null;
  scope_name: string | null;
  missing_permissions: string[];
}

export interface DutyResponsibilitiesPayload {
  items: DutyResponsibilityItem[];
}

export interface DutyResponsibilityOptions {
  responsibilities: { id: string; title: string; description: string | null; purpose_label: string | null; scopes: ResponsibilityScopeValue[] }[];
  tenants: { id: number; shortname: string }[];
  types: { id: string; title: string; parent_id: string | null }[];
  institutions: { id: string; name: string }[];
}

export interface ResponsibilityGrantsPayload {
  duties: {
    id: string;
    duty: { id: string; name: string };
    institution: { id: string; name: string } | null;
    tenant: string | null;
    scope_type: ResponsibilityScopeValue | null;
    scope_name: string | null;
    holders_count: number;
    missing_permissions: string[];
  }[];
  dutyTypes: {
    id: string;
    type: { id: number; title: string };
    scope_type: ResponsibilityScopeValue | null;
    scope_name: string | null;
    duties_count: number;
    needs_scope?: boolean;
  }[];
  institutions: { id: string; institution: { id: string; name: string }; scope_type: ResponsibilityScopeValue | null; scope_name: string | null; duties_count: number }[];
}

export interface ResponsibilityGrantOptions {
  institutions: { id: string; name: string }[];
  tenants: { id: string; shortname: string }[];
  institutionTypes: { id: string; title: string; parent_id: string | null }[];
}

export interface ResponsibilityPerson {
  id: string;
  name: string;
  profile_photo_path: string | null;
  duties: { id: string; name: string; institution: string }[];
}
