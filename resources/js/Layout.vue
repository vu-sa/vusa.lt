<template>
  <n-theme-editor v-if="$page.props.app.env == 'local'">
    <n-config-provider>
      <n-message-provider>
        <n-notification-provider>
          <n-dialog-provider>
            <slot></slot>
          </n-dialog-provider>
        </n-notification-provider>
      </n-message-provider>
    </n-config-provider>
  </n-theme-editor>

  <template v-else>
    <!-- <n-config-provider> -->
    <n-message-provider>
      <n-notification-provider>
        <n-dialog-provider>
          <slot></slot>
        </n-dialog-provider>
      </n-notification-provider>
    </n-message-provider>
    <!-- </n-config-provider> -->
  </template>
</template>

<script setup>
import {
  NMessageProvider,
  NNotificationProvider,
  NDialogProvider,
  NThemeEditor,
  NConfigProvider,
  darkTheme,
} from "naive-ui";
</script>
