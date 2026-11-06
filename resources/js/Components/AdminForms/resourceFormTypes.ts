export type ResourceMediaEntry
  = | { id: string | number; name: string; type: string; status: 'finished'; url: string }
    | { file: File; status: 'pending' };

export type ResourceCreationTemplate = Omit<
  App.Entities.Resource,
  'created_at' | 'updated_at' | 'deleted_at' | 'id' | 'name' | 'description'
> & {
  id: undefined;
  created_at?: string;
  updated_at?: string;
  name: Record<'lt' | 'en', string>;
  description: Record<'lt' | 'en', string>;
  media: ResourceMediaEntry[];
};

export type ResourceEditType = Omit<
  App.Entities.Resource,
  'deleted_at' | 'name' | 'description'
> & {
  name: Record<'lt' | 'en', string>;
  description: Record<'lt' | 'en', string>;
  media: ResourceMediaEntry[];
};
