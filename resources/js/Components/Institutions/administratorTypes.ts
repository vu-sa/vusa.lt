export interface AdministratorUser {
  id: string;
  name: string;
  email: string | null;
  profile_photo_path: string | null;
}

/** One term plus the people nominated to look after the institution during it. */
export interface AdministratorRoster {
  cadence_id: string;
  label: string;
  start_date: string;
  end_date: string;
  /** The term comes from the shared ladder rather than an override of this institution. */
  is_global: boolean;
  is_current: boolean;
  administrators: AdministratorUser[];
}
