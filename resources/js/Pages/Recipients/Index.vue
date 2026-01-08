<template>
  <div>
    <Head title="Recipients" />
    <h1 id="primary-heading" class="sr-only">Recipients</h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900 dark:text-white">Recipients</h1>
        <p class="mt-2 text-sm text-grey-700 dark:text-grey-200">
          A list of all the recipients {{ search ? 'found for your search' : 'in your account' }}
          <button @click="moreInfoOpen = !moreInfoOpen">
            <InformationCircleIcon
              class="h-6 w-6 inline-block cursor-pointer text-grey-500 dark:text-grey-200"
              title="Click for more information"
            />
          </button>
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button
          type="button"
          @click="openAddRecipientModal"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 font-bold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:w-auto"
        >
          Add Recipient
        </button>
      </div>
    </div>

    <vue-good-table
      v-if="rows.length"
      v-on:sort-change="debounceToolips"
      :columns="columns"
      :rows="rows"
      :sort-options="{
        enabled: true,
        initialSortBy: { field: 'created_at', type: 'desc' },
      }"
      styleClass="vgt-table"
    >
      <template #table-column="props">
        <span v-if="props.column.label == 'Key'">
          {{ props.column.label }}
          <span
            class="tooltip outline-none"
            :data-tippy-content="`Use this to attach recipients to new aliases as they are created e.g. alias+key@${$page.props.user.username}.anonaddy.com. You can attach multiple recipients by doing alias+2.3.4@${$page.props.user.username}.anonaddy.com. Separating each key by a full stop.`"
          >
            <icon name="info" class="inline-block w-4 h-4 text-grey-300 fill-current" />
          </span>
        </span>
        <span v-else-if="props.column.label == 'Alias Count'">
          {{ props.column.label }}
          <span
            class="tooltip outline-none"
            data-tippy-content="This shows the total number of aliases that either the recipient is directly assigned to, or where the recipient is set as the default for a custom domain or username."
          >
            <icon name="info" class="inline-block w-4 h-4 text-grey-300 fill-current" />
          </span>
        </span>
        <span v-else>
          {{ props.column.label }}
        </span>
      </template>
      <template #table-row="props">
        <span
          v-if="props.column.field == 'created_at'"
          class="tooltip outline-none cursor-default text-sm text-grey-500 dark:text-grey-300"
          :data-tippy-content="$filters.formatDate(rows[props.row.originalIndex].created_at)"
          >{{ $filters.timeAgo(props.row.created_at) }}
        </span>
        <span v-else-if="props.column.field == 'key'" class="text-grey-500 dark:text-grey-300">
          {{ props.row.key }}
        </span>
        <span v-else-if="props.column.field == 'email'">
          <button
            class="tooltip outline-none font-medium text-grey-700 dark:text-grey-200"
            data-tippy-content="Click to copy"
            @click="clipboard(rows[props.row.originalIndex].email)"
          >
            {{ $filters.truncate(props.row.email, 30) }}
          </button>

          <span
            v-if="isDefault(props.row.id)"
            class="ml-3 py-1 px-2 text-sm bg-yellow-200 text-yellow-900 rounded-full tooltip"
            data-tippy-content="This is your account's default recipient"
            >default</span
          >

          <span
            v-else-if="props.row.email_verified_at"
            class="block text-grey-400 text-sm py-1 dark:text-grey-300"
          >
            <button @click="openMakeDefaultModal(props.row)">Make Default</button>
          </span>
        </span>
        <span v-else-if="props.column.field === 'aliases'">
          <loader v-if="aliasCountLoading" />
          <span
            v-else-if="isDefault(props.row.id) && props.row.aliases_count > 0"
            class="text-grey-500"
          >
            <Link
              :href="route('aliases.index', { recipient: props.row.id })"
              as="button"
              type="button"
              data-tippy-content="Click to view the aliases using your default recipient"
              class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-500 font-medium tooltip"
              >{{ props.row.aliases_count }}</Link
            >
          </span>
          <span v-else-if="props.row.aliases_count" class="text-grey-500">
            <Link
              :href="route('aliases.index', { recipient: props.row.id })"
              as="button"
              type="button"
              data-tippy-content="Click to view the aliases using this recipient"
              class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-500 font-medium tooltip"
              >{{ props.row.aliases_count }}</Link
            >
          </span>
          <span v-else class="text-grey-500 dark:text-grey-300">0</span>
        </span>
        <span v-else-if="props.column.field === 'should_encrypt'">
          <span v-if="props.row.fingerprint" class="flex">
            <Toggle
              v-model="rows[props.row.originalIndex].should_encrypt"
              @on="turnOnEncryption(props.row.id)"
              @off="turnOffEncryption(props.row.id)"
            />
            <button
              @click="clipboard(props.row.fingerprint)"
              class="tooltip"
              :data-tippy-content="props.row.fingerprint"
              aria-label="Copy fingerprint"
            >
              <icon name="fingerprint" class="block w-6 h-6 text-grey-300 fill-current mx-2" />
            </button>
            <button
              @click="openDeleteRecipientKeyModal(props.row)"
              class="tooltip"
              data-tippy-content="Remove public key"
              aria-label="Remove public key"
            >
              <icon name="delete" class="block w-6 h-6 text-grey-300 fill-current" />
            </button>
          </span>
          <button
            v-else
            @click="openRecipientKeyModal(props.row)"
            class="text-sm text-grey-500 dark:text-grey-300 rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Add PGP key
          </button>
        </span>
        <span v-else-if="props.column.field === 'email_verified_at'">
          <span
            name="check"
            v-if="props.row.email_verified_at"
            :data-tippy-content="
              $filters.formatDate(rows[props.row.originalIndex].email_verified_at)
            "
            class="tooltip py-1 px-2 bg-green-100 text-green-800 rounded-full text-xs font-semibold leading-5"
          >
            verified
          </span>
          <button
            v-else
            @click="resendVerification(props.row.id)"
            class="text-sm disabled:cursor-not-allowed rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            :disabled="resendVerificationLoading"
          >
            Resend email
          </button>
        </span>
        <span v-else class="outline-none whitespace-nowrap" tabindex="-1">
          <Link
            :href="route('recipients.edit', props.row.id)"
            as="button"
            type="button"
            class="text-indigo-500 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-500 font-medium"
            >Edit<span class="sr-only">, {{ props.row.email }}</span></Link
          >
          <button
            v-if="!isDefault(props.row.id)"
            @click="openDeleteModal(props.row)"
            as="button"
            type="button"
            class="text-indigo-500 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-500 font-medium ml-4"
          >
            Delete<span class="sr-only">, {{ props.row.email }}</span>
          </button>
        </span>
      </template>
    </vue-good-table>

    <div v-else class="text-center">
      <InboxArrowDownIcon class="mx-auto h-16 w-16 text-grey-400 dark:text-grey-200" />
      <h3 class="mt-2 text-lg font-medium text-grey-900 dark:text-white">
        No Recipients found for that search
      </h3>
      <p class="mt-1 text-md text-grey-500 dark:text-grey-200">
        Try entering a different search term.
      </p>
      <div class="mt-6">
        <Link
          :href="route('recipients.index')"
          type="button"
          class="inline-flex items-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 text-sm font-medium shadow-sm focus:outline-none"
        >
          View All Recipients
        </Link>
      </div>
    </div>

    <Modal :open="addRecipientModalOpen" @close="addRecipientModalOpen = false">
      <template v-slot:title> Add new recipient </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Enter the individual email of the new recipient you'd like to add. This is where your
          aliases will <b>forward messages to</b>.
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          You will receive an email with a verification link that will <b>expire in one hour</b>,
          you can click "Resend email" to get a new one.
        </p>
        <div class="mt-6">
          <p v-show="errors.newRecipient" class="mb-3 text-red-500 text-sm">
            {{ errors.newRecipient }}
          </p>
          <input
            v-model="newRecipient"
            type="email"
            class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 mb-6 dark:bg-white/5 dark:text-white"
            :class="errors.newRecipient ? 'ring-red-500' : ''"
            placeholder="johndoe@example.com"
            autofocus
          />
          <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
            <button
              @click="validateNewRecipient"
              class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
              :disabled="addRecipientLoading"
            >
              Add Recipient
              <loader v-if="addRecipientLoading" />
            </button>
            <button
              @click="addRecipientModalOpen = false"
              class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Cancel
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <Modal :open="addRecipientKeyModalOpen" @close="closeRecipientKeyModal">
      <template v-slot:title> Add Public GPG Key </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Enter your <b>PUBLIC</b> key data in the text area below.
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Make sure to remove <b>Comment:</b> and <b>Version:</b>
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          ElGamal keys are
          <a
            href="https://sequoia-pgp.org/status/#public-key-algorithms"
            class="text-indigo-700 dark:text-indigo-400"
            target="_blank"
            rel="nofollow noreferrer noopener"
            >not currently supported</a
          >.
        </p>
        <div class="mt-6">
          <p v-show="errors.recipientKey" class="mb-3 text-red-500 text-sm">
            {{ errors.recipientKey }}
          </p>
          <textarea
            v-model="recipientKey"
            class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 mb-6 dark:bg-white/5 dark:text-white"
            :class="errors.recipientKey ? 'ring-red-500' : ''"
            placeholder="Begins with '-----BEGIN PGP PUBLIC KEY BLOCK-----'"
            rows="10"
            autofocus
          >
          </textarea>
          <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
            <button
              type="button"
              @click="validateRecipientKey"
              class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
              :disabled="addRecipientKeyLoading"
            >
              Add Key
              <loader v-if="addRecipientKeyLoading" />
            </button>
            <button
              @click="closeRecipientKeyModal"
              class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Cancel
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <Modal :open="deleteRecipientKeyModalOpen" @close="closeDeleteRecipientKeyModal">
      <template v-slot:title> Remove recipient public key </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Are you sure you want to remove the public key for this recipient? It will also be removed
          from any other recipients using the same key.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="deleteRecipientKey(recipientKeyToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="deleteRecipientKeyLoading"
          >
            Remove public key
            <loader v-if="deleteRecipientLoading" />
          </button>
          <button
            @click="closeDeleteRecipientKeyModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="deleteRecipientModalOpen" @close="closeDeleteModal">
      <template v-slot:title> Delete recipient </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Are you sure you want to delete this recipient?
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="deleteRecipient(recipientToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="deleteRecipientLoading"
          >
            Delete recipient
            <loader v-if="deleteRecipientLoading" />
          </button>
          <button
            @click="closeDeleteModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="makeDefaultModalOpen" @close="closeMakeDefaultModal">
      <template v-slot:title> Make default recipient</template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          The default recipient for your account is used for all general email notifications.
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          It is also used for any aliases that do not have any specific recipients set.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="makeDefaultRecipient(recipientToMakeDefault)"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="makeDefaultLoading"
          >
            Make default recipient
            <loader v-if="makeDefaultLoading" />
          </button>
          <button
            @click="closeMakeDefaultModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="moreInfoOpen" @close="moreInfoOpen = false">
      <template v-slot:title> More information </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          This page shows all of the recipients in your account, these are your real email addresses
          where emails can be forwarded to.
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          You must verify each recipient before you can forwarded emails to it.
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          To update your account's default recipient email address click "Make Default" next to that
          recipient.
        </p>

        <div class="mt-6 flex flex-col sm:flex-row">
          <button
            @click="moreInfoOpen = false"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Close
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import Modal from '../../Components/Modal.vue'
import Toggle from '../../Components/Toggle.vue'
import { roundArrow } from 'tippy.js'
import tippy from 'tippy.js'
import { VueGoodTable } from 'vue-good-table-next'
import { notify } from '@kyvg/vue3-notification'
import { InformationCircleIcon, InboxArrowDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  initialRows: {
    type: Array,
    required: true,
  },
  search: {
    type: String,
  },
})

const defaultRecipientId = ref(usePage().props.user.default_recipient_id)

const rows = ref(props.initialRows)

const defaultRecipient = ref({})
const newRecipient = ref('')
const recipientKey = ref('')
const aliasCountLoading = ref(true)
const addRecipientLoading = ref(false)
const addRecipientModalOpen = ref(false)
const recipientToDelete = ref(null)
const recipientKeyToDelete = ref(null)
const deleteRecipientLoading = ref(false)
const deleteRecipientModalOpen = ref(false)
const deleteRecipientKeyLoading = ref(false)
const deleteRecipientKeyModalOpen = ref(false)
const addRecipientKeyLoading = ref(false)
const addRecipientKeyModalOpen = ref(false)
const makeDefaultLoading = ref(false)
const makeDefaultModalOpen = ref(false)
const recipientToMakeDefault = ref(null)
const moreInfoOpen = ref(false)
const recipientToAddKey = ref({})
const resendVerificationLoading = ref(false)
const tippyInstance = ref(null)
const errors = ref({})

const columns = [
  {
    label: 'Created',
    field: 'created_at',
    globalSearchDisabled: true,
  },
  {
    label: 'Key',
    field: 'key',
    sortable: false,
    type: 'number',
  },
  {
    label: 'Email',
    field: 'email',
  },
  {
    label: 'Alias Count',
    field: 'aliases',
    sortable: false,
    globalSearchDisabled: true,
  },
  {
    label: 'Encryption',
    field: 'should_encrypt',
    type: 'boolean',
    globalSearchDisabled: true,
    sortable: false,
  },
  {
    label: 'Verified',
    field: 'email_verified_at',
    globalSearchDisabled: true,
  },
  {
    label: '',
    field: 'actions',
    sortable: false,
    globalSearchDisabled: true,
  },
]

onMounted(() => {
  defaultRecipient.value = _.find(rows.value, ['id', defaultRecipientId.value])

  // Prevent being called when a search returns no results
  if (rows.value.length) {
    axios
      .post(
        route('recipients.alias_count'),
        JSON.stringify({
          ids: _.map(rows.value, row => row.id),
        }),
        {
          headers: { 'Content-Type': 'application/json' },
        },
      )
      .then(response => {
        Object.entries(response.data.count).forEach(([k, v]) => {
          //rows.value[k].aliases_count = v.aliases_count
          rows.value[k].aliases_count =
            v.aliases_count +
            v.domain_aliases_using_as_default_count +
            v.username_aliases_using_as_default_count
        })

        aliasCountLoading.value = false
      })
  }
})

const isDefault = id => {
  return defaultRecipientId.value === id
}

const addNewRecipient = () => {
  addRecipientLoading.value = true

  axios
    .post(
      '/api/v1/recipients',
      JSON.stringify({
        email: newRecipient.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(({ data }) => {
      addRecipientLoading.value = false
      data.data.key = rows.value.length + 1
      rows.value.push(data.data)
      newRecipient.value = ''
      addRecipientModalOpen.value = false
      successMessage('Recipient added and verification email sent')
    })
    .catch(error => {
      addRecipientLoading.value = false
      if (error.response.status === 403) {
        errorMessage(error.response.data)
      } else if (error.response.status === 422) {
        errorMessage(error.response.data.errors.email[0])
      } else if (error.response.status === 429) {
        errorMessage('You are making too many requests')
      } else {
        errorMessage()
      }
    })
}

const makeDefaultRecipient = recipient => {
  makeDefaultLoading.value = true

  axios
    .post(
      `/settings/default-recipient`,
      JSON.stringify({
        id: recipient.id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      makeDefaultLoading.value = false
      defaultRecipientId.value = recipient.id
      debounceToolips()
      closeMakeDefaultModal()
      successMessage('Default recipient updated')
    })
    .catch(error => {
      closeMakeDefaultModal()
      makeDefaultLoading.value = false
      if (error.response.data.message) {
        errorMessage(error.response.data.message)
      } else {
        errorMessage()
      }
    })
}

const deleteRecipient = recipient => {
  deleteRecipientLoading.value = true

  axios
    .delete(`/api/v1/recipients/${recipient.id}`)
    .then(response => {
      recipient.should_encrypt = false
      recipient.fingerprint = null

      rows.value = _.reject(rows.value, row => row.id === recipient.id)
      deleteRecipientModalOpen.value = false
      deleteRecipientLoading.value = false
    })
    .catch(error => {
      errorMessage()
      deleteRecipientLoading.value = false
      deleteRecipientModalOpen.value = false
    })
}

const addRecipientKey = () => {
  addRecipientKeyLoading.value = true

  axios
    .patch(
      `/api/v1/recipient-keys/${recipientToAddKey.value.id}`,
      JSON.stringify({
        key_data: recipientKey.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(({ data }) => {
      addRecipientKeyLoading.value = false

      let recipient = _.find(rows.value, ['id', recipientToAddKey.value.id])
      recipient.should_encrypt = data.data.should_encrypt
      recipient.fingerprint = data.data.fingerprint

      recipientKey.value = ''
      addRecipientKeyModalOpen.value = false
      debounceToolips()
      successMessage(
        `Key Successfully Added for ${recipientToAddKey.value.email}. Make sure to check the fingerprint is correct!`,
      )
    })
    .catch(error => {
      addRecipientKeyLoading.value = false
      if (error.response !== undefined) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
}

const deleteRecipientKey = recipient => {
  deleteRecipientKeyLoading.value = true

  axios
    .delete(`/api/v1/recipient-keys/${recipient.id}`)
    .then(response => {
      recipient.should_encrypt = false
      recipient.fingerprint = null

      deleteRecipientKeyModalOpen.value = false
      deleteRecipientKeyLoading.value = false
    })
    .catch(error => {
      if (error.response !== undefined) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
      deleteRecipientKeyLoading.value = false
      deleteRecipientKeyModalOpen.value = false
    })
}

const turnOnEncryption = id => {
  axios
    .post(
      `/api/v1/encrypted-recipients`,
      JSON.stringify({
        id: id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      //
    })
    .catch(error => {
      errorMessage()
    })
}

const turnOffEncryption = id => {
  axios
    .delete(`/api/v1/encrypted-recipients/${id}`)
    .then(response => {
      //
    })
    .catch(error => {
      errorMessage()
    })
}

const resendVerification = id => {
  resendVerificationLoading.value = true

  axios
    .post(
      '/api/v1/recipients/email/resend',
      JSON.stringify({
        recipient_id: id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(({ data }) => {
      resendVerificationLoading.value = false
      successMessage('Verification email resent')
    })
    .catch(error => {
      resendVerificationLoading.value = false
      if (error.response.status === 429) {
        errorMessage('You can only resend the email once per minute')
      } else {
        errorMessage()
      }
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

const debounceToolips = _.debounce(function () {
  addTooltips()
}, 50)

const openAddRecipientModal = () => {
  errors.value = {}
  newRecipient.value = ''
  addRecipientModalOpen.value = true
}

const openDeleteModal = recipient => {
  deleteRecipientModalOpen.value = true
  recipientToDelete.value = recipient
}

const closeDeleteModal = () => {
  deleteRecipientModalOpen.value = false
  recipientToDelete.value = null
}

const openMakeDefaultModal = recipient => {
  makeDefaultModalOpen.value = true
  recipientToMakeDefault.value = recipient
}

const closeMakeDefaultModal = () => {
  makeDefaultModalOpen.value = false
  recipientToMakeDefault.value = null
}

const openDeleteRecipientKeyModal = recipient => {
  deleteRecipientKeyModalOpen.value = true
  recipientKeyToDelete.value = recipient
}

const closeDeleteRecipientKeyModal = () => {
  deleteRecipientKeyModalOpen.value = false
  recipientKeyToDelete.value = null
}

const openRecipientKeyModal = recipient => {
  errors.value = {}
  recipientKey.value = ''
  addRecipientKeyModalOpen.value = true
  recipientToAddKey.value = recipient
}

const closeRecipientKeyModal = () => {
  addRecipientKeyModalOpen.value = false
  recipientToAddKey.value = {}
}

const validateNewRecipient = e => {
  errors.value = {}

  if (!newRecipient.value) {
    errors.value.newRecipient = 'Email required'
  } else if (!validEmail(newRecipient.value)) {
    errors.value.newRecipient = 'Valid Email required'
  }

  if (!errors.value.newRecipient) {
    addNewRecipient()
  }

  e.preventDefault()
}

const validateRecipientKey = e => {
  errors.value = {}

  if (!recipientKey.value) {
    errors.value.recipientKey = 'Key required'
  } else if (!validKey(recipientKey.value)) {
    errors.value.recipientKey = 'Valid Key required'
  }

  if (!errors.value.recipientKey) {
    addRecipientKey()
  }

  e.preventDefault()
}

const validEmail = email => {
  let re =
    /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
  return re.test(email)
}

const validKey = key => {
  let re =
    /-----BEGIN PGP PUBLIC KEY BLOCK-----([A-Za-z0-9+=\/\n]+)-----END PGP PUBLIC KEY BLOCK-----/i
  return re.test(key)
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
