export type ChecklistItemStatus = 'pending' | 'done' | 'skipped';

export interface ChecklistItem {
  key: string;
  title: string;
  description: string;
  actionLabel: string;
  confirmLabel: string;
  skipLabel: string | null;
  href: string | null;
  status: ChecklistItemStatus;
}

export interface OnboardingChecklistData {
  key: string;
  title: string;
  items: ChecklistItem[];
  resolved: number;
  total: number;
  complete: boolean;
  hidden: boolean;
  href: string;
  nextHref: string;
}

export type OnboardingProgress = Record<string, {
  hidden?: boolean;
  items?: Record<string, { status: ChecklistItemStatus; updatedAt?: string }>;
}>;

export type InstallationPlatform = 'ios' | 'android' | 'desktop';

export interface InstallationTutorial {
  src: string;
  poster?: string;
  captions: string;
  transcript: string;
}
