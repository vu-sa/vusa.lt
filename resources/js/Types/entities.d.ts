declare namespace App.Entities {
  export type AgendaItem = models.AgendaItem;
  export type Banner = models.Banner;
  export type Calendar = models.Calendar;
  export type Category = models.Category;
  export type Comment = models.Comment;
  export type Content = models.Content;

  export type Document = Omit<models.Document, 'sharepoint_id' | 'eTag' | 'public_url_created_at' | 'sharepoint_site_id' | 'sharepoint_list_id' | 'created_at' | 'updated_at'>;

  export type Dutiable = models.Dutiable;

  export type Duty = models.Duty & {
    roles?: Array<models.Role> | null;
    roles_count?: number | null;
  };

  export type FieldResponse = models.FieldResponse;

  export type Form = models.Form;
  export type FormField = models.FormField;

  export type Institution = models.Institution;
  // export interface Media extends models.Media {
  //   original_url?: string;
  // }

  export type Meeting = models.Meeting;
  export type Membership = models.Membership;

  export type Navigation = models.Navigation;
  export type News = models.News;

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

  export type QuickLink = models.QuickLink;
  export type Page = models.Page;
  export type Permission = models.Permission;
  export type Programme = models.Programme;
  export type ProgrammeDay = models.ProgrammeDay;
  export type ProgrammeBlock = models.ProgrammeBlock;
  export type ProgrammePart = models.ProgrammePart;
  export type ProgrammeSection = models.ProgrammeSection;
  export type Registration = models.Registration;
  export type Relationship = models.Relationship;
  export type Relationshipable = models.Relationshipable;
  export type Reservation
    = Omit<models.Reservation, 'resources' | 'users'> & {
      comments?: Array<models.Comment> | null;
      resources?: Array<App.Entities.Resource> | null;
      users?: Array<App.Entities.User> | null;
    };
  export type Resource = Omit<models.Resource, 'is_reservable'> & {
    is_reservable: 0 | 1;
    pivot?: App.Entities.ReservationResource | null;
    media?: App.Entities.Media[] | null;
  };
  export type ResourceCategory = models.ResourceCategory;
  export type ReservationResource
    = Omit<models.ReservationResource, 'state'> & {
      state:
        | 'created'
        | 'reserved'
        | 'lent'
        | 'returned'
        | 'rejected'
        | 'cancelled';
      comments?: Array<models.Comment> | [];
    };
  export type FileableFile = models.FileableFile;
  export type Role = models.Role;
  export type SharepointFile = models.SharepointFile;
  export type SharepointFileable = models.SharepointFileable;
  export type StudyProgram = models.StudyProgram;
  export type Tag = models.Tag;
  export type Task = models.Task;
  export type Tenant = models.Tenant;
  export type Training = models.Training;
  export type Type = models.Type;
  export type Vote = models.Vote;

  export type User
    = Omit<models.User, 'tenants' | 'reservations'> & {
      tenants?: Array<models.Tenant> | null;
      reservations?: Array<App.Entities.Reservation> | null;
      roles?: Array<models.Role> | null;
      roles_count?: number | null;
      pivot?: models.Dutiable | null;
    };
}
