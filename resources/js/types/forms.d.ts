export {};

declare global {
  interface CalendarEventForm
    extends Pick<
      App.Models.Calendar,
      | "title"
      | "date"
      | "description"
      | "category"
      | "url"
      | "attributes"
      | "location"
    > {
    id?: number;
  }

  interface DutyInstitutionForm
    extends Pick<
      App.Models.DutyInstitution,
      "name" | "short_name" | "alias" | "description" | "padalinys_id"
    > {
    id?: number;
  }

  interface SaziningaiExamForm
    extends Omit<
      App.Models.SaziningaiExam,
      | "id"
      | "uuid"
      | "created_at"
      | "updated_at"
      | "exam"
      | "observers"
      | "observers_count"
    > {
    flows: Array<Pick<App.Models.SaziningaiExamFlow, "start_time">>;
    acceptGDPR: boolean;
    acceptDataManagement: boolean;
  }
}
