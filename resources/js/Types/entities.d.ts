declare namespace App.Entities {
  export type AgendaItem = App.Models.Pivots.AgendaItem;
  export type Banner = App.Models.Banner;
  export type Calendar = App.Models.Calendar;
  export type Category = App.Models.Category;
  export type ChangelogItem = App.Models.ChangelogItem;
  export type Comment = App.Models.Comment;
  export interface Contact extends App.Models.Contact {
    comments: Array<App.Models.Comment>;
  }
  export interface Doing extends Omit<App.Models.Doing, "state"> {
    state:
      | "draft"
      | "pending_changes"
      | "pending_padalinys_approval"
      | "pending_final_approval"
      | "approved"
      | "pending_completion"
      | "completed"
      | "cancelled";
  }
  export type Dutiable = App.Models.Pivots.Dutiable;

  export interface Duty extends App.Models.Duty {
    roles?: Array<App.Models.Role> | null;
    roles_count?: number | null;
  }

  export type Goal = App.Models.Goal;
  export type GoalGroup = App.Models.GoalGroup;
  export type GoalMatter = App.Models.Pivots.GoalMatter;
  export type Institution = App.Models.Institution;
  export type MainPage = App.Models.MainPage;
  export type Matter = App.Models.Matter;
  export interface Media extends App.Models.Media {
    original_url?: string;
  }

  export type Meeting = App.Models.Meeting;

  export type Navigation = App.Models.Navigation;
  export type News = App.Models.News;

  export interface Notification<T> {
    created_at: string;
    data: T;
    id: string;
    notifiable_id: string;
    notifiable_type: string;
    read_at: string | null;
    type: string;
    updated_at: string;
  }

  export type Padalinys = App.Models.Padalinys;
  export type Page = App.Models.Page;
  export type Permission = App.Models.Permission;
  export type Registration = App.Models.Registration;
  export type RegistrationForm = App.Models.RegistrationForm;
  export type Relationship = App.Models.Relationship;
  export type Relationshipable = App.Models.Pivots.Relationshipable;
  export interface Reservation
    extends Omit<App.Models.Reservation, "resources"> {
    comments?: Array<App.Models.Comment> | null;
    resources?: Array<App.Entities.Resource> | null;
  }
  export interface Resource extends Omit<App.Models.Resource, "is_reservable"> {
    is_reservable: 0 | 1;
    pivot?: App.Entities.ReservationResource | null;
    media?: App.Entities.Media[] | null;
  }
  export interface ReservationResource
    extends Omit<App.Models.Pivots.ReservationResource, "state"> {
    state:
      | "created"
      | "reserved"
      | "lent"
      | "returned"
      | "updated"
      | "rejected"
      | "cancelled";
    comments?: Array<App.Models.Comment> | [];
  }
  export type Role = App.Models.Role;
  export type SaziningaiExam = App.Models.SaziningaiExam;
  export type SaziningaiExamFlow = App.Models.SaziningaiExamFlow;
  export type SaziningaiExamObserver = App.Models.SaziningaiExamObserver;
  export type SharepointFile = App.Models.SharepointFile;
  export type SharepointFileable = App.Models.Pivots.SharepointFileable;
  export type Tag = App.Models.Tag;
  export type Task = App.Models.Task;
  export type Type = App.Models.Type;

  export interface User extends Omit<App.Models.User, "padaliniai"> {
    padaliniai?: Array<App.Models.Padalinys> | null;
    roles?: Array<App.Models.Role> | null;
    roles_count?: number | null;
    pivot?: App.Models.Pivots.Dutiable | null;
  }
}
