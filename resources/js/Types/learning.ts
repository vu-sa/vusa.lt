import type { DocumentStatus, LearningImportance, LearningLayer, LearningStage, LearningStepKind } from './enums';

export interface LearningTotals {
  essentialDone: number;
  essentialTotal: number;
  allDone: number;
  allTotal: number;
}

export interface LearningSource {
  type: 'document' | 'url';
  url: string | null;
  documentId: number | null;
  documentStatus: DocumentStatus | null;
  title: string | null;
}

export interface LearningStepData {
  id: string;
  key: string | null;
  isSystem: boolean;
  kind: LearningStepKind;
  layer: LearningLayer;
  layerLabel: string;
  stage: LearningStage | null;
  importance: LearningImportance;
  title: string;
  description: string;
  source: LearningSource | null;
  actionUrl: string | null;
  state: 'pending' | 'done' | 'skipped' | 'updated';
  autoCompleted: boolean;
  automaticallyTracked: boolean;
  completedAt: string | null;
  skippable: boolean;
  insightsCount: number;
  reviewedAt: string | null;
  glossaryTerms: Pick<GlossaryTermData, 'id' | 'term' | 'definition'>[];
}

export interface LearningPathData {
  currentStage: LearningStage | null;
  termStart: string | null;
  stages: { stage: LearningStage; steps: LearningStepData[] }[];
  meetingPrep: { institutionType: { id: number; title: string }; steps: LearningStepData[] }[];
  totals: LearningTotals;
}

export type LearningCuratorFlag = 'expired_document' | 'updated_document' | 'missing_source';

export interface LearningLibraryStep {
  id: string;
  title: string;
  kind: LearningStepKind;
  layer: LearningLayer;
  layerLabel: string;
  stage: LearningStage | null;
  importance: LearningImportance;
  isActive: boolean;
  canUpdate: boolean;
  flags: LearningCuratorFlag[];
}

export interface LearningDocumentSearchHit {
  id: number;
  title: string;
  status: DocumentStatus;
  date: string | null;
}

export interface LearningTypeOption {
  id: number;
  title: string;
  parent_id: number | null;
}

export interface LearningTenantOption {
  id: number;
  shortname: string;
  fullname?: string;
  type?: string | null;
}

export interface LearningStepEditData {
  id: string;
  key?: string | null;
  title: { lt: string; en: string };
  description: { lt: string; en: string };
  kind: LearningStepKind;
  layer: LearningLayer;
  stage: LearningStage | null;
  importance: LearningImportance;
  institution_type_id: number | null;
  duty_type_id: number | null;
  tenant_id: number | null;
  per_term: boolean;
  order: number;
  is_active: boolean;
  is_system: boolean;
  sources: Record<string, { document_id: number | null; url: string | null }>;
  source_documents?: Record<string, LearningDocumentSearchHit>;
}

export interface LearningCoordinator {
  id: string;
  name: string;
  email: string | null;
  profile_photo_path: string | null;
  duty: string | null;
  pronouns: Record<string, string>;
  use_original_duty_name: boolean;
  institutions: string[];
}

export interface GlossaryTermData {
  id: string;
  term: string;
  definition: string;
  stepsCount: number;
  steps: { id: string; title: string; url: string | null; isDocument: boolean }[];
  translations: {
    term: { lt: string; en: string };
    definition: { lt: string; en: string };
  };
}

export const handoverFields = ['situation', 'open_issues', 'people', 'resources', 'advice'] as const;
export type HandoverField = typeof handoverFields[number];

export interface HandoverNoteData {
  id: string;
  dutyId: string;
  duty: string;
  author: { id: string; name: string };
  seatDutyId: string;
  seatTitle: string;
  cadenceId: string | null;
  cadenceLabel: string;
  termStart: string | null;
  situation: string | null;
  open_issues: string | null;
  people: string | null;
  resources: string | null;
  advice: string | null;
  createdAt: string | null;
  updatedAt: string | null;
}

export interface WritableHandoverDuty {
  dutyId: string;
  title: string;
  cadenceId: string | null;
  cadenceLabel: string;
  termStart: string;
  author: { id: string; name: string };
  note: HandoverNoteData | null;
  notes: HandoverNoteData[];
}

export interface LearningRepresentative {
  id: string;
  name: string;
  incoming: boolean;
  lastActivity: string | null;
  institutions: { id: string; title: string }[];
  totals: LearningTotals;
}

export interface LearningRepsSummary extends LearningTotals {
  representatives: number;
  essentialsFinished: number;
  notStarted: number;
  averageEssentialProgress: number;
}

export interface LearningInstitutionContacts {
  id: string;
  title: string;
  contacts: { name: string; role: string; email: string | null }[];
}
