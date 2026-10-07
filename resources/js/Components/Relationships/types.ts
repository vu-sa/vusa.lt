export type RelationshipSubject = 'institution' | 'type';

export interface RelationKindOption {
  value: string;
  label: string;
}

/** One link as seen from the record it is shown on. */
export interface RelationshipLinkRow {
  id: number;
  other: { id: string; name: string };
  direction: 'outgoing' | 'incoming';
  kind: string;
  kind_label: string;
  mutual: boolean;
  cross_tenant?: boolean;
}

export interface RelationshipTypeOption {
  id: string;
  name: string;
}
