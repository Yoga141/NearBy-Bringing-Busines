<script setup lang="ts">
import { useUiStore } from '@/stores/ui'
import EditUmkmModal from './modals/EditUmkmModal.vue'
import ManageUserModal from './modals/ManageUserModal.vue'
import PurgeUserModal from './modals/PurgeUserModal.vue'
import SubmissionDetailModal from './modals/SubmissionDetailModal.vue'
import ReplyReviewModal from './modals/ReplyReviewModal.vue'

const ui = useUiStore()
</script>

<template>
  <EditUmkmModal v-if="ui.modalKind === 'editUmkm'" />
  <ManageUserModal v-else-if="ui.modalKind === 'manageUser'" />
  <PurgeUserModal v-else-if="ui.modalKind === 'purgeUser'" />
  <SubmissionDetailModal v-else-if="ui.modalKind === 'submissionDetail'" />
  <ReplyReviewModal v-else-if="ui.modalKind === 'replyReview'" />
</template>
