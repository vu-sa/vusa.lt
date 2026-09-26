import { describe, expect, it } from 'vitest';

import { adminWorkspaceIcons, workspaceIcon } from '@/Constants/adminWorkspaces';

/** The keys AdminNavigationCatalog emits; a super admin sees all of them (AdminNavigationCatalogTest). */
const catalogWorkspaceKeys = ['pradzia', 'atstovavimas', 'rezervacijos', 'svetaine', 'organizacija', 'sistema'];

describe('adminWorkspaces', () => {
  it('has an icon for every workspace the catalog emits', () => {
    expect(Object.keys(adminWorkspaceIcons).sort()).toEqual([...catalogWorkspaceKeys].sort());
  });

  it('falls back to a usable icon for an unknown workspace', () => {
    expect(workspaceIcon('not-a-workspace')).toBe(adminWorkspaceIcons.pradzia);
  });
});
