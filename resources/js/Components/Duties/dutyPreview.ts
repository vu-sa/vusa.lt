import { trans as $t } from 'laravel-vue-i18n';

import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import type { DutySearchResult } from '@/Shared/Search/types';

interface Member {
  id?: string;
  name: string;
}

/** A duty as its preview shows it, whether it came from search or from the database. */
export interface DutyPreviewModel {
  id: string;
  name: string;
  email?: string | null;
  tenantShortname?: string;
  institution: { id?: string; name: string; isActive?: boolean } | null;
  types: string[];
  memberCount: number;
  /** Search documents carry member names; database rows carry only the count. */
  currentMembers?: Member[];
  previousMembers?: Member[];
}

type Translation = { lt?: string; en?: string } | string | null | undefined;

/** The fields of a database duty row the preview reads (`toFullArray()` shape). */
export interface DutyPreviewRecord {
  id: string | number;
  name: Translation;
  email?: string | null;
  dutiables_count?: number;
  institution?: { id: string | number; name?: Translation; short_name?: Translation; is_active?: boolean } | null;
  types?: { title: Translation }[];
}

export function dutyPreviewFromSearch(duty: DutySearchResult): DutyPreviewModel {
  const institutionName = getTranslatedValue({ lt: duty.institution_name_lt, en: duty.institution_name_en });
  const members = (names: string[] | undefined, ids: string[] | undefined): Member[] =>
    (names ?? []).map((name, index) => ({ name, id: ids?.[index] }));

  return {
    id: duty.id,
    name: getTranslatedValue({ lt: duty.name_lt, en: duty.name_en }, undefined, $t('Be pavadinimo')),
    email: duty.email,
    tenantShortname: duty.tenant_shortname,
    institution: institutionName ? { id: duty.institution_id, name: institutionName, isActive: duty.institution_is_active } : null,
    types: duty.type_titles ?? [],
    memberCount: duty.current_users_count ?? duty.current_user_names?.length ?? 0,
    currentMembers: members(duty.current_user_names, duty.current_user_ids),
    previousMembers: members(duty.previous_user_names, duty.previous_user_ids),
  };
}

export function dutyPreviewFromRecord(duty: DutyPreviewRecord): DutyPreviewModel {
  const { institution } = duty;

  return {
    id: String(duty.id),
    name: getTranslatedValue(duty.name, undefined, $t('Be pavadinimo')),
    email: duty.email,
    institution: institution
      ? { id: String(institution.id), name: getTranslatedValue(institution.short_name || institution.name, undefined, '—'), isActive: institution.is_active }
      : null,
    types: (duty.types ?? []).map(type => getTranslatedValue(type.title)).filter(Boolean),
    memberCount: duty.dutiables_count ?? 0,
  };
}
