export {};

declare global {
  interface CalendarEventForm
    extends Pick<
      App.Entities.Calendar,
      | 'title'
      | 'date'
      | 'end_date'
      | 'description'
      | 'location'
      | 'organizer'
      | 'cto_url'
      | 'facebook_url'
      | 'is_draft'
      | 'is_all_day'
      | 'is_international'
      | 'tenant_id'
      | 'category_id'
      | 'tenant'
    > {
    id?: number;
    // Form-specific properties (not in base model)
    main_image?: File | string | null;
    main_image_url?: string | null;
    images?: Array<{
      id?: number;
      name?: string;
      url?: string;
      file?: File;
      status?: string;
    }>;
    youtube_url?: string | null;
  }

  interface InstitutionForm
    extends Pick<
      App.Entities.Institution,
      'name' | 'short_name' | 'alias' | 'description' | 'tenant_id'
    > {
    id?: number;
  }
}
