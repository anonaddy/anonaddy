<template>
  <div>
    <div class="mb-6 flex flex-col md:flex-row justify-between md:items-center">
      <div class="relative">
        <input
          v-model="search"
          @keyup.esc="search = ''"
          tabindex="0"
          type="text"
          class="w-full md:w-64 appearance-none shadow bg-white text-grey-700 focus:outline-none rounded py-3 pl-3 pr-8"
          placeholder="Search Recipients"
        />
        <icon
          v-if="search"
          @click="search = ''"
          name="close-circle"
          class="absolute right-0 inset-y-0 w-5 h-full text-grey-300 fill-current mr-2 flex items-center cursor-pointer"
        />
        <icon
          v-else
          name="search"
          class="absolute right-0 inset-y-0 w-5 h-full text-grey-300 fill-current pointer-events-none mr-2 flex items-center"
        />
      </div>
      <div class="mt-4 md:mt-0">
        <button
          @click="addRecipientModalOpen = true"
          class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none ml-auto"
        >
          Add Recipient
        </button>
      </div>
    </div>

    <vue-good-table
      v-on:search="debounceToolips"
      :columns="columns"
      :rows="rows"
      :search-options="{
        enabled: true,
        skipDiacritics: true,
        externalQuery: search,
      }"
      :sort-options="{
        enabled: true,
        initialSortBy: { field: 'created_at', type: 'desc' },
      }"
      styleClass="vgt-table"
    >
      <template #emptystate class="flex items-center justify-center h-24 text-lg text-grey-700">
        No recipients found for that search!
      </template>
      <template #table-column="props">
        <span v-if="props.column.label == 'Key'">
          Key
          <span
            class="tooltip outline-none"
            :data-tippy-content="`Use this to attach recipients to new aliases as they are created e.g. alias+key@${domain}. You can attach multiple recipients by doing alias+2.3.4@${domain}. Separating each key by a full stop.`"
          >
            <icon name="info" class="inline-block w-4 h-4 text-grey-300 fill-current" />
          </span>
        </span>
        <span v-else-if="props.column.label == 'Inline Encryption'">
          PGP/Inline
          <span
            class="tooltip outline-none"
            data-tippy-content="Use inline (PGP/Inline) instead of PGP/MIME encryption for forwarded messages. Please Note: This will ONLY encrypt and forward the plain text content! Do not enable this if you wish to receive attachments or messages with HTML content."
          >
            <icon name="info" class="inline-block w-4 h-4 text-grey-300 fill-current" />
          </span>
        </span>
        <span v-else-if="props.column.label == 'Hide Subject'">
          Hide Subject
          <span
            class="tooltip outline-none"
            data-tippy-content="Enabling this setting will hide and encrypt the email subject using protected headers. Many mail clients are able to automatically decrypt and display the subject once the email arrives."
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
          class="tooltip outline-none text-sm"
          :data-tippy-content="$filters.formatDate(rows[props.row.originalIndex].created_at)"
          >{{ $filters.timeAgo(props.row.created_at) }}
        </span>
        <span v-else-if="props.column.field == 'key'">
          {{ props.row.key }}
        </span>
        <span v-else-if="props.column.field == 'email'">
          <span
            class="tooltip cursor-pointer outline-none"
            data-tippy-content="Click to copy"
            v-clipboard="() => rows[props.row.originalIndex].email"
            v-clipboard:success="clipboardSuccess"
            v-clipboard:error="clipboardError"
            >{{ $filters.truncate(props.row.email, 30) }}</span
          >

          <span
            v-if="isDefault(props.row.id)"
            class="ml-3 py-1 px-2 text-sm bg-yellow-200 text-yellow-900 rounded-full tooltip"
            data-tippy-content="The default recipient will be used for all aliases with no other recipients assigned"
          >
            default
          </span>
        </span>
        <span v-else-if="props.column.field === 'aliases'">
          <span
            v-if="props.row.aliases.length"
            class="tooltip outline-none"
            :data-tippy-content="aliasesTooltip(props.row.aliases, isDefault(props.row.id))"
            >{{ $filters.truncate(props.row.aliases[0].email, 40) }}
            <span
              v-if="isDefault(props.row.id) && aliasesUsingDefaultCount > 1"
              class="block text-grey-500 text-sm"
            >
              + {{ aliasesUsingDefaultCount - 1 }}</span
            >
            <span v-else-if="props.row.aliases.length > 1" class="block text-grey-500 text-sm">
              + {{ props.row.aliases.length - 1 }}</span
            >
          </span>
          <span v-else class="block text-grey-500 text-sm">{{ props.row.aliases.length }}</span>
        </span>
        <span
          v-else-if="props.column.field === 'can_reply_send'"
          class="flex justify-center items-center"
        >
          <Toggle
            v-model="rows[props.row.originalIndex].can_reply_send"
            @on="allowRepliesSends(props.row.id)"
            @off="disallowRepliesSends(props.row.id)"
          />
        </span>
        <span v-else-if="props.column.field === 'should_encrypt'">
          <span v-if="props.row.fingerprint" class="flex">
            <Toggle
              v-model="rows[props.row.originalIndex].should_encrypt"
              @on="turnOnEncryption(props.row.id)"
              @off="turnOffEncryption(props.row.id)"
            />
            <icon
              name="fingerprint"
              class="tooltip outline-none cursor-pointer block w-6 h-6 text-grey-300 fill-current mx-2"
              :data-tippy-content="props.row.fingerprint"
              v-clipboard="() => props.row.fingerprint"
              v-clipboard:success="clipboardSuccess"
              v-clipboard:error="clipboardError"
            />
            <icon
              name="delete"
              class="tooltip outline-none cursor-pointer block w-6 h-6 text-grey-300 fill-current"
              @click="openDeleteRecipientKeyModal(props.row)"
              data-tippy-content="Remove public key"
            />
          </span>
          <button
            v-else
            @click="openRecipientKeyModal(props.row)"
            class="focus:outline-none text-sm"
          >
            Add public key
          </button>
        </span>
        <span
          v-else-if="props.column.field === 'inline_encryption'"
          class="flex justify-center items-center"
        >
          <Toggle
            v-if="props.row.fingerprint"
            v-model="rows[props.row.originalIndex].inline_encryption"
            @on="turnOnInlineEncryption(props.row.id)"
            @off="turnOffInlineEncryption(props.row.id)"
          />
        </span>
        <span
          v-else-if="props.column.field === 'protected_headers'"
          class="flex justify-center items-center"
        >
          <Toggle
            v-if="props.row.fingerprint"
            v-model="rows[props.row.originalIndex].protected_headers"
            @on="turnOnProtectedHeaders(props.row.id)"
            @off="turnOffProtectedHeaders(props.row.id)"
          />
        </span>
        <span v-else-if="props.column.field === 'email_verified_at'">
          <span
            name="check"
            v-if="props.row.email_verified_at"
            class="py-1 px-2 bg-green-200 text-green-900 rounded-full text-xs"
          >
            verified
          </span>
          <button
            v-else
            @click="resendVerification(props.row.id)"
            class="focus:outline-none text-sm"
            :class="resendVerificationLoading ? 'cursor-not-allowed' : ''"
            :disabled="resendVerificationLoading"
          >
            Resend email
          </button>
        </span>
        <span v-else class="flex items-center justify-center outline-none" tabindex="-1">
          <icon
            v-if="!isDefault(props.row.id)"
            name="trash"
            class="block w-6 h-6 text-grey-300 fill-current cursor-pointer"
            @click="openDeleteModal(props.row)"
          />
        </span>
      </template>
    </vue-good-table>

    <Modal :open="addRecipientModalOpen" @close="addRecipientModalOpen = false">
      <template v-slot:title> Add new recipient </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Enter the individual email of the new recipient you'd like to add.
        </p>
        <p class="mt-4 text-grey-700">
          You will receive an email with a verification link that will expire in one hour, you can
          click "Resend email" to get a new one.
        </p>
        <div class="mt-6">
          <p v-show="errors.newRecipient" class="mb-3 text-red-500 text-sm">
            {{ errors.newRecipient }}
          </p>
          <input
            v-model="newRecipient"
            type="email"
            class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3 mb-6"
            :class="errors.newRecipient ? 'border-red-500' : ''"
            placeholder="johndoe@example.com"
            autofocus
          />
          <button
            @click="validateNewRecipient"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            :class="addRecipientLoading ? 'cursor-not-allowed' : ''"
            :disabled="addRecipientLoading"
          >
            Add Recipient
            <loader v-if="addRecipientLoading" />
          </button>
          <button
            @click="addRecipientModalOpen = false"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="addRecipientKeyModalOpen" @close="closeRecipientKeyModal">
      <template v-slot:title> Add Public GPG Key </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">Enter your <b>PUBLIC</b> key data in the text area below.</p>
        <p class="mt-4 text-grey-700">Make sure to remove <b>Comment:</b> and <b>Version:</b></p>
        <div class="mt-6">
          <p v-show="errors.recipientKey" class="mb-3 text-red-500 text-sm">
            {{ errors.recipientKey }}
          </p>
          <textarea
            v-model="recipientKey"
            class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3 mb-6"
            :class="errors.recipientKey ? 'border-red-500' : ''"
            placeholder="Begins with '-----BEGIN PGP PUBLIC KEY BLOCK-----'"
            rows="10"
            autofocus
          >
          </textarea>
          <button
            type="button"
            @click="validateRecipientKey"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            :class="addRecipientKeyLoading ? 'cursor-not-allowed' : ''"
            :disabled="addRecipientKeyLoading"
          >
            Add Key
            <loader v-if="addRecipientKeyLoading" />
          </button>
          <button
            @click="closeRecipientKeyModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="deleteRecipientKeyModalOpen" @close="closeDeleteRecipientKeyModal">
      <template v-slot:title> Remove recipient public key </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Are you sure you want to remove the public key for this recipient? It will also be removed
          from any other recipients using the same key.
        </p>
        <div class="mt-6">
          <button
            type="button"
            @click="deleteRecipientKey(recipientKeyToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none"
            :class="deleteRecipientKeyLoading ? 'cursor-not-allowed' : ''"
            :disabled="deleteRecipientKeyLoading"
          >
            Remove public key
            <loader v-if="deleteRecipientLoading" />
          </button>
          <button
            @click="closeDeleteRecipientKeyModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="deleteRecipientModalOpen" @close="closeDeleteModal">
      <template v-slot:title> Delete recipient </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">Are you sure you want to delete this recipient?</p>
        <div class="mt-6">
          <button
            type="button"
            @click="deleteRecipient(recipientToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none"
            :class="deleteRecipientLoading ? 'cursor-not-allowed' : ''"
            :disabled="deleteRecipientLoading"
          >
            Delete recipient
            <loader v-if="deleteRecipientLoading" />
          </button>
          <button
            @click="closeDeleteModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script>
import Modal from './../components/Modal.vue'
import Toggle from './../components/Toggle.vue'
import { roundArrow } from 'tippy.js'
import 'tippy.js/dist/svg-arrow.css'
import 'tippy.js/dist/tippy.css'
import tippy from 'tippy.js'

export default {
  props: {
    user: {
      type: Object,
      required: true,
    },
    initialRecipients: {
      type: Array,
      required: true,
    },
    aliasesUsingDefault: {
      type: Array,
      required: true,
    },
    aliasesUsingDefaultCount: {
      type: Number,
      required: true,
    },
    domain: {
      type: String,
      required: true,
    },
  },
  components: {
    Modal,
    Toggle,
  },
  created() {
    this.defaultRecipient = _.find(this.rows, ['id', this.user.default_recipient_id])
    this.defaultRecipient.aliases = this.defaultRecipient.aliases.concat(this.aliasesUsingDefault)
  },
  data() {
    return {
      defaultRecipient: {},
      newRecipient: '',
      recipientKey: '',
      search: '',
      addRecipientLoading: false,
      addRecipientModalOpen: false,
      recipientToDelete: null,
      recipientKeyToDelete: null,
      deleteRecipientLoading: false,
      deleteRecipientModalOpen: false,
      deleteRecipientKeyLoading: false,
      deleteRecipientKeyModalOpen: false,
      addRecipientKeyLoading: false,
      addRecipientKeyModalOpen: false,
      recipientToAddKey: {},
      resendVerificationLoading: false,
      errors: {},
      columns: [
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
          label: 'Recipient Aliases',
          field: 'aliases',
          sortable: true,
          sortFn: this.sortRecipientAliases,
          globalSearchDisabled: true,
        },
        {
          label: 'Can Reply/Send',
          field: 'can_reply_send',
          type: 'boolean',
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
          label: 'Inline Encryption',
          field: 'inline_encryption',
          type: 'boolean',
          globalSearchDisabled: true,
          sortable: false,
        },
        {
          label: 'Hide Subject',
          field: 'protected_headers',
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
      ],
      rows: this.initialRecipients,
      tippyInstance: null,
    }
  },
  watch: {
    addRecipientKeyModalOpen: _.debounce(function () {
      this.addTooltips()
    }, 50),
  },
  methods: {
    addTooltips() {
      if (this.tippyInstance) {
        _.each(this.tippyInstance, instance => instance.destroy())
      }

      this.tippyInstance = tippy('.tooltip', {
        arrow: roundArrow,
        allowHTML: true,
      })
    },
    debounceToolips: _.debounce(function () {
      this.addTooltips()
    }, 50),
    aliasesTooltip(aliases, isDefault) {
      let ellipses =
        aliases.length > 5 || (isDefault && this.aliasesUsingDefaultCount > 5) ? '...' : ''

      return (
        _.reduce(_.take(aliases, 5), (list, alias) => list + `${alias.email}<br>`, '') + ellipses
      )
    },
    isDefault(id) {
      return this.user.default_recipient_id === id
    },
    validateNewRecipient(e) {
      this.errors = {}

      if (!this.newRecipient) {
        this.errors.newRecipient = 'Email required'
      } else if (!this.validEmail(this.newRecipient)) {
        this.errors.newRecipient = 'Valid Email required'
      }

      if (!this.errors.newRecipient) {
        this.addNewRecipient()
      }

      e.preventDefault()
    },
    addNewRecipient() {
      this.addRecipientLoading = true

      axios
        .post(
          '/api/v1/recipients',
          JSON.stringify({
            email: this.newRecipient,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(({ data }) => {
          this.addRecipientLoading = false
          data.data.key = this.rows.length + 1
          this.rows.push(data.data)
          this.newRecipient = ''
          this.addRecipientModalOpen = false
          this.success('Recipient created and verification email sent')
        })
        .catch(error => {
          this.addRecipientLoading = false
          if (error.response.status === 422) {
            this.error(error.response.data.errors.email[0])
          } else if (error.response.status === 429) {
            this.error('You are making too many requests')
          } else {
            this.error()
          }
        })
    },
    resendVerification(id) {
      this.resendVerificationLoading = true

      axios
        .post(
          '/recipients/email/resend',
          JSON.stringify({
            recipient_id: id,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(({ data }) => {
          this.resendVerificationLoading = false
          this.success('Verification email resent')
        })
        .catch(error => {
          this.resendVerificationLoading = false
          if (error.response.status === 429) {
            this.error('You can only resend the email once per minute')
          } else {
            this.error()
          }
        })
    },
    openDeleteModal(recipient) {
      this.deleteRecipientModalOpen = true
      this.recipientToDelete = recipient
    },
    closeDeleteModal() {
      this.deleteRecipientModalOpen = false
      this.recipientToDelete = null
    },
    deleteRecipient(recipient) {
      this.deleteRecipientLoading = true

      axios
        .delete(`/api/v1/recipients/${recipient.id}`)
        .then(response => {
          recipient.should_encrypt = false
          recipient.fingerprint = null

          this.rows = _.reject(this.rows, row => row.id === recipient.id)
          this.deleteRecipientModalOpen = false
          this.deleteRecipientLoading = false
        })
        .catch(error => {
          this.error()
          this.deleteRecipientLoading = false
          this.deleteRecipientModalOpen = false
        })
    },
    openDeleteRecipientKeyModal(recipient) {
      this.deleteRecipientKeyModalOpen = true
      this.recipientKeyToDelete = recipient
    },
    closeDeleteRecipientKeyModal() {
      this.deleteRecipientKeyModalOpen = false
      this.recipientKeyIdToDelete = null
    },
    deleteRecipientKey(recipient) {
      this.deleteRecipientKeyLoading = true

      axios
        .delete(`/api/v1/recipient-keys/${recipient.id}`)
        .then(response => {
          recipient.should_encrypt = false
          recipient.fingerprint = null

          this.deleteRecipientKeyModalOpen = false
          this.deleteRecipientKeyLoading = false
        })
        .catch(error => {
          if (error.response !== undefined) {
            this.error(error.response.data)
          } else {
            this.error()
          }
          this.deleteRecipientKeyLoading = false
          this.deleteRecipientKeyModalOpen = false
        })
    },
    validateRecipientKey(e) {
      this.errors = {}

      if (!this.recipientKey) {
        this.errors.recipientKey = 'Key required'
      } else if (!this.validKey(this.recipientKey)) {
        this.errors.recipientKey = 'Valid Key required'
      }

      if (!this.errors.recipientKey) {
        this.addRecipientKey()
      }

      e.preventDefault()
    },
    addRecipientKey() {
      this.addRecipientKeyLoading = true

      axios
        .patch(
          `/api/v1/recipient-keys/${this.recipientToAddKey.id}`,
          JSON.stringify({
            key_data: this.recipientKey,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(({ data }) => {
          this.addRecipientKeyLoading = false

          let recipient = _.find(this.rows, ['id', this.recipientToAddKey.id])
          recipient.should_encrypt = data.data.should_encrypt
          recipient.fingerprint = data.data.fingerprint

          this.recipientKey = ''
          this.addRecipientKeyModalOpen = false
          this.success(
            `Key Successfully Added for ${this.recipientToAddKey.email}. Make sure to check the fingerprint is correct!`
          )
        })
        .catch(error => {
          this.addRecipientKeyLoading = false
          if (error.response !== undefined) {
            this.error(error.response.data)
          } else {
            this.error()
          }
        })
    },
    turnOnEncryption(id) {
      axios
        .post(
          `/api/v1/encrypted-recipients`,
          JSON.stringify({
            id: id,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          //
        })
        .catch(error => {
          this.error()
        })
    },
    turnOffEncryption(id) {
      axios
        .delete(`/api/v1/encrypted-recipients/${id}`)
        .then(response => {
          //
        })
        .catch(error => {
          this.error()
        })
    },
    allowRepliesSends(id) {
      axios
        .post(
          `/api/v1/allowed-recipients`,
          JSON.stringify({
            id: id,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          //
        })
        .catch(error => {
          this.error()
        })
    },
    disallowRepliesSends(id) {
      axios
        .delete(`/api/v1/allowed-recipients/${id}`)
        .then(response => {
          //
        })
        .catch(error => {
          this.error()
        })
    },
    turnOnInlineEncryption(id) {
      axios
        .post(
          `/api/v1/inline-encrypted-recipients`,
          JSON.stringify({
            id: id,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          //
        })
        .catch(error => {
          if (error.response.status === 422) {
            this.error(error.response.data)
          } else {
            this.error()
          }
        })
    },
    turnOffInlineEncryption(id) {
      axios
        .delete(`/api/v1/inline-encrypted-recipients/${id}`)
        .then(response => {
          //
        })
        .catch(error => {
          this.error()
        })
    },
    turnOnProtectedHeaders(id) {
      axios
        .post(
          `/api/v1/protected-headers-recipients`,
          JSON.stringify({
            id: id,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          //
        })
        .catch(error => {
          if (error.response.status === 422) {
            this.error(error.response.data)
          } else {
            this.error()
          }
        })
    },
    turnOffProtectedHeaders(id) {
      axios
        .delete(`/api/v1/protected-headers-recipients/${id}`)
        .then(response => {
          //
        })
        .catch(error => {
          this.error()
        })
    },
    openRecipientKeyModal(recipient) {
      this.addRecipientKeyModalOpen = true
      this.recipientToAddKey = recipient
    },
    closeRecipientKeyModal() {
      this.addRecipientKeyModalOpen = false
      this.recipientToAddKey = {}
    },
    validEmail(email) {
      let re =
        /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
      return re.test(email)
    },
    validKey(key) {
      let re =
        /-----BEGIN PGP PUBLIC KEY BLOCK-----([A-Za-z0-9+=\/\n]+)-----END PGP PUBLIC KEY BLOCK-----/i
      return re.test(key)
    },
    clipboardSuccess() {
      this.success('Copied to clipboard')
    },
    clipboardError() {
      this.error('Could not copy to clipboard')
    },
    success(text = '') {
      this.$notify({
        title: 'Success',
        text: text,
        type: 'success',
      })
    },
    error(text = 'An error has occurred, please try again later') {
      this.$notify({
        title: 'Error',
        text: text,
        type: 'error',
      })
    },
  },
}
</script>
