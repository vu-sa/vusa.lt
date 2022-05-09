<template>
  <n-theme-editor v-if="$page.props.app.env == 'local'">
    <n-config-provider :theme-overrides="themeOverrides">
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
    <n-config-provider :theme-overrides="themeOverrides">
      <n-message-provider>
        <n-notification-provider>
          <n-dialog-provider>
            <slot></slot>
          </n-dialog-provider>
        </n-notification-provider>
      </n-message-provider>
    </n-config-provider>
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

const themeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
  },
};
</script>
