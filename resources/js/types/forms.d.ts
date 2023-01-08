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
      | "extra_attributes"
      | "location"
    > {
    id?: number;
  }

  interface InstitutionForm
    extends Pick<
      App.Entities.Institution,
      "name" | "short_name" | "alias" | "description" | "padalinys_id"
    > {
    id?: number;
  }

  interface SaziningaiExamForm
    extends Omit<
      App.Entities.SaziningaiExam,
      | "id"
      | "uuid"
      | "created_at"
      | "updated_at"
      | "exam"
      | "observers"
      | "observers_count"
    > {
    flows: Array<Pick<App.Entities.SaziningaiExamFlow, "start_time">>;
    acceptGDPR: boolean;
    acceptDataManagement: boolean;
  }
}
