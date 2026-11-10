import type { LearningAudienceType } from '@/Types/enums';
import type { LearningAudienceEditData, LearningTenantOption, LearningTypeOption } from '@/Types/learning';

export type LearningAudienceDraft = Omit<LearningAudienceEditData, 'id'> & { id?: string };

export interface LearningSectionOption {
  id: string;
  title: { lt?: string; en?: string };
  description: { lt?: string; en?: string };
  order: number;
  tenant_id: number | null;
  canUpdate?: boolean;
  canDelete?: boolean;
  tenant: { id: number; shortname: string } | null;
}

export interface LearningAudienceOptions {
  audienceTypes: LearningAudienceType[];
  canTargetEverywhere: boolean;
  tenants: LearningTenantOption[];
  institutionTypes: LearningTypeOption[];
  dutyTypes: LearningTypeOption[];
  responsibilities: { id: string; title: string }[];
}

export interface LearningSectionScopes {
  create: { canTargetEverywhere: boolean; tenants: LearningTenantOption[] };
  update: { canTargetEverywhere: boolean; tenants: LearningTenantOption[] };
}
