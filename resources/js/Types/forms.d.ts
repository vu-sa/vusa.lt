export {};

declare global {
  interface CalendarEventForm
    extends Pick<
      App.Entities.Calendar,
      | "title"
      | "date"
      | "description"
      | "category"
      | "url"
      | "location"
    > {
    id?: number;
  }

  interface InstitutionForm
    extends Pick<
      App.Entities.Institution,
      "name" | "short_name" | "alias" | "description" | "tenant_id"
    > {
    id?: number;
  }
}
