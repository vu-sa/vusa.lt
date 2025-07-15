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
    }

    export interface Calendar {
      // columns
      id: number
      title?: string[] | null
      description?: string[] | null
      location?: string[] | null
      organizer?: string[] | null
      cto_url?: string[] | null
      facebook_url?: string | null
      video_url?: string | null
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
      translations: unknown
      // relations
      tenant?: Tenant
      category?: Category
      media?: Medium[]
    }

    export interface Category {
      // columns
      id: number
      alias?: string | null
      name: string
      description?: string | null
      created_at: string
      updated_at: string
      // relations
      pages?: Page[]
      news?: News[]
      calendars?: Calendar[]
    }

    export interface ChangelogItem {
      // columns
      id: number
      title: string[]
      date: string
      description: string[]
      permission_id?: string | null
      // mutators
      translations: unknown
    }

    export interface Comment {
      // columns
      id: string
      parent_id?: string | null
      comment: string
      decision?: string | null
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
    }

    export interface Content {
      // columns
      id: number
      created_at: string
      updated_at: string
      // relations
      parts?: ContentPart[]
    }

    export interface ContentPart {
      // columns
      id: number
      content_id: number
      type: string
      json_content: string[]
      options?: string[] | null
      order: number
      created_at: string
      updated_at: string
      // mutators
      html: unknown
      // relations
      content?: Content
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
      updated_at?: string
      effective_date?: string | null
      expiration_date?: string | null
      // mutators
      is_in_effect: unknown
      // relations
      institution?: Institution
    }

    export interface Doing {
      // columns
      id: string
      title: string
      drive_item_name?: string | null
      state: unknown
      date: string
      extra_attributes?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // relations
      goals?: Goal[]
      matters?: Matter[]
      types?: Type[]
      users?: User[]
      comments?: Comment[]
      commentable?: Doing
      files?: SharepointFile[]
      tasks?: Task[]
      activities?: Activity[]
    }

    export interface Duty {
      // columns
      id: string
      name?: string[] | null
      description?: string[] | null
      institution_id: string
      order: number
      email?: string | null
      places_to_occupy?: number | null
      contacts_grouping: string
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      translations: unknown
      // relations
      dutiables?: Dutiable[]
      users?: User[]
      current_users?: User[]
      previous_users?: User[]
      types?: Type[]
      institution?: Institution
      institutions?: Institution
      available_trainings?: Training[]
      roles?: Role[]
      permissions?: Permission[]
      activities?: Activity[]
      notifications?: DatabaseNotification[]
    }

    export interface FieldResponse {
      // columns
      id: number
      registration_id: number
      form_field_id: number
      response: string[]
      created_at: string
      updated_at: string
      // relations
      registration?: Registration
      form_field?: FormField
    }

    export interface File {
    }

    export interface Form {
      // columns
      id: string
      name: string[]
      description?: string[] | null
      user_id?: string | null
      tenant_id: number
      path?: string[] | null
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
    }

    export interface FormField {
      // columns
      id: number
      form_id: string
      label: string[]
      description?: string[] | null
      type: string
      subtype?: string | null
      options?: string[] | null
      is_required: boolean
      order: number
      default_value?: string[] | null
      placeholder?: string[] | null
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
    }

    export interface Goal {
      // columns
      id: string
      group_id?: string | null
      tenant_id: number
      title: string
      description?: string | null
      start_date: string
      end_date?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // relations
      matters?: Matter[]
      doings?: Doing[]
      group?: GoalGroup
      tenant?: Tenant
      commentable?: Goal
      comments?: Comment[]
      files?: SharepointFile[]
      tasks?: Task[]
      activities?: Activity[]
    }

    export interface GoalGroup {
      // columns
      id: string
      title: string
      description?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // relations
      goals?: Goal[]
      matters?: Matter[]
      activities?: Activity[]
    }

    export interface Institution {
      // columns
      id: string
      name?: string[] | null
      short_name?: string[] | null
      alias: string
      description?: string[] | null
      address?: string[] | null
      phone?: string | null
      email?: string | null
      website?: string | null
      image_url?: string | null
      logo_url?: string | null
      facebook_url?: string | null
      instagram_url?: string | null
      tenant_id?: number | null
      is_active: boolean
      contacts_layout: string
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      related_institutions: unknown
      maybe_short_name: unknown
      translations: unknown
      // relations
      duties?: Duty[]
      types?: Type[]
      tenant?: Tenant
      documents?: Document[]
      matters?: Matter[]
      meetings?: Meeting[]
      users?: User
      available_trainings?: Training[]
      commentable?: Institution
      comments?: Comment[]
      outgoing_relationships?: Relationship[]
      incoming_relationships?: Relationship[]
      files?: SharepointFile[]
      activities?: Activity[]
    }

    export interface Matter {
      // columns
      id: string
      title: string
      description?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // relations
      institutions?: Institution[]
      institution?: Institution[]
      meetings?: Meeting[]
      doings?: Doing[]
      goals?: Goal[]
      activities?: Activity[]
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
      // relations
      matters?: Matter[]
      agenda_items?: AgendaItem[]
      institutions?: Institution[]
      comments?: Comment[]
      types?: Type[]
      commentable?: Meeting
      files?: SharepointFile[]
      tasks?: Task[]
      activities?: Activity[]
    }

    export interface Membership {
      // columns
      id: string
      name: string[]
      tenant_id: number
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      tenant?: Tenant
      users?: User[]
      available_trainings?: Training[]
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
      extra_attributes?: string[] | null
      created_at?: string
      updated_at?: string
      // relations
      user?: User
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
      image?: unknown | null
      image_author?: string | null
      important: boolean
      tenant_id: number
      publish_time?: string | null
      main_points?: string | null
      read_more?: string | null
      draft?: boolean | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // relations
      user?: User
      tenant?: Tenant
      other_language_news?: News
      tags?: Tag[]
      content?: Content
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
      tenant_id: number
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // relations
      tenant?: Tenant
      category?: Category
      content?: Content
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
    }

    export interface AgendaItem {
      // columns
      id: string
      meeting_id: string
      matter_id?: string | null
      created_at: string
      updated_at: string
      title: string
      description?: string | null
      student_vote?: string | null
      decision?: string | null
      student_benefit?: string | null
      start_time?: string | null
      // relations
      matter?: Matter
      meeting?: Meeting
      activities?: Activity[]
    }

    export interface Doable {
      // columns
      doable_type: string
      doable_id: string
      doing_id: string
      created_at: string
      updated_at: string
      // relations
      doing?: Doing
      user?: User
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
      description?: string[] | null
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
    }

    export interface GoalMatter {
      // columns
      goal_id: string
      matter_id: string
      created_at: string
      updated_at: string
      // relations
      goal?: Goal
      matter?: Matter
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
    }

    export interface Relationshipable {
      // columns
      id: number
      relationship_id: number
      relationshipable_type: string
      relationshipable_id: string
      related_model_id: string
      created_at: string
      updated_at: string
      // relations
      relationshipable?: Relationshipable
      related_model?: Relationshipable
      relationship?: Relationship
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
      approvable: bool
      state_properties: unknown
      // relations
      reservation?: Reservation
      resource?: Resource
      commentable?: ReservationResource
      comments?: Comment[]
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
      meeting?: Meeting
      institution?: Institution
      type?: Type
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
    }

    export interface Programme {
      // columns
      id: number
      title: string[]
      description?: string[] | null
      start_date: string
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      days?: ProgrammeDay[]
      programmable?: Programme
    }

    export interface ProgrammeBlock {
      // columns
      id: number
      programme_section_id: number
      title: string[]
      description?: string[] | null
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      parts?: ProgrammePart[]
    }

    export interface ProgrammeDay {
      // columns
      id: number
      programme_id: number
      title: string[]
      description?: string[] | null
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
    }

    export interface ProgrammePart {
      // columns
      id: number
      title: string[]
      description?: string[] | null
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
    }

    export interface ProgrammeSection {
      // columns
      id: number
      title: string[]
      duration: number
      start_time?: string | null
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      programme_days?: ProgrammeDay[]
      blocks?: ProgrammeBlock[]
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
    }

    export interface Resource {
      // columns
      id: string
      identifier?: string | null
      name: string[]
      description?: string[] | null
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
    }

    export interface ResourceCategory {
      // columns
      id: number
      name: string[]
      description?: string[] | null
      icon?: string | null
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      resources?: Resource[]
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
    }

    export interface StudyProgram {
      // columns
      id: string
      name: string[]
      degree: string
      tenant_id: number
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      tenant?: Tenant
      dutiables?: Dutiable[]
    }

    export interface Tag {
      // columns
      id: number
      alias?: string | null
      name: Record<string, string>
      description?: Record<string, string> | null
      created_at: string
      updated_at: string
      // relationships
      news?: News[]
    }

    export interface Task {
      // columns
      id: string
      name: string
      description?: string | null
      due_date?: string | null
      taskable_type: string
      taskable_id: string
      completed_at?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // relations
      taskable?: Task
      users?: User[]
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
    }

    export interface Training {
      // columns
      id: string
      name: string[]
      description: string[]
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
    }

    export interface TrainingTask {
      // columns
      id: number
      training_id: string
      name: string[]
      description?: string[] | null
      due_date?: string | null
      created_at: string
      updated_at: string
      // mutators
      translations: unknown
      // relations
      training?: Training
    }

    export interface Type {
      // columns
      id: number
      parent_id?: number | null
      title?: string[] | null
      description?: string[] | null
      model_type?: string | null
      slug?: string | null
      created_at: string
      updated_at: string
      deleted_at?: string | null
      // mutators
      translations: unknown
      // relations
      institutions?: Institution[]
      duties?: Duty[]
      doings?: Doing[]
      meetings?: Meeting[]
      roles?: Role[]
      descendants?: Type[]
      parent?: Type
      recursive_parent?: Type
      outgoing_relationships?: Relationship[]
      incoming_relationships?: Relationship[]
      files?: SharepointFile[]
      activities?: Activity[]
    }

    export interface Typeable {
      // relations
      type?: Type
      typeable?: Typeable
    }

    export interface User {
      // columns
      id: string
      email: string
      phone?: string | null
      facebook_url?: string | null
      name: string
      pronouns?: string[] | null
      show_pronouns: boolean
      password?: string | null
      is_active: boolean
      email_verified_at?: string | null
      remember_token?: string | null
      last_action?: string | null
      last_changelog_check?: string | null
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
      doings?: Doing[]
      duties?: Duty[]
      previous_duties?: Duty[]
      current_duties?: Duty[]
      dutiables?: Dutiable[]
      tasks?: Task[]
      reservations?: Reservation[]
      memberships?: Membership[]
      trainings?: Training[]
      available_trainings_through_user?: Training[]
      roles?: Role[]
      permissions?: Permission[]
      activities?: Activity[]
      notifications?: DatabaseNotification[]
    }

  }
}

