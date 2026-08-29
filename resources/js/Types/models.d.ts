export {}
declare global {
  export namespace models {

    export interface Activity {
      // columns
      id: number
      log_name?: string | null
      description: string
      subject_type?: string | null
      event?: string | null
      subject_id?: string | null
      root_subject_type?: string | null
      root_subject_id?: string | null
      causer_type?: string | null
      causer_id?: string | null
      attribute_changes?: Record<string, unknown> | null
      properties?: Record<string, unknown> | null
      created_at?: string | null
      updated_at?: string | null
      // relations
      root_subject?: Activity
      subject?: Activity
      causer?: Activity
      // counts
      // exists
    }

    export interface AgendaItem {
      // columns
      id: string
      meeting_id: string
      matter_id?: string | null
      created_at: string
      updated_at: string
      title: string
      order: number
      brought_by_students: boolean
      type?: AgendaItemType | null
      student_position?: string | null
      description?: string | null
      start_time?: string | null
      end_time?: string | null
      // relations
      meeting?: Meeting
      votes?: Vote[]
      main_vote?: Vote
      additional_votes?: Vote[]
      note?: AgendaItemNote
      comments?: Comment[]
      root_comments?: Comment[]
      activities_as_subject?: Activity[]
      // counts
      votes_count: number
      additional_votes_count: number
      comments_count: number
      root_comments_count: number
      activities_as_subject_count: number
      // exists
      meeting_exists: boolean
      votes_exists: boolean
      main_vote_exists: boolean
      additional_votes_exists: boolean
      note_exists: boolean
      comments_exists: boolean
      root_comments_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface AgendaItemNote {
      // columns
      id: string
      agenda_item_id: string
      yjs_state?: string | null
      notes_html?: string | null
      updated_by?: string | null
      created_at?: string | null
      updated_at?: string | null
      // relations
      agenda_item?: AgendaItem
      editor?: User
      activities_as_subject?: Activity[]
      // counts
      activities_as_subject_count: number
      // exists
      agenda_item_exists: boolean
      editor_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface Approval {
      // columns
      id: string
      approvable_type: string
      approvable_id: string
      user_id: string
      decision: ApprovalDecision
      step: number
      notes?: string | null
      created_at?: string | null
      updated_at?: string | null
      // relations
      approvable?: Approval
      user?: User
      activities_as_subject?: Activity[]
      // counts
      activities_as_subject_count: number
      // exists
      user_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface ApprovalFlow {
      // columns
      id: string
      name: string
      flowable_type?: string | null
      flowable_id?: string | null
      steps: Array<unknown>
      is_sequential: boolean
      escalation_days?: number | null
      created_at?: string | null
      updated_at?: string | null
      // mutators
      total_steps: number
      // relations
      flowable?: ApprovalFlow
      // counts
      // exists
    }

    export interface Banner {
      // columns
      id: number
      title: string
      image_url: string
      link_url: string
      lang: string
      order: number
      is_active: number
      tenant_id: number
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // relations
      tenant?: Tenant
      activities_as_subject?: Activity[]
      // counts
      activities_as_subject_count: number
      // exists
      tenant_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface Cadence {
      // columns
      id: string
      institution_id?: string | null
      start_meeting_id?: string | null
      end_meeting_id?: string | null
      start_date: string
      end_date: string
      created_at?: string | null
      updated_at?: string | null
      // mutators
      label: string
      // relations
      institution?: Institution
      start_meeting?: Meeting
      end_meeting?: Meeting
      // counts
      // exists
      institution_exists: boolean
      start_meeting_exists: boolean
      end_meeting_exists: boolean
    }

    export interface Calendar {
      // columns
      id: number
      title?: Array<unknown> | null
      description?: Array<unknown> | null
      location?: Array<unknown> | null
      is_remote: boolean
      organizer?: Array<unknown> | null
      cto_url?: Array<unknown> | null
      facebook_url?: string | null
      video_url?: string | null
      main_image?: string | null
      is_draft: boolean
      is_all_day: boolean
      is_international: boolean
      hero_style: CalendarHeroStyleEnum
      date: string
      end_date?: string | null
      category_id?: number | null
      tenant_id: number
      meeting_id?: string | null
      created_at: string
      updated_at: string
      registration_form_id?: number | null
      deleted_at?: string | null
      // mutators
      main_image_url: string
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      tenant?: Tenant
      meeting?: Meeting
      category?: Category
      media?: Media[]
      activities_as_subject?: Activity[]
      // counts
      media_count: number
      activities_as_subject_count: number
      // exists
      tenant_exists: boolean
      meeting_exists: boolean
      category_exists: boolean
      media_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface Category {
      // columns
      id: number
      alias?: string | null
      created_at: string
      updated_at: string
      name?: Array<unknown> | null
      description?: Array<unknown> | null
      deleted_at?: string | null
      // mutators
      force_delete_blocked_reason: string
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      pages?: Page[]
      news?: News[]
      calendars?: Calendar[]
      // counts
      pages_count: number
      news_count: number
      calendars_count: number
      // exists
      pages_exists: boolean
      news_exists: boolean
      calendars_exists: boolean
    }

    export interface Comment {
      // columns
      id: string
      parent_id?: string | null
      thread_root_id?: string | null
      commentable_type: string
      commentable_id: string
      user_id: string
      kind: CommentKind
      body: string
      metadata?: Array<unknown> | null
      mentioned_user_ids?: Array<unknown> | null
      resolved_at?: string | null
      resolved_by?: string | null
      edited_at?: string | null
      created_at?: string | null
      updated_at?: string | null
      deleted_at?: string | null
      // relations
      commentable?: Comment
      user?: User
      parent?: Comment
      replies?: Comment[]
      thread_root?: Comment
      reactions?: CommentReaction[]
      poll_votes?: CommentPollVote[]
      resolver?: User
      // counts
      replies_count: number
      reactions_count: number
      poll_votes_count: number
      // exists
      user_exists: boolean
      parent_exists: boolean
      replies_exists: boolean
      thread_root_exists: boolean
      reactions_exists: boolean
      poll_votes_exists: boolean
      resolver_exists: boolean
    }

    export interface CommentPollVote {
      // columns
      id: string
      comment_id: string
      user_id: string
      option_id: string
      created_at?: string | null
      updated_at?: string | null
      // relations
      comment?: Comment
      user?: User
      // counts
      // exists
      comment_exists: boolean
      user_exists: boolean
    }

    export interface CommentReaction {
      // columns
      id: string
      comment_id: string
      user_id: string
      emoji: string
      created_at?: string | null
      updated_at?: string | null
      // relations
      comment?: Comment
      user?: User
      // counts
      // exists
      comment_exists: boolean
      user_exists: boolean
    }

    export interface Content {
      // columns
      id: number
      created_at: string
      updated_at: string
      // relations
      parts?: ContentPart[]
      news?: News
      page?: Page
      tenant?: Tenant
      // counts
      parts_count: number
      // exists
      parts_exists: boolean
      news_exists: boolean
      page_exists: boolean
      tenant_exists: boolean
    }

    export interface ContentPart {
      // columns
      id: number
      content_id: number
      type: string
      json_content: Record<string, unknown>
      options?: Record<string, unknown> | null
      order: number
      created_at: string
      updated_at: string
      // mutators
      content_summary: string
      html: string
      // relations
      content?: Content
      text_box_submissions?: TextBoxSubmission[]
      activities_as_subject?: Activity[]
      // counts
      text_box_submissions_count: number
      activities_as_subject_count: number
      // exists
      content_exists: boolean
      text_box_submissions_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface Document {
      // columns
      id: number
      name: string
      title: string
      sharepoint_id?: string
      e_tag?: string | null
      document_date?: string | null
      institution_id?: string | null
      meeting_id?: string | null
      content_type?: string | null
      language?: string | null
      summary?: string | null
      anonymous_url?: string | null
      link_url?: string | null
      sharepoint_permission_id?: string | null
      is_active: boolean
      sharepoint_site_id?: string
      sharepoint_list_id?: string
      created_at?: string
      checked_at?: string | null
      sync_status: string
      sync_error_message?: string | null
      sync_attempts: boolean
      last_sync_attempt_at?: string | null
      updated_at?: string
      effective_date?: string | null
      expiration_date?: string | null
      // mutators
      is_in_effect: boolean
      // relations
      meeting?: Meeting
      institution?: Institution
      activities_as_subject?: Activity[]
      // counts
      activities_as_subject_count: number
      // exists
      meeting_exists: boolean
      institution_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface Dutiable {
      // columns
      id: string
      via_dutiable_id?: string | null
      duty_id: string
      tenant_id?: number | null
      dutiable_id: string
      dutiable_type: string
      start_date: string
      end_date?: string | null
      study_program_id?: string | null
      study_program_note?: Array<unknown> | null
      additional_email?: string | null
      additional_photo?: string | null
      additional_photo_focal_point?: string | null
      description?: Array<unknown> | null
      use_original_duty_name: boolean
      created_at: string
      updated_at: string
      // mutators
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      dutiable?: Dutiable
      duty?: Duty
      study_program?: StudyProgram
      user?: User
      tenant?: Tenant
      via_dutiable?: Dutiable
      derived_dutiables?: Dutiable[]
      // counts
      derived_dutiables_count: number
      // exists
      duty_exists: boolean
      study_program_exists: boolean
      user_exists: boolean
      tenant_exists: boolean
      via_dutiable_exists: boolean
      derived_dutiables_exists: boolean
    }

    export interface Duty {
      // columns
      id: string
      name?: Array<unknown> | null
      description?: Array<unknown> | null
      institution_id: string
      order: number
      email?: string | null
      contacts_grouping: string
      places_to_occupy?: number | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      force_delete_blocked_reason: string
      has_protocol: boolean
      has_report: boolean
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      dutiables?: Dutiable[]
      users?: User[]
      current_users?: User[]
      previous_users?: User[]
      types?: Type[]
      institution?: Institution
      institutions?: Institution
      tenants?: Tenant
      meetings?: Meeting
      agenda_items?: AgendaItem
      tasks?: Task
      reservations?: Reservation
      resources?: Resource
      ex_officio_target_duties?: Duty[]
      ex_officio_source_duties?: Duty[]
      assignable_tenants?: Tenant[]
      roles?: Role[]
      teams?: Permission[]
      permissions?: Permission[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      activities_as_subject?: Activity[]
      notifications?: DatabaseNotification[]
      // counts
      dutiables_count: number
      users_count: number
      current_users_count: number
      previous_users_count: number
      types_count: number
      ex_officio_target_duties_count: number
      ex_officio_source_duties_count: number
      assignable_tenants_count: number
      roles_count: number
      teams_count: number
      permissions_count: number
      fileable_files_count: number
      available_files_count: number
      activities_as_subject_count: number
      notifications_count: number
      // exists
      dutiables_exists: boolean
      users_exists: boolean
      current_users_exists: boolean
      previous_users_exists: boolean
      types_exists: boolean
      institution_exists: boolean
      institutions_exists: boolean
      ex_officio_target_duties_exists: boolean
      ex_officio_source_duties_exists: boolean
      assignable_tenants_exists: boolean
      roles_exists: boolean
      teams_exists: boolean
      permissions_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      activities_as_subject_exists: boolean
      notifications_exists: boolean
    }

    export interface FieldResponse {
      // columns
      id: number
      registration_id: number
      form_field_id: number
      response: Array<unknown>
      created_at: string
      updated_at: string
      // relations
      registration?: Registration
      form_field?: FormField
      // counts
      // exists
      registration_exists: boolean
      form_field_exists: boolean
    }

    export interface File {
    }

    export interface FileableFile {
      // columns
      id: string
      fileable_type: string
      fileable_id: string
      sharepoint_id: string
      sharepoint_path?: string | null
      name: string
      file_type?: string | null
      mime_type?: string | null
      size_bytes?: number | null
      file_date?: string | null
      description?: string | null
      public_link?: string | null
      public_link_expires_at?: string | null
      last_synced_at?: string | null
      deleted_externally_at?: string | null
      created_at?: string | null
      updated_at?: string | null
      // mutators
      formatted_size: string
      file_type_label: string
      // relations
      fileable?: FileableFile
      // counts
      // exists
    }

    export interface Form {
      // columns
      id: string
      name: Array<unknown>
      description?: Array<unknown> | null
      tenant_id: number
      path?: Array<unknown> | null
      publish_time?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      force_delete_blocked_reason: string
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      form_fields?: FormField[]
      registrations?: Registration[]
      tenant?: Tenant
      // counts
      form_fields_count: number
      registrations_count: number
      // exists
      form_fields_exists: boolean
      registrations_exists: boolean
      tenant_exists: boolean
    }

    export interface FormField {
      // columns
      id: number
      form_id: string
      label: Array<unknown>
      description?: Array<unknown> | null
      type: string
      subtype?: string | null
      options?: Array<unknown> | null
      is_required: boolean
      order: number
      default_value?: Array<unknown> | null
      placeholder?: Array<unknown> | null
      use_model_options: boolean
      options_model?: string | null
      options_model_field?: string | null
      created_at: string
      updated_at: string
      // mutators
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      form?: Form
      field_responses?: FieldResponse[]
      // counts
      field_responses_count: number
      // exists
      form_exists: boolean
      field_responses_exists: boolean
    }

    export interface Institution {
      // columns
      id: string
      name?: Array<unknown> | null
      short_name?: Array<unknown> | null
      alias: string
      description?: Array<unknown> | null
      address?: Array<unknown> | null
      phone?: string | null
      email?: string | null
      website?: string | null
      image_url?: string | null
      logo_url?: string | null
      facebook_url?: string | null
      instagram_url?: string | null
      tenant_id?: number | null
      is_active: boolean
      meeting_periodicity_days?: number | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      related_institutions: unknown
      maybe_short_name: unknown
      governance_scope: InstitutionScope
      has_public_meetings: boolean
      force_delete_blocked_reason: string
      has_protocol: boolean
      has_report: boolean
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      duties?: Duty[]
      cadences?: Cadence[]
      types?: Type[]
      tenant?: Tenant
      tenants?: Tenant
      documents?: Document[]
      check_ins?: InstitutionCheckIn[]
      meetings?: Meeting[]
      problems?: Problem[]
      tasks_from_meetings?: Task
      users?: User
      followers?: User[]
      administrators?: User[]
      administrator_assignments?: InstitutionAdministrator[]
      comments?: Comment[]
      root_comments?: Comment[]
      outgoing_relationships?: Relationship[]
      incoming_relationships?: Relationship[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      tasks?: Task[]
      activities_as_subject?: Activity[]
      // counts
      duties_count: number
      cadences_count: number
      types_count: number
      documents_count: number
      check_ins_count: number
      meetings_count: number
      problems_count: number
      followers_count: number
      administrators_count: number
      administrator_assignments_count: number
      comments_count: number
      root_comments_count: number
      outgoing_relationships_count: number
      incoming_relationships_count: number
      fileable_files_count: number
      available_files_count: number
      tasks_count: number
      activities_as_subject_count: number
      // exists
      duties_exists: boolean
      cadences_exists: boolean
      types_exists: boolean
      tenant_exists: boolean
      tenants_exists: boolean
      documents_exists: boolean
      check_ins_exists: boolean
      meetings_exists: boolean
      problems_exists: boolean
      followers_exists: boolean
      administrators_exists: boolean
      administrator_assignments_exists: boolean
      comments_exists: boolean
      root_comments_exists: boolean
      outgoing_relationships_exists: boolean
      incoming_relationships_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      tasks_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface InstitutionAdministrator {
      // columns
      id: string
      institution_id: string
      cadence_id: string
      user_id: string
      created_at?: string | null
      updated_at?: string | null
      // relations
      institution?: Institution
      cadence?: Cadence
      user?: User
      // counts
      // exists
      institution_exists: boolean
      cadence_exists: boolean
      user_exists: boolean
    }

    export interface InstitutionCheckIn {
      // columns
      id: string
      tenant_id?: number | null
      institution_id: string
      user_id: string
      start_date: string
      end_date: string
      note?: string | null
      created_at?: string | null
      updated_at?: string | null
      // relations
      institution?: Institution
      user?: User
      tenant?: Tenant
      // counts
      // exists
      institution_exists: boolean
      user_exists: boolean
      tenant_exists: boolean
    }

    export interface InstitutionFollow {
      // columns
      id: string
      user_id: string
      institution_id: string
      created_at?: string | null
      updated_at?: string | null
      // relations
      user?: User
      institution?: Institution
      // counts
      // exists
      user_exists: boolean
      institution_exists: boolean
    }

    export interface InstitutionNotificationMute {
      // columns
      id: string
      user_id: string
      institution_id: string
      muted_at: string
      created_at?: string | null
      updated_at?: string | null
      // relations
      user?: User
      institution?: Institution
      // counts
      // exists
      user_exists: boolean
      institution_exists: boolean
    }

    export interface LecturerReview {
      // columns
      id: string
      study_set_course_id: string
      lecturer: Array<unknown>
      comment: Array<unknown>
      is_visible: boolean
      created_at: string
      updated_at: string
      // mutators
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      course?: StudySetCourse
      // counts
      // exists
      course_exists: boolean
    }

    export interface Meeting {
      // columns
      id: string
      title: string
      description?: string | null
      type?: MeetingType | null
      start_time: string
      end_time?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      is_joint: boolean
      is_public: boolean
      type_label: string
      type_slug: string
      completion_status: string
<<<<<<< Updated upstream
      requires_student_perspective: boolean
=======
      has_calendar_event: boolean
>>>>>>> Stashed changes
      has_protocol: boolean
      has_report: boolean
      // relations
      agenda_items?: AgendaItem[]
      institutions?: Institution[]
      calendar_event?: Calendar
      documents?: Document[]
      comments?: Comment[]
      root_comments?: Comment[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      tasks?: Task[]
      activities_as_subject?: Activity[]
      // counts
      agenda_items_count: number
      institutions_count: number
      documents_count: number
      comments_count: number
      root_comments_count: number
      fileable_files_count: number
      available_files_count: number
      tasks_count: number
      activities_as_subject_count: number
      // exists
      agenda_items_exists: boolean
      institutions_exists: boolean
      calendar_event_exists: boolean
      documents_exists: boolean
      comments_exists: boolean
      root_comments_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      tasks_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface Model {
    }

    export interface Navigation {
      // columns
      id: number
      parent_id: number
      padalinys_id: number
      name: string
      lang: string
      url: string
      order: number
      is_active: boolean
      extra_attributes?: Array<unknown> | null
      created_at?: string
      updated_at?: string
      deleted_at?: string | null
      // relations
      user?: User
      activities_as_subject?: Activity[]
      // counts
      activities_as_subject_count: number
      // exists
      user_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface News {
      // columns
      id: number
      title: string
      category_id?: number | null
      permalink?: string | null
      short: string
      lang: string
      other_lang_id?: number | null
      content_id: number
      image?: string | null
      image_author?: string | null
      important: boolean
      tenant_id: number
      publish_time?: string | null
      main_points?: string | null
      highlights?: Array<unknown> | null
      layout: string
      show_breadcrumbs: boolean
      read_more?: string | null
      draft?: boolean | null
      created_at: string
      updated_at: string
      last_edited_at?: string | null
      deleted_at?: string | null
      // relations
      user?: User
      tenant?: Tenant
      other_language_news?: News
      tags?: Tag[]
      content?: Content
      activities_as_subject?: Activity[]
      // counts
      tags_count: number
      activities_as_subject_count: number
      // exists
      user_exists: boolean
      tenant_exists: boolean
      other_language_news_exists: boolean
      tags_exists: boolean
      content_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface NotificationDigestQueue {
      // columns
      id: number
      user_id: string
      notification_class: string
      category: string
      data: Array<unknown>
      created_at?: string | null
      updated_at?: string | null
      // relations
      user?: User
      // counts
      // exists
      user_exists: boolean
    }

    export interface Page {
      // columns
      id: number
      title: string
      permalink?: string | null
      lang: string
      other_lang_id?: number | null
      content_id: number
      category_id?: number | null
      is_active: boolean
      highlights?: Array<unknown> | null
      layout: string
      show_table_of_contents: boolean
      show_title: boolean
      show_breadcrumbs: boolean
      featured_image?: string | null
      meta_description?: string | null
      publish_time?: string | null
      tenant_id: number
      created_at: string
      updated_at: string
      last_edited_at?: string | null
      deleted_at?: string | null
      // relations
      tenant?: Tenant
      other_language_page?: Page
      category?: Category
      content?: Content
      activities_as_subject?: Activity[]
      // counts
      activities_as_subject_count: number
      // exists
      tenant_exists: boolean
      other_language_page_exists: boolean
      category_exists: boolean
      content_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface Permission {
      // columns
      id: string
      name: string
      guard_name: string
      created_at?: string | null
      updated_at?: string | null
      // relations
      roles?: Role[]
      users?: User[]
      teams?: Permission[]
      permissions?: Permission[]
      // counts
      roles_count: number
      users_count: number
      teams_count: number
      permissions_count: number
      // exists
      roles_exists: boolean
      users_exists: boolean
      teams_exists: boolean
      permissions_exists: boolean
    }

    export interface Problem {
      // columns
      id: string
      title: Array<unknown>
      description: Array<unknown>
      solution?: Array<unknown> | null
      steps_taken?: Array<unknown> | null
      tenant_id: number
      created_by: string
      responsible_user_id?: string | null
      occurred_at: string
      resolved_at?: string | null
      status: string
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      tenant?: Tenant
      created_by?: User
      responsible_user?: User
      categories?: ProblemCategory[]
      institutions?: Institution[]
      activities_as_subject?: Activity[]
      // counts
      categories_count: number
      institutions_count: number
      activities_as_subject_count: number
      // exists
      tenant_exists: boolean
      created_by_exists: boolean
      responsible_user_exists: boolean
      categories_exists: boolean
      institutions_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface ProblemCategory {
      // columns
      id: number
      name: Array<unknown>
      slug: string
      description?: Array<unknown> | null
      created_at: string
      updated_at: string
      // mutators
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      problems?: Problem[]
      // counts
      problems_count: number
      // exists
      problems_exists: boolean
    }

    export interface PublicInstitution {
      // columns
      id: string
      name?: Array<unknown> | null
      short_name?: Array<unknown> | null
      alias: string
      description?: Array<unknown> | null
      address?: Array<unknown> | null
      phone?: string | null
      email?: string | null
      website?: string | null
      image_url?: string | null
      logo_url?: string | null
      facebook_url?: string | null
      instagram_url?: string | null
      tenant_id?: number | null
      is_active: boolean
      meeting_periodicity_days?: number | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      related_institutions: unknown
      maybe_short_name: unknown
      governance_scope: InstitutionScope
      has_public_meetings: boolean
      force_delete_blocked_reason: string
      has_protocol: boolean
      has_report: boolean
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      types?: Type[]
      duties?: Duty[]
      meetings?: Meeting[]
      cadences?: Cadence[]
      tenant?: Tenant
      tenants?: Tenant
      documents?: Document[]
      check_ins?: InstitutionCheckIn[]
      problems?: Problem[]
      tasks_from_meetings?: Task
      users?: User
      followers?: User[]
      administrators?: User[]
      administrator_assignments?: InstitutionAdministrator[]
      comments?: Comment[]
      root_comments?: Comment[]
      outgoing_relationships?: Relationship[]
      incoming_relationships?: Relationship[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      tasks?: Task[]
      activities_as_subject?: Activity[]
      // counts
      types_count: number
      duties_count: number
      meetings_count: number
      cadences_count: number
      documents_count: number
      check_ins_count: number
      problems_count: number
      followers_count: number
      administrators_count: number
      administrator_assignments_count: number
      comments_count: number
      root_comments_count: number
      outgoing_relationships_count: number
      incoming_relationships_count: number
      fileable_files_count: number
      available_files_count: number
      tasks_count: number
      activities_as_subject_count: number
      // exists
      types_exists: boolean
      duties_exists: boolean
      meetings_exists: boolean
      cadences_exists: boolean
      tenant_exists: boolean
      tenants_exists: boolean
      documents_exists: boolean
      check_ins_exists: boolean
      problems_exists: boolean
      followers_exists: boolean
      administrators_exists: boolean
      administrator_assignments_exists: boolean
      comments_exists: boolean
      root_comments_exists: boolean
      outgoing_relationships_exists: boolean
      incoming_relationships_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      tasks_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface PublicMeeting {
      // columns
      id: string
      title: string
      description?: string | null
      type?: MeetingType | null
      start_time: string
      end_time?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      is_joint: boolean
      is_public: boolean
      type_label: string
      type_slug: string
      completion_status: string
<<<<<<< Updated upstream
      requires_student_perspective: boolean
=======
      has_calendar_event: boolean
>>>>>>> Stashed changes
      has_protocol: boolean
      has_report: boolean
      // relations
      institutions?: Institution[]
      types?: Type[]
      agenda_items?: AgendaItem[]
      calendar_event?: Calendar
      documents?: Document[]
      comments?: Comment[]
      root_comments?: Comment[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      tasks?: Task[]
      activities_as_subject?: Activity[]
      // counts
      institutions_count: number
      types_count: number
      agenda_items_count: number
      documents_count: number
      comments_count: number
      root_comments_count: number
      fileable_files_count: number
      available_files_count: number
      tasks_count: number
      activities_as_subject_count: number
      // exists
      institutions_exists: boolean
      types_exists: boolean
      agenda_items_exists: boolean
      calendar_event_exists: boolean
      documents_exists: boolean
      comments_exists: boolean
      root_comments_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      tasks_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface PublicNews {
      // columns
      id: number
      title: string
      category_id?: number | null
      permalink?: string | null
      short: string
      lang: string
      other_lang_id?: number | null
      content_id: number
      image?: string | null
      image_author?: string | null
      important: boolean
      tenant_id: number
      publish_time?: string | null
      main_points?: string | null
      highlights?: Array<unknown> | null
      layout: string
      show_breadcrumbs: boolean
      read_more?: string | null
      draft?: boolean | null
      created_at: string
      updated_at: string
      last_edited_at?: string | null
      deleted_at?: string | null
      // relations
      user?: User
      tenant?: Tenant
      other_language_news?: News
      tags?: Tag[]
      content?: Content
      activities_as_subject?: Activity[]
      // counts
      tags_count: number
      activities_as_subject_count: number
      // exists
      user_exists: boolean
      tenant_exists: boolean
      other_language_news_exists: boolean
      tags_exists: boolean
      content_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface PublicPage {
      // columns
      id: number
      title: string
      permalink?: string | null
      lang: string
      other_lang_id?: number | null
      content_id: number
      category_id?: number | null
      is_active: boolean
      highlights?: Array<unknown> | null
      layout: string
      show_table_of_contents: boolean
      show_title: boolean
      show_breadcrumbs: boolean
      featured_image?: string | null
      meta_description?: string | null
      publish_time?: string | null
      tenant_id: number
      created_at: string
      updated_at: string
      last_edited_at?: string | null
      deleted_at?: string | null
      // relations
      tenant?: Tenant
      other_language_page?: Page
      category?: Category
      content?: Content
      activities_as_subject?: Activity[]
      // counts
      activities_as_subject_count: number
      // exists
      tenant_exists: boolean
      other_language_page_exists: boolean
      category_exists: boolean
      content_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface QuickLink {
      // columns
      id: number
      link?: string | null
      text?: string | null
      icon?: string | null
      order?: number | null
      is_active: boolean
      is_important: boolean
      tenant_id: number
      lang?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // relations
      tenant?: Tenant
      // counts
      // exists
      tenant_exists: boolean
    }

    export interface Registration {
      // columns
      id: number
      user_id?: string | null
      form_id: string
      created_at: string
      updated_at: string
      // relations
      form?: Form
      field_responses?: FieldResponse[]
      // counts
      field_responses_count: number
      // exists
      form_exists: boolean
      field_responses_exists: boolean
    }

    export interface Relationship {
      // columns
      id: number
      name: string
      slug: string
      description?: string | null
      type?: string | null
      created_at: string
      updated_at: string
      // relations
      institutions?: Institution[]
      relationshipables?: Relationshipable[]
      types?: Type[]
      // counts
      institutions_count: number
      relationshipables_count: number
      types_count: number
      // exists
      institutions_exists: boolean
      relationshipables_exists: boolean
      types_exists: boolean
    }

    export interface Relationshipable {
      // columns
      id: number
      relationship_id: number
      relationshipable_type: string
      relationshipable_id: string
      related_model_id: string
      scope: string
      bidirectional: boolean
      created_at: string
      updated_at: string
      // relations
      relationshipable?: Relationshipable
      related_model?: Relationshipable
      relationship?: Relationship
      // counts
      // exists
      relationship_exists: boolean
    }

    export interface Reservation {
      // columns
      id: string
      name: string
      description?: string | null
      start_time: string
      end_time: string
      completed_at?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      is_completed: unknown
      // relations
      resources?: Resource[]
      users?: User[]
      comments?: Comment[]
      root_comments?: Comment[]
      tasks?: Task[]
      activities_as_subject?: Activity[]
      // counts
      resources_count: number
      users_count: number
      comments_count: number
      root_comments_count: number
      tasks_count: number
      activities_as_subject_count: number
      // exists
      resources_exists: boolean
      users_exists: boolean
      comments_exists: boolean
      root_comments_exists: boolean
      tasks_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface ReservationResource {
      // columns
      id: number
      reservation_id: string
      resource_id: string
      start_time?: string | null
      end_time?: string | null
      quantity: number
      state: unknown
      returned_at?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      approvable: boolean
      state_properties: unknown
      // relations
      reservation?: Reservation
      resource?: Resource
      approvals?: Approval[]
      comments?: Comment[]
      root_comments?: Comment[]
      // counts
      approvals_count: number
      comments_count: number
      root_comments_count: number
      // exists
      reservation_exists: boolean
      resource_exists: boolean
      approvals_exists: boolean
      comments_exists: boolean
      root_comments_exists: boolean
    }

    export interface Resource {
      // columns
      id: string
      identifier?: string | null
      name: Array<unknown>
      description?: Array<unknown> | null
      resource_category_id?: number | null
      location?: string | null
      capacity: number
      tenant_id: number
      is_reservable: boolean
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      force_delete_blocked_reason: string
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      reservations?: Reservation[]
      active_reservations?: Reservation[]
      tenant?: Tenant
      category?: ResourceCategory
      media?: Media[]
      // counts
      reservations_count: number
      active_reservations_count: number
      media_count: number
      // exists
      reservations_exists: boolean
      active_reservations_exists: boolean
      tenant_exists: boolean
      category_exists: boolean
      media_exists: boolean
    }

    export interface ResourceCategory {
      // columns
      id: number
      name: Array<unknown>
      description?: Array<unknown> | null
      icon?: string | null
      created_at: string
      updated_at: string
      // mutators
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      resources?: Resource[]
      // counts
      resources_count: number
      // exists
      resources_exists: boolean
    }

    export interface Role {
      // columns
      id: string
      name: string
      guard_name: string
      created_at?: string | null
      updated_at?: string | null
      // relations
      duties?: Duty[]
      attachable_types?: Type[]
      types?: Type[]
      permissions?: Permission[]
      users?: User[]
      // counts
      duties_count: number
      attachable_types_count: number
      types_count: number
      permissions_count: number
      users_count: number
      // exists
      duties_exists: boolean
      attachable_types_exists: boolean
      types_exists: boolean
      permissions_exists: boolean
      users_exists: boolean
    }

    export interface RoleType {
      // columns
      id: number
      role_id: string
      type_id: number
      created_at?: string | null
      updated_at?: string | null
      // relations
      role?: Role
      type?: Type
      // counts
      // exists
      role_exists: boolean
      type_exists: boolean
    }

    export interface SharepointFile {
      // columns
      sharepoint_id: string
      id: string
      // relations
      fileables?: SharepointFileable[]
      types?: Type[]
      institutions?: Institution[]
      meetings?: Meeting[]
      comments?: Comment[]
      root_comments?: Comment[]
      // counts
      fileables_count: number
      types_count: number
      institutions_count: number
      meetings_count: number
      comments_count: number
      root_comments_count: number
      // exists
      fileables_exists: boolean
      types_exists: boolean
      institutions_exists: boolean
      meetings_exists: boolean
      comments_exists: boolean
      root_comments_exists: boolean
    }

    export interface SharepointFileable {
      // columns
      sharepoint_file_id: string
      fileable_type: string
      fileable_id: string
      created_at: string
      updated_at: string
      // relations
      fileable?: SharepointFileable
      sharepoint_file?: SharepointFile
      meeting?: Meeting
      institution?: Institution
      type?: Type
      // counts
      // exists
      sharepoint_file_exists: boolean
      meeting_exists: boolean
      institution_exists: boolean
      type_exists: boolean
    }

    export interface StudyProgram {
      // columns
      id: string
      name: Array<unknown>
      degree: string
      tenant_id: number
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      force_delete_blocked_reason: string
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      tenant?: Tenant
      dutiables?: Dutiable[]
      // counts
      dutiables_count: number
      // exists
      tenant_exists: boolean
      dutiables_exists: boolean
    }

    export interface StudySet {
      // columns
      id: string
      name: Array<unknown>
      description?: Array<unknown> | null
      order: number
      is_visible: boolean
      tenant_id: number
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      total_credits: number
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      tenant?: Tenant
      courses?: StudySetCourse[]
      reviews?: LecturerReview[]
      // counts
      courses_count: number
      reviews_count: number
      // exists
      tenant_exists: boolean
      courses_exists: boolean
      reviews_exists: boolean
    }

    export interface StudySetCourse {
      // columns
      id: string
      study_set_id: string
      name: Array<unknown>
      order: number
      semester: string
      credits: number
      is_visible: boolean
      created_at: string
      updated_at: string
      // mutators
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      study_set?: StudySet
      reviews?: LecturerReview[]
      // counts
      reviews_count: number
      // exists
      study_set_exists: boolean
      reviews_exists: boolean
    }

    export interface Tag {
      // columns
      id: number
      alias?: string | null
      created_at: string
      updated_at: string
      name?: Array<unknown> | null
      description?: Array<unknown> | null
      deleted_at?: string | null
      // mutators
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      news?: News[]
      // counts
      news_count: number
      // exists
      news_exists: boolean
    }

    export interface Task {
      // columns
      id: string
      name: string
      description?: string | null
      action_type?: ActionType | null
      metadata?: Array<unknown> | null
      due_date?: string | null
      taskable_type: string
      taskable_id: string
      completed_at?: string | null
      created_at: string
      updated_at: string
      // mutators
      icon: string
      color: string
      // relations
      taskable?: Task
      users?: User[]
      tenants?: Tenant
      // counts
      users_count: number
      // exists
      users_exists: boolean
    }

    export interface Tenant {
      // columns
      id: number
      type?: TenantType | null
      fullname: string
      shortname: string
      alias: string
      phone?: string | null
      email?: string | null
      address?: string | null
      shortname_vu?: string | null
      primary_institution_id?: string | null
      content_id?: number | null
      // relations
      banners?: Banner[]
      calendar?: Calendar[]
      duties?: Duty[]
      institutions?: Institution[]
      news?: News[]
      pages?: Page[]
      quick_links?: QuickLink[]
      resources?: Resource[]
      study_sets?: StudySet[]
      users?: User
      reservations?: Reservation
      primary_institution?: Institution
      content?: Content
      // counts
      banners_count: number
      calendar_count: number
      duties_count: number
      institutions_count: number
      news_count: number
      pages_count: number
      quick_links_count: number
      resources_count: number
      study_sets_count: number
      // exists
      banners_exists: boolean
      calendar_exists: boolean
      duties_exists: boolean
      institutions_exists: boolean
      news_exists: boolean
      pages_exists: boolean
      quick_links_exists: boolean
      resources_exists: boolean
      study_sets_exists: boolean
      primary_institution_exists: boolean
      content_exists: boolean
    }

    export interface TextBoxSubmission {
      // columns
      id: string
      content_part_id: number
      text: string
      user_id?: string | null
      ip_address?: string | null
      created_at: string
      updated_at: string
      // relations
      content_part?: ContentPart
      user?: User
      // counts
      // exists
      content_part_exists: boolean
      user_exists: boolean
    }

    export interface Type {
      // columns
      id: number
      parent_id?: number | null
      title?: Array<unknown> | null
      description?: Array<unknown> | null
      model_type?: string | null
      slug?: string | null
      extra_attributes?: Array<unknown> | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      force_delete_blocked_reason: string
      has_protocol: boolean
      has_report: boolean
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      institutions?: Institution[]
      duties?: Duty[]
      roles?: Role[]
      descendants?: Type[]
      parent?: Type
      recursive_parent?: Type
      outgoing_relationships?: Relationship[]
      incoming_relationships?: Relationship[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      activities_as_subject?: Activity[]
      // counts
      institutions_count: number
      duties_count: number
      roles_count: number
      descendants_count: number
      outgoing_relationships_count: number
      incoming_relationships_count: number
      fileable_files_count: number
      available_files_count: number
      activities_as_subject_count: number
      // exists
      institutions_exists: boolean
      duties_exists: boolean
      roles_exists: boolean
      descendants_exists: boolean
      parent_exists: boolean
      recursive_parent_exists: boolean
      outgoing_relationships_exists: boolean
      incoming_relationships_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      activities_as_subject_exists: boolean
    }

    export interface Typeable {
      // columns
      type_id: number
      typeable_type: string
      typeable_id: string
      // relations
      type?: Type
      typeable?: Typeable
      // counts
      // exists
      type_exists: boolean
    }

    export interface User {
      // columns
      id: string
      email: string
      phone?: string | null
      facebook_url?: string | null
      name: string
      pronouns?: Array<unknown> | null
      show_pronouns: boolean
      password?: string | null
      is_active: boolean
      email_verified_at?: string | null
      remember_token?: string | null
      last_action?: string | null
      tutorial_progress?: Array<unknown> | null
      notification_preferences?: Array<unknown> | null
      ui_preferences?: Array<unknown> | null
      microsoft_token?: string | null
      updated_at: string
      created_at: string
      profile_photo_path?: string | null
      profile_photo_focal_point?: string | null
      deleted_at?: string | null
      name_was_changed?: boolean
      // mutators
      has_password: unknown
      force_delete_blocked_reason: string
      translatable_columns_from: Array<unknown>
      translations: unknown
      // relations
      duties?: Duty[]
      previous_duties?: Duty[]
      current_duties?: Duty[]
      dutiables?: Dutiable[]
      tenants?: Tenant
      tasks?: Task[]
      administered_institutions?: Institution[]
      followed_institutions?: Institution[]
      muted_institutions?: Institution[]
      reservations?: Reservation[]
      push_subscriptions?: PushSubscription[]
      roles?: Role[]
      teams?: Permission[]
      permissions?: Permission[]
      activities_as_subject?: Activity[]
      notifications?: DatabaseNotification[]
      // counts
      duties_count: number
      previous_duties_count: number
      current_duties_count: number
      dutiables_count: number
      tasks_count: number
      administered_institutions_count: number
      followed_institutions_count: number
      muted_institutions_count: number
      reservations_count: number
      push_subscriptions_count: number
      roles_count: number
      teams_count: number
      permissions_count: number
      activities_as_subject_count: number
      notifications_count: number
      // exists
      duties_exists: boolean
      previous_duties_exists: boolean
      current_duties_exists: boolean
      dutiables_exists: boolean
      tasks_exists: boolean
      administered_institutions_exists: boolean
      followed_institutions_exists: boolean
      muted_institutions_exists: boolean
      reservations_exists: boolean
      push_subscriptions_exists: boolean
      roles_exists: boolean
      teams_exists: boolean
      permissions_exists: boolean
      activities_as_subject_exists: boolean
      notifications_exists: boolean
    }

    export interface Vote {
      // columns
      id: string
      agenda_item_id: string
      is_main: boolean
      is_consensus: boolean
      title?: string | null
      student_vote?: string | null
      decision?: string | null
      student_benefit?: string | null
      note?: string | null
      order: number
      created_at?: string | null
      updated_at?: string | null
      // mutators
      is_complete: boolean
      vote_matches: boolean
      vote_alignment_status: string
      decision_label: string
      student_vote_label: string
      student_benefit_label: string
      // relations
      agenda_item?: AgendaItem
      activities_as_subject?: Activity[]
      // counts
      activities_as_subject_count: number
      // exists
      agenda_item_exists: boolean
      activities_as_subject_exists: boolean
    }

    const AgendaItemType = {
      Voting: 'voting',
      Informational: 'informational',
      Deferred: 'deferred',
    } as const;

    export type AgendaItemType = typeof AgendaItemType[keyof typeof AgendaItemType]

    const ApprovalDecision = {
      Approved: 'approved',
      Rejected: 'rejected',
      Cancelled: 'cancelled',
    } as const;

    export type ApprovalDecision = typeof ApprovalDecision[keyof typeof ApprovalDecision]

    const CalendarHeroStyleEnum = {
      CARD: 'card',
      SPLIT: 'split',
      MINIMAL: 'minimal',
    } as const;

    export type CalendarHeroStyleEnum = typeof CalendarHeroStyleEnum[keyof typeof CalendarHeroStyleEnum]

    const CommentKind = {
      Comment: 'comment',
      Poll: 'poll',
    } as const;

    export type CommentKind = typeof CommentKind[keyof typeof CommentKind]

    const InstitutionScope = {
      Vusa: 'vusa',
      University: 'vu',
      National: 'national',
      International: 'international',
    } as const;

    export type InstitutionScope = typeof InstitutionScope[keyof typeof InstitutionScope]

    const MeetingType = {
      InPerson: 'in-person',
      Remote: 'remote',
      Email: 'email',
    } as const;

    export type MeetingType = typeof MeetingType[keyof typeof MeetingType]

    const ActionType = {
      Manual: 'manual',
      Approval: 'approval',
      Pickup: 'pickup',
      Return: 'return',
      AgendaCreation: 'agenda_creation',
      AgendaCompletion: 'agenda_completion',
      PeriodicityGap: 'periodicity_gap',
    } as const;

    export type ActionType = typeof ActionType[keyof typeof ActionType]

    const TenantType = {
      Pagrindinis: 'pagrindinis',
      Padalinys: 'padalinys',
      Pkp: 'pkp',
    } as const;

    export type TenantType = typeof TenantType[keyof typeof TenantType]

  }
}

