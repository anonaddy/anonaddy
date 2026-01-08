<template>
  <div>
    <Head title="Edit Recipient" />
    <h1 id="primary-heading" class="sr-only">Edit Recipient</h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900 dark:text-white">Edit Recipient</h1>
        <p class="mt-2 text-sm text-grey-700 dark:text-grey-200">
          Make changes to your recipient email address
        </p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 dark:bg-grey-900">
      <div class="space-y-8 divide-y divide-grey-200">
        <div>
          <div class="flex items-center">
            <h3
              class="text-xl font-medium leading-6 text-grey-900 cursor-pointer tooltip dark:text-grey-100"
              data-tippy-content="Click to copy"
              @click="clipboard(recipient.email)"
            >
              {{ recipient.email }}
            </h3>
            <span
              name="check"
              v-if="recipient.email_verified_at"
              :data-tippy-content="$filters.formatDate(recipient.email_verified_at)"
              class="tooltip ml-2 py-1 px-2 bg-green-100 text-green-800 rounded-full text-xs font-semibold leading-5"
            >
              verified
            </span>
            <span
              v-if="defaultRecipientId === recipient.id"
              class="ml-2 py-1 px-2 text-xs bg-yellow-200 text-yellow-900 rounded-full tooltip"
              data-tippy-content="This is your account's default email address"
              >default</span
            >
          </div>
        </div>
        <div class="pt-8">
          <label
            for="can_reply_send"
            class="block font-medium text-grey-700 text-lg pointer-events-none cursor-default dark:text-grey-200"
            >Can Reply/Send from Aliases</label
          >
          <p class="mt-1 text-base text-grey-700 dark:text-grey-200">
            Toggle this option to determine whether this recipient is allowed to reply and send from
            your aliases. When set to off this recipient will not be able to reply or send from your
            aliases and you will be notified when an attempt is made.
          </p>
          <Toggle
            id="can_reply_send"
            class="mt-4"
            v-model="recipient.can_reply_send"
            @on="allowRepliesSends"
            @off="disallowRepliesSends"
          />
        </div>

        <div class="pt-8">
          <label
            for="hide_email_subject"
            class="block font-medium text-grey-700 text-lg pointer-events-none cursor-default dark:text-grey-200"
            >Hide Email Subject</label
          >
          <p class="mt-1 text-base text-grey-700 dark:text-grey-200">
            <span v-if="!recipient.fingerprint"
              >You <b>must add a PGP key before you can use this setting</b>.</span
            >
            Enabling this option will hide and encrypt the email subject using protected headers.
            Many mail clients are able to automatically decrypt and display the subject once the
            email arrives.
          </p>
          <Toggle
            v-if="recipient.fingerprint && !recipient.inline_encryption"
            id="hide_email_subject"
            class="mt-4"
            v-model="recipient.protected_headers"
            @on="turnOnProtectedHeaders"
            @off="turnOffProtectedHeaders"
          />
          <Toggle
            v-else
            id="hide_email_subject"
            class="mt-4 !cursor-not-allowed"
            :title="
              recipient.inline_encryption
                ? 'You need to disable inline encryption before you can enable protected headers (hide subject)'
                : 'You must enable encryption first by adding a PGP key'
            "
            v-model="recipient.protected_headers"
            disabled="disabled"
          />
        </div>

        <div class="pt-8">
          <label
            for="use_inline_encryption"
            class="block font-medium text-grey-700 text-lg pointer-events-none cursor-default dark:text-grey-200"
            >Use PGP/Inline Encryption</label
          >
          <p class="mt-1 text-base text-grey-700 dark:text-grey-200">
            <span v-if="!recipient.fingerprint"
              >You <b>must add a PGP key before you can use this setting</b>.</span
            >
            Enabling this option will use (PGP/Inline) instead of the default PGP/MIME encryption
            for forwarded messages. Please Note: This will <b>ONLY</b> encrypt and forward the plain
            text content. Do not enable this if you wish to receive attachments or message with HTML
            content.
          </p>
          <Toggle
            v-if="recipient.fingerprint && !recipient.protected_headers"
            id="use_inline_encryption"
            class="mt-4"
            v-model="recipient.inline_encryption"
            @on="turnOnInlineEncryption"
            @off="turnOffInlineEncryption"
          />
          <Toggle
            v-else
            id="use_inline_encryption"
            class="mt-4 !cursor-not-allowed"
            :title="
              recipient.protected_headers
                ? 'You need to disable protected headers (hide subject) before you can enable inline encryption'
                : 'You must enable encryption first by adding a PGP key'
            "
            v-model="recipient.inline_encryption"
            disabled="disabled"
          />
        </div>

        <div class="pt-5">
          <span
            class="mt-2 text-sm text-grey-500 tooltip"
            :data-tippy-content="$filters.formatDate(recipient.updated_at)"
            >Last updated {{ $filters.timeAgo(recipient.updated_at) }}.</span
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import Toggle from '../../Components/Toggle.vue'
import { notify } from '@kyvg/vue3-notification'
import { roundArrow } from 'tippy.js'
import tippy from 'tippy.js'

const props = defineProps({
  initialRecipient: {
    type: Object,
    required: true,
  },
})

const recipient = ref(props.initialRecipient)
const defaultRecipientId = ref(usePage().props.user.default_recipient_id)

const tippyInstance = ref(null)

onMounted(() => {
  addTooltips()
})

const allowRepliesSends = () => {
  axios
    .post(
      `/api/v1/allowed-recipients`,
      JSON.stringify({
        id: recipient.value.id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      successMessage('Can reply/send enabled')
    })
    .catch(error => {
      errorMessage()
    })
}

const disallowRepliesSends = () => {
  axios
    .delete(`/api/v1/allowed-recipients/${recipient.value.id}`)
    .then(response => {
      successMessage('Can reply/send disabled')
    })
    .catch(error => {
      errorMessage()
    })
}

const turnOnInlineEncryption = () => {
  axios
    .post(
      `/api/v1/inline-encrypted-recipients`,
      JSON.stringify({
        id: recipient.value.id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      successMessage('Use PGP/Inline enabled')
    })
    .catch(error => {
      if (error.response.status === 422) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
}

const turnOffInlineEncryption = () => {
  axios
    .delete(`/api/v1/inline-encrypted-recipients/${recipient.value.id}`)
    .then(response => {
      successMessage('Use PGP/Inline disabled')
    })
    .catch(error => {
      errorMessage()
    })
}

const turnOnProtectedHeaders = () => {
  axios
    .post(
      `/api/v1/protected-headers-recipients`,
      JSON.stringify({
        id: recipient.value.id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      successMessage('Hide email subject enabled')
    })
    .catch(error => {
      if (error.response.status === 422) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
}

const turnOffProtectedHeaders = () => {
  axios
    .delete(`/api/v1/protected-headers-recipients/${recipient.value.id}`)
    .then(response => {
      successMessage('Hide email subject disabled')
    })
    .catch(error => {
      errorMessage()
    })
}

const addTooltips = () => {
  if (tippyInstance.value) {
    _.each(tippyInstance.value, instance => instance.destroy())
  }

  tippyInstance.value = tippy('.tooltip', {
    arrow: roundArrow,
    allowHTML: true,
  })
}

const clipboard = (str, success, error) => {
  // Needed as v-clipboard doesn't work inside modals!
  navigator.clipboard.writeText(str).then(
    () => {
      successMessage('Copied to clipboard')
    },
    () => {
      errorMessage('Could not copy to clipboard')
    },
  )
}

const successMessage = (text = '') => {
  notify({
    title: 'Success',
    text: text,
    type: 'success',
  })
}

const errorMessage = (text = 'An error has occurred, please try again later') => {
  notify({
    title: 'Error',
    text: text,
    type: 'error',
  })
}
</script>
