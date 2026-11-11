import type { DocumentStatus, KnowledgeItemKind, KnowledgeSectionKind, LearningImportance, LearningAudienceType, LearningStage, LearningStepKind } from './enums';

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
  /** Not on public vusa.lt; `url` opens it through the knowledge item instead. */
  private?: boolean;
  thumbnailUrl?: string | null;
}

export interface LearningStepData {
  id: string;
  key: string | null;
  isSystem: boolean;
  kind: LearningStepKind;
  audienceLabel: string;
  audienceNotes: { label: string; description: string }[];
  stage: LearningStage | null;
  importance: LearningImportance;
  title: string;
  description: string;
  actionUrl: string | null;
  actionLabel: string | null;
  state: 'pending' | 'done' | 'skipped' | 'updated';
  autoCompleted: boolean;
  automaticallyTracked: boolean;
  completedAt: string | null;
  skippable: boolean;
  reviewedAt: string | null;
}

export interface LearningPathData {
  currentStage: LearningStage | null;
  termStart: string | null;
  stages: { stage: LearningStage; steps: LearningStepData[] }[];
  meetingPrep: { audience: { id: string; label: string }; steps: LearningStepData[] }[];
  knowledge: LearningDocumentTotals & { nextItem: KnowledgeItemData | null; priorityProgress: KnowledgePriorityProgress[] };
  totals: LearningTotals;
}

export interface KnowledgePriorityProgress {
  importance: LearningImportance;
  done: number;
  total: number;
}

export type LearningCuratorFlag = 'expired_document' | 'updated_document' | 'missing_source';

export interface LearningLibraryStep {
  id: string;
  title: string;
  kind: LearningStepKind;
  audiences: { id: string; type: LearningAudienceType; label: string; importance: LearningImportance; stage: LearningStage | null }[];
  audienceTypes: LearningAudienceType[];
  order: number;
  isActive: boolean;
  canUpdate: boolean;
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
  action_label?: { lt: string; en: string };
  action_url?: { lt: string; en: string };
  kind: LearningStepKind;
  audiences: LearningAudienceEditData[];
  order: number;
  is_active: boolean;
  is_system: boolean;
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

export interface LearningInstitutionContacts {
  id: string;
  title: string;
  contacts: { name: string; role: string; email: string | null }[];
}

export interface LearningAudienceEditData {
  id: string;
  type: LearningAudienceType;
  source_id: string | null;
  tenant_id: number | null;
  importance: LearningImportance;
  stage: LearningStage | null;
  description: { lt: string; en: string };
  order: number;
  label: string;
}

export interface LearningDocumentTotals {
  essentialDone: number;
  essentialTotal: number;
  done: number;
  total: number;
}

/** A Wiki section: a topic groups terms, a collection groups material (optionally dated, e.g. a training). */
export interface WikiSection {
  id: string;
  kind: KnowledgeSectionKind;
  title: string;
  description: string;
  date: string | null;
  order: number;
}

export interface LearningDocumentsData {
  sections: WikiSection[];
  items: KnowledgeItemData[];
  selfLearned: SelfLearnedItemData[];
  stats: { pathDone: number; pathTotal: number; selfLearned: number };
}

export type SelfLearnedItemData = Omit<KnowledgeItemData, 'importance'> & { importance: null };

export interface KnowledgeItemData {
  id: string;
  kind: KnowledgeItemKind;
  title: string;
  abbreviation: string;
  /** Plain text; `summaryHtml` keeps its bold marks. */
  summary: string;
  summaryHtml: string;
  description: string;
  audienceLabel: string;
  audienceNotes: { label: string; description: string }[];
  importance: LearningImportance;
  section: { id: string; title: string } | null;
  source: LearningSource | null;
  state: 'pending' | 'done' | 'skipped' | 'updated';
  autoCompleted: false;
  completedAt: string | null;
  skippable: boolean;
  insightsCount: number;
  reviewedAt: string | null;
}

export interface KnowledgeItemEditData {
  id: string;
  kind: KnowledgeItemKind;
  title: { lt: string; en: string };
  abbreviation: { lt: string; en: string };
  summary: { lt: string; en: string };
  description: { lt: string; en: string };
  knowledge_section_id: string | null;
  tenant_id: number | null;
  audiences: LearningAudienceEditData[];
  is_active: boolean;
  counts_in_progress: boolean;
  sources: Record<string, { document_id: number | null; url: string | null }>;
  source_documents: Record<string, LearningDocumentSearchHit>;
}

export interface KnowledgeLibraryItem extends Omit<LearningLibraryStep, 'kind'> {
  kind: KnowledgeItemKind;
  abbreviation: string;
  hasSource: boolean;
  section: { id: string; title: string } | null;
  flags: LearningCuratorFlag[];
}

/** A wiki article as a link target: popovers, related lists, search and the A–Ž index. */
export interface WikiPageSummary {
  id: string;
  kind: KnowledgeItemKind;
  title: string;
  abbreviation: string;
  summary: string;
  summaryHtml: string;
  url: string;
  /** The source document's first page, when there is one. */
  thumbnailUrl: string | null;
}

/** Material about a term, with the unit it belongs to; the viewer's own units come first. */
export interface WikiMaterial extends WikiPageSummary {
  tenant: string | null;
  mine: boolean;
}

export interface WikiIndexEntry extends WikiPageSummary {
  sectionId: string | null;
  /** Off for material only some readers need (one unit's regulations); it then counts only for those it is assigned to. */
  countsInProgress: boolean;
  updatedAt: string | null;
}

export type WikiSubjectType = 'institution' | 'institution_type' | 'duty_type' | 'knowledge_item';

export interface WikiSubject {
  id: string;
  type: WikiSubjectType;
  title: string;
  url: string | null;
}
