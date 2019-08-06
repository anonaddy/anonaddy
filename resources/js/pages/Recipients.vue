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
          @click.native="search = ''"
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
    <div class="bg-white rounded shadow overflow-x-auto">
      <table class="w-full whitespace-no-wrap">
        <tr class="text-left font-semibold text-grey-500 text-sm tracking-wider">
          <th class="p-4">
            <div class="flex items-center">
              Created
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('created_at', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('created_at', 'asc') }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('created_at', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('created_at', 'desc'),
                  }"
                />
              </div>
            </div>
          </th>
          <th class="p-4">
            <div class="flex items-center">
              Key
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('key', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('key', 'asc') }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('key', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('key', 'desc'),
                  }"
                />
              </div>
              <span
                class="tooltip outline-none"
                :data-tippy-content="
                  `Use this to attach recipients to new aliases as they are created e.g. alias+key@${
                    user.username
                  }.anonaddy.com. You can attach multiple recipients by doing alias+2.3.4@${
                    user.username
                  }.anonaddy.com`
                "
              >
                <icon name="info" class="inline-block w-4 h-4 text-grey-200 fill-current" />
              </span>
            </div>
          </th>
          <th class="p-4">
            <div class="flex items-center">
              Email
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('email', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('email', 'asc') }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('email', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('email', 'desc') }"
                />
              </div>
            </div>
          </th>
          <th class="p-4">
            <div class="flex items-center">
              Recipient Aliases
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('aliases', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('aliases', 'asc'),
                  }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('aliases', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('aliases', 'desc'),
                  }"
                />
              </div>
            </div>
          </th>
          <th class="p-4">
            <div class="flex items-center">
              Encryption
            </div>
          </th>
          <th class="p-4" colspan="2">
            <div class="flex items-center">
              Verified
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('email_verified_at', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('email_verified_at', 'asc'),
                  }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('email_verified_at', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{
                    'text-grey-800': isCurrentSort('email_verified_at', 'desc'),
                  }"
                />
              </div>
            </div>
          </th>
        </tr>
        <tr
          v-for="recipient in queriedRecipients"
          :key="recipient.email"
          class="hover:bg-grey-50 focus-within:bg-grey-50 h-20"
        >
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center">
              <span
                class="tooltip outline-none text-sm"
                :data-tippy-content="recipient.created_at | formatDate"
                >{{ recipient.created_at | timeAgo }}</span
              >
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center">
              {{ recipient.key }}
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center focus:text-indigo-500">
              <span
                class="tooltip cursor-pointer outline-none"
                data-tippy-content="Click to copy"
                v-clipboard="() => recipient.email"
                v-clipboard:success="clipboardSuccess"
                v-clipboard:error="clipboardError"
                >{{ recipient.email | truncate(30) }}</span
              >

              <span
                v-if="isDefault(recipient.id)"
                class="ml-3 py-1 px-2 text-sm bg-yellow-200 text-yellow-900 rounded-full tooltip"
                data-tippy-content="The default recipient will be used for all aliases with no other recipients assigned"
              >
                default
              </span>
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center focus:text-indigo-500">
              <span
                v-if="recipient.aliases.length"
                class="tooltip outline-none"
                :data-tippy-content="aliasesTooltip(recipient.aliases)"
                >{{ recipient.aliases[0].email | truncate(40) }}
                <span
                  v-if="recipient.aliases.length > 1"
                  class="block text-center text-grey-500 text-sm"
                >
                  + {{ recipient.aliases.length - 1 }}</span
                >
              </span>
              <span v-else class="block text-center text-grey-500 text-sm">{{
                recipient.aliases.length
              }}</span>
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center focus:text-indigo-500 text-sm">
              <span v-if="recipient.fingerprint" class="flex">
                <Toggle
                  v-model="recipient.should_encrypt"
                  @on="turnOnEncryption(recipient)"
                  @off="turnOffEncryption(recipient)"
                />
                <icon
                  name="fingerprint"
                  class="tooltip outline-none cursor-pointer block w-6 h-6 text-grey-200 fill-current ml-2"
                  :data-tippy-content="recipient.fingerprint"
                  v-clipboard="() => recipient.fingerprint"
                  v-clipboard:success="clipboardSuccess"
                  v-clipboard:error="clipboardError"
                />
              </span>
              <button v-else @click="openRecipientKeyModal(recipient)" class="focus:outline-none">
                Add public key
              </button>
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center focus:text-indigo-500 text-sm">
              <span
                name="check"
                v-if="recipient.email_verified_at"
                class="py-1 px-2 bg-green-200 text-green-900 rounded-full"
              >
                verified
              </span>
              <button
                v-else
                @click="resendVerification(recipient.id)"
                class="focus:outline-none"
                :class="resendVerificationLoading ? 'cursor-not-allowed' : ''"
                :disabled="resendVerificationLoading"
              >
                Resend email
              </button>
            </div>
          </td>
          <td class="border-grey-200 border-t w-px">
            <div
              v-if="!isDefault(recipient.id)"
              class="px-4 flex items-center cursor-pointer outline-none focus:text-indigo-500"
              @click="openDeleteModal(recipient.id)"
              tabindex="-1"
            >
              <icon name="trash" class="block w-6 h-6 text-grey-200 fill-current" />
            </div>
          </td>
        </tr>
        <tr v-if="queriedRecipients.length === 0">
          <td
            class="border-grey-200 border-t p-4 text-center h-24 text-lg text-grey-700"
            colspan="6"
          >
            No recipients found for that search!
          </td>
        </tr>
      </table>
    </div>

    <Modal :open="addRecipientModalOpen" @close="addRecipientModalOpen = false">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl p-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Add new recipient
        </h2>
        <p class="mt-4 text-grey-700">
          Enter the individual email of the new recipient you'd like to add.
        </p>
        <div class="mt-6">
          <p v-show="errors.newRecipient" class="mb-3 text-red-500">
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
          </button>
          <button
            @click="addRecipientModalOpen = false"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <Modal :open="addRecipientKeyModalOpen" @close="closeRecipientKeyModal">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl p-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Add Public GPG Key
        </h2>
        <p class="mt-4 text-grey-700">Enter your <b>PUBLIC</b> key data in the text area below.</p>
        <p class="mt-4 text-grey-700">Make sure to remove <b>Comment:</b> and <b>Version:</b></p>
        <div class="mt-6">
          <p v-show="errors.recipientKey" class="mb-3 text-red-500">
            {{ errors.recipientKey }}
          </p>
          <textarea
            v-model="recipientKey"
            type="email"
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
          </button>
          <button
            @click="closeRecipientKeyModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <Modal :open="deleteRecipientModalOpen" @close="closeDeleteModal">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl p-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Delete recipient
        </h2>
        <p class="mt-4 text-grey-700">Are you sure you want to delete this recipient?</p>
        <div class="mt-6">
          <button
            type="button"
            @click="deleteRecipient(recipientIdToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none"
            :class="deleteRecipientLoading ? 'cursor-not-allowed' : ''"
            :disabled="deleteRecipientLoading"
          >
            Delete recipient
          </button>
          <button
            @click="closeDeleteModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script>
import Modal from './../components/Modal.vue'
import Toggle from './../components/Toggle.vue'
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
  },
  components: {
    Modal,
    Toggle,
  },
  created() {
    this.defaultRecipient = _.find(this.recipients, ['id', this.user.default_recipient_id])
    this.defaultRecipient.aliases = this.defaultRecipient.aliases.concat(this.aliasesUsingDefault)
  },
  mounted() {
    this.addTooltips()
  },
  data() {
    return {
      recipients: this.initialRecipients,
      defaultRecipient: {},
      newRecipient: '',
      recipientKey: '',
      search: '',
      addRecipientLoading: false,
      addRecipientModalOpen: false,
      recipientIdToDelete: null,
      deleteRecipientLoading: false,
      deleteRecipientModalOpen: false,
      addRecipientKeyLoading: false,
      addRecipientKeyModalOpen: false,
      recipientToAddKey: {},
      resendVerificationLoading: false,
      currentSort: 'created_at',
      currentSortDir: 'desc',
      errors: {},
    }
  },
  watch: {
    queriedRecipients: _.debounce(function() {
      this.addTooltips()
    }, 50),
    addRecipientKeyModalOpen: _.debounce(function() {
      this.addTooltips()
    }, 50),
  },
  computed: {
    queriedRecipients() {
      return _.filter(this.recipients, recipient => recipient.email.includes(this.search))
    },
  },
  methods: {
    addTooltips() {
      tippy('.tooltip', {
        arrow: true,
        arrowType: 'round',
      })
    },
    aliasesTooltip(aliases) {
      return _.reduce(aliases, (list, alias) => list + `${alias.email}<br>`, '')
    },
    isDefault(id) {
      return this.user.default_recipient_id === id
    },
    isCurrentSort(col, dir) {
      return this.currentSort === col && this.currentSortDir === dir
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
          '/recipients',
          JSON.stringify({
            email: this.newRecipient,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(({ data }) => {
          this.addRecipientLoading = false
          data.data.key = this.recipients.length + 1
          this.recipients.push(data.data)
          this.reSort()
          this.newRecipient = ''
          this.addRecipientModalOpen = false
          this.success('Recipient created and verification email sent')
        })
        .catch(error => {
          this.addRecipientLoading = false
          if (error.response.status == 422) {
            this.error(error.response.data.errors.email[0])
          } else {
            this.error()
          }
        })
    },
    resendVerification(id) {
      this.resendVerificationLoading = true

      axios
        .get(`/recipients/${id}/email/resend`)
        .then(({ data }) => {
          this.resendVerificationLoading = false
          this.success('Verification email resent')
        })
        .catch(error => {
          this.resendVerificationLoading = false
          if (error.response.status === 429) {
            this.error('You can only resend the email once every 5 minutes')
          } else {
            this.error()
          }
        })
    },
    openDeleteModal(id) {
      this.deleteRecipientModalOpen = true
      this.recipientIdToDelete = id
    },
    closeDeleteModal() {
      this.deleteRecipientModalOpen = false
      this.recipientIdToDelete = null
    },
    deleteRecipient(id) {
      this.deleteRecipientLoading = true

      axios
        .delete(`/recipients/${id}`)
        .then(response => {
          this.recipients = _.filter(this.recipients, recipient => recipient.id !== id)
          this.deleteRecipientModalOpen = false
          this.deleteRecipientLoading = false
        })
        .catch(error => {
          this.error()
          this.deleteRecipientLoading = false
          this.deleteRecipientModalOpen = false
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
          `/recipient-keys/${this.recipientToAddKey.id}`,
          JSON.stringify({
            key_data: this.recipientKey,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(({ data }) => {
          this.addRecipientKeyLoading = false

          let recipient = _.find(this.recipients, ['id', this.recipientToAddKey.id])
          recipient.should_encrypt = data.data.should_encrypt
          recipient.fingerprint = data.data.fingerprint

          this.recipientKey = ''
          this.addRecipientKeyModalOpen = false
          this.success(
            `Key Successfully Added for ${
              this.recipientToAddKey.email
            }. Make sure to check the fingerprint is correct!`
          )
        })
        .catch(error => {
          this.addRecipientKeyLoading = false
          if (error.response !== undefined) {
            this.error(error.response.data.message)
          } else {
            this.error()
          }
        })
    },
    turnOnEncryption(recipient) {
      axios
        .post(
          `/encrypted-recipients`,
          JSON.stringify({
            id: recipient.id,
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
    turnOffEncryption(recipient) {
      axios
        .delete(`/encrypted-recipients/${recipient.id}`)
        .then(response => {
          //
        })
        .catch(error => {
          this.error()
        })
    },
    sort(col, dir) {
      if (this.currentSort === col && this.currentSortDir === dir) {
        this.currentSort = 'created_at'
        this.currentSortDir = 'desc'
      } else {
        this.currentSort = col
        this.currentSortDir = dir
      }

      this.reSort()
    },
    openRecipientKeyModal(recipient) {
      this.addRecipientKeyModalOpen = true
      this.recipientToAddKey = recipient
    },
    closeRecipientKeyModal() {
      this.addRecipientKeyModalOpen = false
      this.recipientToAddKey = {}
    },
    reSort() {
      this.recipients = _.orderBy(this.recipients, [this.currentSort], [this.currentSortDir])
    },
    validEmail(email) {
      let re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
      return re.test(email)
    },
    validKey(key) {
      let re = /-----BEGIN PGP PUBLIC KEY BLOCK-----([A-Za-z0-9+=\/\n]+)-----END PGP PUBLIC KEY BLOCK-----/i
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
