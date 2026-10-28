import type { Component } from 'vue';

export interface AdminRecordAction {
  key: string;
  label: string;
  /** One-word table label; falls back to label. */
  shortLabel?: string;
  icon?: Component;
  href?: string;
  external?: boolean;
  /** Native same-tab navigation for pages outside the Inertia app. */
  plain?: boolean;
  role?: 'open' | 'edit';
  primary?: boolean;
  destructive?: boolean;
  disabled?: boolean;
  loading?: boolean;
}
