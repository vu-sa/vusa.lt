export {}
declare global {
  export namespace models {

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
      // relations
      tenant?: Tenant
      // counts
      // exists
      tenant_exists: boolean
    }

    export interface Category {
      // columns
      id: number
      alias?: string | null
      created_at: string
      updated_at: string
      name?: Array<unknown> | null
      description?: Array<unknown> | null
      // mutators
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
      comment: string
      user_id: string
      commentable_type: string
      commentable_id: string
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // relations
      commentable?: Comment
      comments?: Comment[]
      user?: User
      activities?: Activity[]
      // counts
      comments_count: number
      activities_count: number
      // exists
      comments_exists: boolean
      user_exists: boolean
      activities_exists: boolean
    }

    export interface Content {
      // columns
      id: number
      created_at: string
      updated_at: string
      // relations
      parts?: ContentPart[]
      // counts
      parts_count: number
      // exists
      parts_exists: boolean
    }

    export interface File {
    }

    export interface Form {
      // columns
      id: string
      name: Array<unknown>
      description?: Array<unknown> | null
      user_id?: string | null
      tenant_id: number
      path?: Array<unknown> | null
      publish_time?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      translations: unknown
      // relations
      form_fields?: FormField[]
      registrations?: Registration[]
      user?: User
      tenant?: Tenant
      training?: Training
      // counts
      form_fields_count: number
      registrations_count: number
      // exists
      form_fields_exists: boolean
      registrations_exists: boolean
      user_exists: boolean
      tenant_exists: boolean
      training_exists: boolean
    }

    export interface Membership {
      // columns
      id: string
      name: Array<unknown>
      tenant_id: number
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      tenant?: Tenant
      users?: User[]
      available_trainings?: Training[]
      // counts
      users_count: number
      available_trainings_count: number
      // exists
      tenant_exists: boolean
      users_exists: boolean
      available_trainings_exists: boolean
    }

    export interface Model {
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
      permissions?: Permission[]
      // counts
      roles_count: number
      users_count: number
      permissions_count: number
      // exists
      roles_exists: boolean
      users_exists: boolean
      permissions_exists: boolean
    }

    export interface ProgrammeElement {
      // columns
      id: number
      programme_day_id: number
      elementable_type: string
      elementable_id: number
      order: number
      created_at: string
      updated_at: string
      // relations
      elementable?: ProgrammeElement
      day?: ProgrammeDay
      blocks?: ProgrammeBlock[]
      // counts
      blocks_count: number
      // exists
      day_exists: boolean
      blocks_exists: boolean
    }

    export interface Trainable {
      // columns
      id: number
      training_id: string
      trainable_type: string
      trainable_id: string
      tenant_id?: number | null
      quota?: number | null
      created_at: string
      updated_at: string
      // relations
      trainable?: Trainable
      user?: User
      duty?: Duty
      institution?: Institution
      membership?: Membership
      tenant?: Tenant
      // counts
      // exists
      user_exists: boolean
      duty_exists: boolean
      institution_exists: boolean
      membership_exists: boolean
      tenant_exists: boolean
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
      description?: string | null
      student_vote?: string | null
      decision?: string | null
      student_benefit?: string | null
      start_time?: string | null
      // relations
      meeting?: Meeting
      activities?: Activity[]
      // counts
      activities_count: number
      // exists
      meeting_exists: boolean
      activities_exists: boolean
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
      commentable?: ReservationResource
      comments?: Comment[]
      // counts
      approvals_count: number
      comments_count: number
      // exists
      reservation_exists: boolean
      resource_exists: boolean
      approvals_exists: boolean
      comments_exists: boolean
    }

    export interface MembershipUser {
      // columns
      id: number
      membership_id: string
      user_id: string
      start_date: string
      end_date?: string | null
      status: string
      created_at: string
      updated_at: string
      // relations
      membership?: Membership
      user?: User
      // counts
      // exists
      membership_exists: boolean
      user_exists: boolean
    }

    export interface Dutiable {
      // columns
      id: string
      duty_id: string
      dutiable_id: string
      dutiable_type: string
      start_date: string
      end_date?: string | null
      study_program_id?: string | null
      additional_email?: string | null
      additional_photo?: string | null
      description?: Array<unknown> | null
      use_original_duty_name: boolean
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      dutiable?: Dutiable
      duty?: Duty
      study_program?: StudyProgram
      user?: User
      // counts
      // exists
      duty_exists: boolean
      study_program_exists: boolean
      user_exists: boolean
    }

    export interface Programme {
      // columns
      id: number
      title: Array<unknown>
      description?: Array<unknown> | null
      start_date: string
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      days?: ProgrammeDay[]
      programmable?: Programme
      // counts
      days_count: number
      // exists
      days_exists: boolean
    }

    export interface ProgrammeBlock {
      // columns
      id: number
      programme_section_id: number
      title: Array<unknown>
      description?: Array<unknown> | null
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      parts?: ProgrammePart[]
      // counts
      parts_count: number
      // exists
      parts_exists: boolean
    }

    export interface ProgrammePart {
      // columns
      id: number
      title: Array<unknown>
      description?: Array<unknown> | null
      instructor?: string | null
      duration: number
      start_time?: string | null
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      programme_days?: ProgrammeDay[]
      programme_blocks?: ProgrammeBlock[]
      // counts
      programme_days_count: number
      programme_blocks_count: number
      // exists
      programme_days_exists: boolean
      programme_blocks_exists: boolean
    }

    export interface ProgrammeSection {
      // columns
      id: number
      title: Array<unknown>
      duration: number
      start_time?: string | null
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      programme_days?: ProgrammeDay[]
      blocks?: ProgrammeBlock[]
      // counts
      programme_days_count: number
      blocks_count: number
      // exists
      programme_days_exists: boolean
      blocks_exists: boolean
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

    export interface ResourceCategory {
      // columns
      id: number
      name: Array<unknown>
      description?: Array<unknown> | null
      icon?: string | null
      created_at: string
      updated_at: string
      // mutators
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
      commentable?: SharepointFile
      comments?: Comment[]
      // counts
      fileables_count: number
      types_count: number
      institutions_count: number
      meetings_count: number
      comments_count: number
      // exists
      fileables_exists: boolean
      types_exists: boolean
      institutions_exists: boolean
      meetings_exists: boolean
      comments_exists: boolean
    }

    export interface StudyProgram {
      // columns
      id: string
      name: Array<unknown>
      degree: string
      tenant_id: number
      created_at: string
      updated_at: string
      // mutators
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

    export interface Tag {
      // columns
      id: number
      alias?: string | null
      created_at: string
      updated_at: string
      name?: Array<unknown> | null
      description?: Array<unknown> | null
      // mutators
      translations: unknown
      // relations
      news?: News[]
      // counts
      news_count: number
      // exists
      news_exists: boolean
    }

    export interface Typeable {
      // relations
      type?: Type
      typeable?: Typeable
      // counts
      // exists
      type_exists: boolean
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
      html: unknown
      // relations
      content?: Content
      // counts
      // exists
      content_exists: boolean
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
      deleted_at?: string | null
      // mutators
      icon: string
      color: string
      // relations
      taskable?: Task
      users?: User[]
      // counts
      users_count: number
      // exists
      users_exists: boolean
    }

    export interface Tenant {
      // columns
      id: number
      type?: string | null
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
      // exists
      banners_exists: boolean
      calendar_exists: boolean
      duties_exists: boolean
      institutions_exists: boolean
      news_exists: boolean
      pages_exists: boolean
      quick_links_exists: boolean
      resources_exists: boolean
      primary_institution_exists: boolean
      content_exists: boolean
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
      contacts_layout: string
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      related_institutions: unknown
      maybe_short_name: unknown
      has_public_meetings: boolean
      has_protocol: boolean
      has_report: boolean
      translations: unknown
      // relations
      duties?: Duty[]
      types?: Type[]
      tenant?: Tenant
      documents?: Document[]
      check_ins?: InstitutionCheckIn[]
      meetings?: Meeting[]
      users?: User
      available_trainings?: Training[]
      commentable?: Institution
      comments?: Comment[]
      outgoing_relationships?: Relationship[]
      incoming_relationships?: Relationship[]
      files?: SharepointFile[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      activities?: Activity[]
      // counts
      duties_count: number
      types_count: number
      documents_count: number
      check_ins_count: number
      meetings_count: number
      available_trainings_count: number
      comments_count: number
      outgoing_relationships_count: number
      incoming_relationships_count: number
      files_count: number
      fileable_files_count: number
      available_files_count: number
      activities_count: number
      // exists
      duties_exists: boolean
      types_exists: boolean
      tenant_exists: boolean
      documents_exists: boolean
      check_ins_exists: boolean
      meetings_exists: boolean
      available_trainings_exists: boolean
      comments_exists: boolean
      outgoing_relationships_exists: boolean
      incoming_relationships_exists: boolean
      files_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      activities_exists: boolean
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
      contacts_layout: string
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      related_institutions: unknown
      maybe_short_name: unknown
      has_public_meetings: boolean
      has_protocol: boolean
      has_report: boolean
      translations: unknown
      // relations
      types?: Type[]
      duties?: Duty[]
      meetings?: Meeting[]
      tenant?: Tenant
      documents?: Document[]
      check_ins?: InstitutionCheckIn[]
      users?: User
      available_trainings?: Training[]
      commentable?: PublicInstitution
      comments?: Comment[]
      outgoing_relationships?: Relationship[]
      incoming_relationships?: Relationship[]
      files?: SharepointFile[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      activities?: Activity[]
      // counts
      types_count: number
      duties_count: number
      meetings_count: number
      documents_count: number
      check_ins_count: number
      available_trainings_count: number
      comments_count: number
      outgoing_relationships_count: number
      incoming_relationships_count: number
      files_count: number
      fileable_files_count: number
      available_files_count: number
      activities_count: number
      // exists
      types_exists: boolean
      duties_exists: boolean
      meetings_exists: boolean
      tenant_exists: boolean
      documents_exists: boolean
      check_ins_exists: boolean
      available_trainings_exists: boolean
      comments_exists: boolean
      outgoing_relationships_exists: boolean
      incoming_relationships_exists: boolean
      files_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      activities_exists: boolean
    }

    export interface PublicMeeting {
      // columns
      id: string
      title: string
      description?: string | null
      start_time: string
      end_time?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      is_public: boolean
      completion_status: string
      has_protocol: boolean
      has_report: boolean
      // relations
      institutions?: Institution[]
      types?: Type[]
      agenda_items?: AgendaItem[]
      comments?: Comment[]
      commentable?: PublicMeeting
      files?: SharepointFile[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      tasks?: Task[]
      activities?: Activity[]
      // counts
      institutions_count: number
      types_count: number
      agenda_items_count: number
      comments_count: number
      files_count: number
      fileable_files_count: number
      available_files_count: number
      tasks_count: number
      activities_count: number
      // exists
      institutions_exists: boolean
      types_exists: boolean
      agenda_items_exists: boolean
      comments_exists: boolean
      files_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      tasks_exists: boolean
      activities_exists: boolean
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
      deleted_at?: string | null
      // relations
      approvable?: Approval
      user?: User
      activities?: Activity[]
      // counts
      activities_count: number
      // exists
      user_exists: boolean
      activities_exists: boolean
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
      microsoft_token?: string | null
      updated_at: string
      created_at: string
      profile_photo_path?: string | null
      deleted_at?: string | null
      name_was_changed?: boolean
      // mutators
      has_password: unknown
      translations: unknown
      // relations
      duties?: Duty[]
      previous_duties?: Duty[]
      current_duties?: Duty[]
      dutiables?: Dutiable[]
      tasks?: Task[]
      reservations?: Reservation[]
      memberships?: Membership[]
      trainings?: Training[]
      available_trainings_through_user?: Training[]
      push_subscriptions?: PushSubscription[]
      roles?: Role[]
      permissions?: Permission[]
      activities?: Activity[]
      notifications?: DatabaseNotification[]
      // counts
      duties_count: number
      previous_duties_count: number
      current_duties_count: number
      dutiables_count: number
      tasks_count: number
      reservations_count: number
      memberships_count: number
      trainings_count: number
      available_trainings_through_user_count: number
      push_subscriptions_count: number
      roles_count: number
      permissions_count: number
      activities_count: number
      notifications_count: number
      // exists
      duties_exists: boolean
      previous_duties_exists: boolean
      current_duties_exists: boolean
      dutiables_exists: boolean
      tasks_exists: boolean
      reservations_exists: boolean
      memberships_exists: boolean
      trainings_exists: boolean
      available_trainings_through_user_exists: boolean
      push_subscriptions_exists: boolean
      roles_exists: boolean
      permissions_exists: boolean
      activities_exists: boolean
      notifications_exists: boolean
    }

    export interface Meeting {
      // columns
      id: string
      title: string
      description?: string | null
      start_time: string
      end_time?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      is_public: boolean
      completion_status: string
      has_protocol: boolean
      has_report: boolean
      // relations
      agenda_items?: AgendaItem[]
      institutions?: Institution[]
      comments?: Comment[]
      types?: Type[]
      commentable?: Meeting
      files?: SharepointFile[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      tasks?: Task[]
      activities?: Activity[]
      // counts
      agenda_items_count: number
      institutions_count: number
      comments_count: number
      types_count: number
      files_count: number
      fileable_files_count: number
      available_files_count: number
      tasks_count: number
      activities_count: number
      // exists
      agenda_items_exists: boolean
      institutions_exists: boolean
      comments_exists: boolean
      types_exists: boolean
      files_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      tasks_exists: boolean
      activities_exists: boolean
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
      // counts
      tags_count: number
      // exists
      user_exists: boolean
      tenant_exists: boolean
      other_language_news_exists: boolean
      tags_exists: boolean
      content_exists: boolean
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
      content_type?: string | null
      language?: string | null
      summary?: string | null
      anonymous_url?: string | null
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
      institution?: Institution
      // counts
      // exists
      institution_exists: boolean
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
      highlights?: Array<string> | null
      layout: string
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
      // counts
      // exists
      tenant_exists: boolean
      other_language_page_exists: boolean
      category_exists: boolean
      content_exists: boolean
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
      commentable?: Reservation
      comments?: Comment[]
      tasks?: Task[]
      activities?: Activity[]
      // counts
      resources_count: number
      users_count: number
      comments_count: number
      tasks_count: number
      activities_count: number
      // exists
      resources_exists: boolean
      users_exists: boolean
      comments_exists: boolean
      tasks_exists: boolean
      activities_exists: boolean
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
      translations: unknown
      // relations
      reservations?: Reservation[]
      active_reservations?: Reservation[]
      tenant?: Tenant
      category?: ResourceCategory
      media?: Medium[]
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

    export interface Training {
      // columns
      id: string
      name: Array<unknown>
      description: Array<unknown>
      address?: string | null
      meeting_url?: string | null
      image?: string | null
      status: string
      start_time: string
      end_time?: string | null
      organizer_id: string
      institution_id: string
      form_id?: string | null
      max_participants?: number | null
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      trainables?: Trainable[]
      organizer?: User
      users?: User[]
      institution?: Institution
      form?: Form
      tasks?: TrainingTask[]
      programmes?: Programme[]
      activities?: Activity[]
      // counts
      trainables_count: number
      users_count: number
      tasks_count: number
      programmes_count: number
      activities_count: number
      // exists
      trainables_exists: boolean
      organizer_exists: boolean
      users_exists: boolean
      institution_exists: boolean
      form_exists: boolean
      tasks_exists: boolean
      programmes_exists: boolean
      activities_exists: boolean
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
      has_protocol: boolean
      has_report: boolean
      translations: unknown
      // relations
      institutions?: Institution[]
      duties?: Duty[]
      meetings?: Meeting[]
      roles?: Role[]
      descendants?: Type[]
      parent?: Type
      recursive_parent?: Type
      outgoing_relationships?: Relationship[]
      incoming_relationships?: Relationship[]
      files?: SharepointFile[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      activities?: Activity[]
      // counts
      institutions_count: number
      duties_count: number
      meetings_count: number
      roles_count: number
      descendants_count: number
      outgoing_relationships_count: number
      incoming_relationships_count: number
      files_count: number
      fileable_files_count: number
      available_files_count: number
      activities_count: number
      // exists
      institutions_exists: boolean
      duties_exists: boolean
      meetings_exists: boolean
      roles_exists: boolean
      descendants_exists: boolean
      parent_exists: boolean
      recursive_parent_exists: boolean
      outgoing_relationships_exists: boolean
      incoming_relationships_exists: boolean
      files_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      activities_exists: boolean
    }

    export interface TrainingTask {
      // columns
      id: number
      training_id: string
      name: Array<unknown>
      description?: Array<unknown> | null
      due_date?: string | null
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      training?: Training
      // counts
      // exists
      training_exists: boolean
    }

    export interface Calendar {
      // columns
      id: number
      title?: Array<unknown> | null
      description?: Array<unknown> | null
      location?: Array<unknown> | null
      organizer?: Array<unknown> | null
      cto_url?: Array<unknown> | null
      facebook_url?: string | null
      video_url?: string | null
      main_image?: string | null
      is_draft: boolean
      is_all_day: boolean
      is_international: boolean
      date: string
      end_date?: string | null
      category_id?: number | null
      tenant_id: number
      created_at: string
      updated_at: string
      registration_form_id?: number | null
      // mutators
      main_image_url: string
      translations: unknown
      // relations
      tenant?: Tenant
      category?: Category
      media?: Medium[]
      // counts
      media_count: number
      // exists
      tenant_exists: boolean
      category_exists: boolean
      media_exists: boolean
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
      // relations
      user?: User
      // counts
      // exists
      user_exists: boolean
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

    export interface ProgrammeDay {
      // columns
      id: number
      programme_id: number
      title: Array<unknown>
      description?: Array<unknown> | null
      order: number
      start_time: string
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      programme?: Programme
      elements?: ProgrammeElement[]
      sections?: ProgrammeSection[]
      parts?: ProgrammePart[]
      // counts
      elements_count: number
      sections_count: number
      parts_count: number
      // exists
      programme_exists: boolean
      elements_exists: boolean
      sections_exists: boolean
      parts_exists: boolean
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
      deleted_at?: string | null
      // mutators
      formatted_size: string
      file_type_label: string
      // relations
      fileable?: FileableFile
      // counts
      // exists
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
      activities?: Activity[]
      // counts
      activities_count: number
      // exists
      institution_exists: boolean
      user_exists: boolean
      tenant_exists: boolean
      activities_exists: boolean
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
      has_protocol: boolean
      has_report: boolean
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
      available_trainings?: Training[]
      roles?: Role[]
      permissions?: Permission[]
      files?: SharepointFile[]
      fileable_files?: FileableFile[]
      available_files?: FileableFile[]
      activities?: Activity[]
      notifications?: DatabaseNotification[]
      // counts
      dutiables_count: number
      users_count: number
      current_users_count: number
      previous_users_count: number
      types_count: number
      available_trainings_count: number
      roles_count: number
      permissions_count: number
      files_count: number
      fileable_files_count: number
      available_files_count: number
      activities_count: number
      notifications_count: number
      // exists
      dutiables_exists: boolean
      users_exists: boolean
      current_users_exists: boolean
      previous_users_exists: boolean
      types_exists: boolean
      institution_exists: boolean
      institutions_exists: boolean
      available_trainings_exists: boolean
      roles_exists: boolean
      permissions_exists: boolean
      files_exists: boolean
      fileable_files_exists: boolean
      available_files_exists: boolean
      activities_exists: boolean
      notifications_exists: boolean
    }

    const ActionType = {
      Manual: 'manual',
      Approval: 'approval',
      Pickup: 'pickup',
      Return: 'return',
    } as const;

    export type ActionType = typeof ActionType[keyof typeof ActionType]

    const ApprovalDecision = {
      Approved: 'approved',
      Rejected: 'rejected',
      Cancelled: 'cancelled',
    } as const;

    export type ApprovalDecision = typeof ApprovalDecision[keyof typeof ApprovalDecision]

  }
}

