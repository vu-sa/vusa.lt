<template>
	<n-theme-editor>
		<n-message-provider>
			<n-notification-provider>
				<n-dialog-provider>
					<slot></slot>
				</n-dialog-provider>
			</n-notification-provider>
		</n-message-provider>
	</n-theme-editor>
</template>

<script setup>
import { NMessageProvider, NNotificationProvider, NDialogProvider, NThemeEditor } from "naive-ui";
</script>