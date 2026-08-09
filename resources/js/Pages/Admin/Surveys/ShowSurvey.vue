<template>
  <PageContent :title="survey.name?.lt" :heading-icon="ClipboardList">
    <template #above-header>
      <div class="flex flex-wrap items-center gap-2">
        <Badge :variant="statusVariant">{{ statusLabel }}</Badge>
        <span v-if="survey.tenant" class="text-sm text-zinc-500">{{ survey.tenant.shortname }}</span>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Questions -->
      <Card>
        <CardHeader>
          <CardTitle>{{ $t('surveys.sections.questions') }}</CardTitle>
        </CardHeader>
        <CardContent>
          <SurveyQuestionBuilder
            :survey-id="survey.id"
            :initial-questions="survey.questions"
            :question-types="questionTypes"
            :question-templates="questionTemplates"
            :editable="survey.is_editable"
          />
        </CardContent>
      </Card>

      <!-- Approval -->
      <Card>
        <CardHeader>
          <CardTitle>{{ $t('surveys.sections.approval') }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <Button
            v-if="survey.status === 'draft' || survey.status === 'rejected'"
            :disabled="survey.questions.length === 0"
            @click="requestApproval"
          >
            <Send class="mr-1 size-4" />
            {{ $t('surveys.actions.request_approval') }}
          </Button>

          <ApprovalActions
            v-if="survey.status === 'pending_approval' && survey.can_approve"
            :approvable-type="approvableType"
            :approvable-id="survey.id"
          />

          <ApprovalTimeline v-if="survey.approvals.length" :approvals="survey.approvals" />
        </CardContent>
      </Card>

      <!-- LimeSurvey -->
      <Card>
        <CardHeader>
          <CardTitle>{{ $t('surveys.sections.limesurvey') }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <p v-if="!limeSurveyConfigured" class="text-sm text-amber-700 dark:text-amber-400">
            {{ $t('surveys.limesurvey.not_configured') }}
          </p>

          <p v-else-if="!survey.is_published" class="text-sm text-zinc-500">
            {{ $t('surveys.limesurvey.not_published') }}
          </p>

          <template v-else>
            <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-zinc-500">{{ $t('surveys.limesurvey.survey_id') }}</dt>
                <dd class="font-mono">{{ survey.limesurvey_survey_id }}</dd>
              </div>
              <div>
                <dt class="text-zinc-500">{{ $t('surveys.limesurvey.public_url') }}</dt>
                <dd>
                  <a
                    :href="survey.limesurvey_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-vusa-red underline"
                  >
                    {{ $t('surveys.actions.open_survey') }}
                  </a>
                </dd>
              </div>
              <div v-if="survey.response_stats">
                <dt class="text-zinc-500">{{ $t('surveys.limesurvey.completed') }}</dt>
                <dd class="text-lg font-semibold">{{ survey.response_stats.completed }}</dd>
              </div>
              <div v-if="survey.response_stats">
                <dt class="text-zinc-500">{{ $t('surveys.limesurvey.incomplete') }}</dt>
                <dd class="text-lg font-semibold">{{ survey.response_stats.incomplete }}</dd>
              </div>
              <div v-if="survey.stats_synced_at">
                <dt class="text-zinc-500">{{ $t('surveys.limesurvey.last_synced') }}</dt>
                <dd>{{ survey.stats_synced_at }}</dd>
              </div>
            </dl>
          </template>

          <p v-if="survey.sync_error_message" class="text-sm text-red-600">
            {{ survey.sync_error_message }}
          </p>

          <Button
            v-if="limeSurveyConfigured && (survey.is_published || survey.status === 'approved')"
            variant="outline"
            @click="resync"
          >
            <RefreshCw class="mr-1 size-4" />
            {{ survey.is_published ? $t('surveys.actions.resync') : $t('surveys.actions.retry_publish') }}
          </Button>
        </CardContent>
      </Card>
    </div>
  </PageContent>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { router } from '@inertiajs/vue3';
import { ClipboardList, RefreshCw, Send } from 'lucide-vue-next';
import { computed } from 'vue';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import SurveyQuestionBuilder from '@/Components/Surveys/SurveyQuestionBuilder.vue';
import ApprovalActions from '@/Features/Admin/Approvals/ApprovalActions.vue';
import ApprovalTimeline from '@/Features/Admin/Approvals/ApprovalTimeline.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

const props = defineProps<{
  survey: Record<string, any>;
  questionTypes: { value: string; label: string; hasOptions: boolean }[];
  questionTemplates: Record<string, any>[];
  limeSurveyConfigured: boolean;
}>();

// ApprovalController validates this against ModelEnum and resolves the class itself.
const approvableType = 'survey';

const statusLabel = computed(() => $t(`surveys.status.${props.survey.status}`));

const statusVariant = computed(() => {
  if (props.survey.status === 'rejected') {
    return 'destructive';
  }

  return props.survey.status === 'active' || props.survey.status === 'approved' ? 'default' : 'secondary';
});

const requestApproval = () => {
  router.post(route('surveys.requestApproval', props.survey.id), {}, { preserveScroll: true });
};

const resync = () => {
  router.post(route('surveys.resync', props.survey.id), {}, { preserveScroll: true });
};
</script>
