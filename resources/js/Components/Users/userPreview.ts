import { getActiveLanguage } from 'laravel-vue-i18n';

import { getUserDuties } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import type { UserSearchResult } from '@/Shared/Search/types';

interface DutyLink {
  id?: string;
  name: string;
}

/** A member as their preview shows them, whether they came from search or from the database. */
export interface UserPreviewModel {
  id: string;
  name: string;
  email?: string | null;
  phone?: string | null;
  tenantShortname?: string;
  isDeleted?: boolean;
  /** Search documents carry duty names; the members list shows its own term summary instead. */
  currentDuties?: DutyLink[];
  previousDuties?: DutyLink[];
}

export function userPreviewFromSearch(user: UserSearchResult, locale = getActiveLanguage()): UserPreviewModel {
  return {
    id: user.id,
    name: user.name,
    email: user.email,
    phone: user.phone,
    tenantShortname: user.tenant_shortname,
    isDeleted: user.is_active === false,
    currentDuties: getUserDuties(user, 'current', locale),
    previousDuties: getUserDuties(user, 'previous', locale),
  };
}

export function userPreviewFromRecord(user: { id: string | number; name: string; email?: string | null; phone?: string | null }, isDeleted = false): UserPreviewModel {
  return { id: String(user.id), name: user.name, email: user.email, phone: user.phone, isDeleted };
}
