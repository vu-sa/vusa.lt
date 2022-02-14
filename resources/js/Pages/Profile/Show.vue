<template>
  <app-layout title="Profile">
    <div>
      <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div v-if="$page.props.jetstream.canUpdateProfileInformation">
          <update-profile-information-form :user="$page.props.user" />

          <jet-section-border />
        </div>

        <!-- <div v-if="$page.props.jetstream.canUpdatePassword">
          <update-password-form class="mt-10 sm:mt-0" />

          <jet-section-border />
        </div>

        <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
          <two-factor-authentication-form class="mt-10 sm:mt-0" />

          <jet-section-border />
        </div> -->

        <logout-other-browser-sessions-form
          :sessions="sessions"
          class="mt-10 sm:mt-0"
        />

        <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
          <jet-section-border />
        </template>
      </div>
    </div>
  </app-layout>
</template>

<script setup>
import AppLayout from "@/Layouts/AdminLayout.vue";
import JetSectionBorder from "@/Jetstream/SectionBorder.vue";
import LogoutOtherBrowserSessionsForm from "@/Pages/Admin/Partials/LogoutOtherBrowserSessionsForm.vue";
// import TwoFactorAuthenticationForm from "@/Pages/Admin/Partials/TwoFactorAuthenticationForm.vue";
// import UpdatePasswordForm from "@/Pages/Admin/Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "@/Pages/Admin/Partials/UpdateProfileInformationForm.vue";

const props = defineProps(['sessions'])
</script>
