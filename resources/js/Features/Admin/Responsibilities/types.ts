export type ResponsibilityScopeValue = 'tenant' | 'institution_type' | 'institution';

export interface DutyResponsibilityItem {
  id: string;
  responsibility: string;
  label: string;
  scope_type: ResponsibilityScopeValue;
  scope_id: string;
  scope_name: string | null;
}

export interface DutyResponsibilitiesPayload {
  items: DutyResponsibilityItem[];
  roles: { id: string; name: string }[];
}

export interface DutyResponsibilityOptions {
  responsibilities: { value: string; label: string; description: string; scopes: ResponsibilityScopeValue[] }[];
  tenants: { id: number; shortname: string }[];
  types: { id: string; title: string }[];
  institutions: { id: string; name: string }[];
}
