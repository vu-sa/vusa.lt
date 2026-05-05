export interface TenantShortnameData {
  shortname: string;
  shortname_vu?: string | null;
}

export const formatVuFacultyShortname = (tenant: TenantShortnameData): string => {
  const vuShortname = tenant.shortname_vu?.trim();

  if (vuShortname) {
    return vuShortname.startsWith("VU ") ? vuShortname : `VU ${vuShortname}`;
  }

  return tenant.shortname.replace(/^VU SA\s+/, "VU ");
};
