export {};

declare global {
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
    flows: Array<Pick<App.Models.SaziningaiExamFlow>, "start_time">;
    acceptGDPR: boolean;
    acceptDataManagement: boolean;
  }
}
