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
  completedAt: string | null;
  skippable: boolean;
  insightsCount: number;
  reviewedAt: string | null;
  glossaryTerms: GlossaryTermData[];
}

export interface LearningPathData {
  currentStage: LearningStage | null;
  termStart: string | null;
  stages: { stage: LearningStage; steps: LearningStepData[] }[];
  meetingPrep: { institutionType: { id: number; title: string }; steps: LearningStepData[] }[];
  totals: LearningTotals;
}

export interface LearningLibraryStep {
  id: string;
  title: string;
  kind: LearningStepKind;
  layer: LearningLayer;
  stage: LearningStage | null;
  importance: LearningImportance;
  isActive: boolean;
}

export interface LearningStepEditData {
  id: string;
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
}

export interface HandoverNoteData {
  id: string;
  dutyId: string;
  duty: string;
  author: { id: string; name: string };
  body: string;
  createdAt: string | null;
  updatedAt: string | null;
}

export interface WritableHandoverDuty {
  dutyId: string;
  title: string;
  note: HandoverNoteData | null;
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
