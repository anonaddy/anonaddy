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
          placeholder="Search Usernames"
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
          @click="addUsernameModalOpen = true"
          class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none ml-auto"
        >
          Add Username
        </button>
      </div>
    </div>

    <vue-good-table
      v-if="initialUsernames.length"
      @on-search="debounceToolips"
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
      <div slot="emptystate" class="flex items-center justify-center h-24 text-lg text-grey-700">
        No usernames found for that search!
      </div>
      <template slot="table-row" slot-scope="props">
        <span
          v-if="props.column.field == 'created_at'"
          class="tooltip outline-none text-sm"
          :data-tippy-content="props.row.created_at | formatDate"
          >{{ props.row.created_at | timeAgo }}
        </span>
        <span v-else-if="props.column.field == 'username'">
          <span
            class="tooltip cursor-pointer outline-none"
            data-tippy-content="Click to copy"
            v-clipboard="() => props.row.username"
            v-clipboard:success="clipboardSuccess"
            v-clipboard:error="clipboardError"
            >{{ props.row.username | truncate(30) }}</span
          >
        </span>
        <span v-else-if="props.column.field == 'description'">
          <div v-if="usernameIdToEdit === props.row.id" class="flex items-center">
            <input
              @keyup.enter="editUsername(rows[props.row.originalIndex])"
              @keyup.esc="usernameIdToEdit = usernameDescriptionToEdit = ''"
              v-model="usernameDescriptionToEdit"
              type="text"
              class="flex-grow appearance-none bg-grey-100 border text-grey-700 focus:outline-none rounded px-2 py-1"
              :class="
                usernameDescriptionToEdit.length > 100 ? 'border-red-500' : 'border-transparent'
              "
              placeholder="Add description"
              tabindex="0"
              autofocus
            />
            <icon
              name="close"
              class="inline-block w-6 h-6 text-red-300 fill-current cursor-pointer"
              @click.native="usernameIdToEdit = usernameDescriptionToEdit = ''"
            />
            <icon
              name="save"
              class="inline-block w-6 h-6 text-cyan-500 fill-current cursor-pointer"
              @click.native="editUsername(rows[props.row.originalIndex])"
            />
          </div>
          <div v-else-if="props.row.description" class="flex items-centers">
            <span class="tooltip outline-none" :data-tippy-content="props.row.description">{{
              props.row.description | truncate(60)
            }}</span>
            <icon
              name="edit"
              class="inline-block w-6 h-6 text-grey-200 fill-current cursor-pointer ml-2"
              @click.native="
                ;(usernameIdToEdit = props.row.id),
                  (usernameDescriptionToEdit = props.row.description)
              "
            />
          </div>
          <div v-else class="flex justify-center">
            <icon
              name="plus"
              class="block w-6 h-6 text-grey-200 fill-current cursor-pointer"
              @click.native=";(usernameIdToEdit = props.row.id), (usernameDescriptionToEdit = '')"
            />
          </div>
        </span>
        <span v-else-if="props.column.field === 'active'" class="flex items-center">
          <Toggle
            v-model="rows[props.row.originalIndex].active"
            @on="activateUsername(props.row.id)"
            @off="deactivateUsername(props.row.id)"
          />
        </span>
        <span v-else class="flex items-center justify-center outline-none" tabindex="-1">
          <icon
            name="trash"
            class="block w-6 h-6 text-grey-200 fill-current cursor-pointer"
            @click.native="openDeleteModal(props.row.id)"
          />
        </span>
      </template>
    </vue-good-table>

    <div v-else class="bg-white rounded shadow overflow-x-auto">
      <div class="p-8 text-center text-lg text-grey-700">
        <h1 class="mb-6 text-xl text-indigo-800 font-semibold">
          This is where you can add and view additional usernames
        </h1>
        <div class="mx-auto mb-6 w-24 border-b-2 border-grey-200"></div>
        <p class="mb-4">
          When you add an additional username here you will be able to use it exactly like the
          username you signed up with!
        </p>
        <p class="mb-4">
          You can then separate aliases under your different usernames to reduce the chance of
          anyone linking ownership of them together. Great for compartmentalisation e.g. for work
          and personal emails.
        </p>
        <p>
          You can add a maximum of {{ usernameCount }} additional usernames. Deleted usernames still
          count towards your limit so please choose carefully.
        </p>
      </div>
    </div>

    <Modal :open="addUsernameModalOpen" @close="addUsernameModalOpen = false">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl p-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Add new username
        </h2>
        <p class="mt-4 text-grey-700">
          Please choose additional usernames carefully as you can only add a maximum of
          {{ usernameCount }}.
        </p>
        <div class="mt-6">
          <p v-show="errors.newUsername" class="mb-3 text-red-500">
            {{ errors.newUsername }}
          </p>
          <input
            v-model="newUsername"
            type="text"
            class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3 mb-6"
            :class="errors.newUsername ? 'border-red-500' : ''"
            placeholder="johndoe"
            autofocus
          />
          <button
            @click="validateNewUsername"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            :class="addUsernameLoading ? 'cursor-not-allowed' : ''"
            :disabled="addUsernameLoading"
          >
            Add Username
            <loader v-if="addUsernameLoading" />
          </button>
          <button
            @click="addUsernameModalOpen = false"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <Modal :open="deleteUsernameModalOpen" @close="closeDeleteModal">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl p-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Delete username
        </h2>
        <p class="mt-4 text-grey-700">
          Are you sure you want to delete this username? You will no longer be able to receive any
          emails at this username subdomain. This will still count towards your additional username
          limit even once deleted.
        </p>
        <div class="mt-6">
          <button
            type="button"
            @click="deleteUsername(usernameIdToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none"
            :class="deleteUsernameLoading ? 'cursor-not-allowed' : ''"
            :disabled="deleteUsernameLoading"
          >
            Delete username
            <loader v-if="deleteUsernameLoading" />
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
    initialUsernames: {
      type: Array,
      required: true,
    },
    usernameCount: {
      type: Number,
      required: true,
    },
  },
  components: {
    Modal,
    Toggle,
  },
  mounted() {
    this.addTooltips()
  },
  data() {
    return {
      newUsername: '',
      search: '',
      addUsernameLoading: false,
      addUsernameModalOpen: false,
      usernameIdToDelete: null,
      usernameIdToEdit: '',
      usernameDescriptionToEdit: '',
      deleteUsernameLoading: false,
      deleteUsernameModalOpen: false,
      errors: {},
      columns: [
        {
          label: 'Created',
          field: 'created_at',
          globalSearchDisabled: true,
        },
        {
          label: 'Username',
          field: 'username',
        },
        {
          label: 'Description',
          field: 'description',
          width: '500px',
        },
        {
          label: 'Active',
          field: 'active',
          type: 'boolean',
          globalSearchDisabled: true,
        },
        {
          label: '',
          field: 'actions',
          sortable: false,
          globalSearchDisabled: true,
        },
      ],
      rows: this.initialUsernames,
    }
  },
  watch: {
    usernameIdToEdit: _.debounce(function() {
      this.addTooltips()
    }, 50),
  },
  methods: {
    addTooltips() {
      tippy('.tooltip', {
        arrow: true,
        arrowType: 'round',
      })
    },
    debounceToolips: _.debounce(function() {
      this.addTooltips()
    }, 50),
    validateNewUsername(e) {
      this.errors = {}

      if (!this.newUsername) {
        this.errors.newUsername = 'Username is required'
      } else if (!this.validUsername(this.newUsername)) {
        this.errors.newUsername = 'Username must only contain letters and numbers'
      } else if (this.newUsername.length > 20) {
        this.errors.newUsername = 'Username cannot be greater than 20 characters'
      }

      if (!this.errors.newUsername) {
        this.addNewUsername()
      }

      e.preventDefault()
    },
    addNewUsername() {
      this.addUsernameLoading = true

      axios
        .post(
          '/usernames',
          JSON.stringify({
            username: this.newUsername,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(({ data }) => {
          this.addUsernameLoading = false
          this.rows.push(data.data)
          this.newUsername = ''
          this.addUsernameModalOpen = false
          this.success('Additional Username added')
        })
        .catch(error => {
          this.addUsernameLoading = false

          if (error.response.status === 403) {
            this.error('You have reached your additional username limit')
          } else if (error.response.status == 422) {
            this.error(error.response.data.errors.username[0])
          } else {
            this.error()
          }
        })
    },
    openDeleteModal(id) {
      this.deleteUsernameModalOpen = true
      this.usernameIdToDelete = id
    },
    closeDeleteModal() {
      this.deleteUsernameModalOpen = false
      this.usernameIdToDelete = null
    },
    editUsername(username) {
      if (this.usernameDescriptionToEdit.length > 100) {
        return this.error('Description cannot be more than 100 characters')
      }

      axios
        .patch(
          `/usernames/${username.id}`,
          JSON.stringify({
            description: this.usernameDescriptionToEdit,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          username.description = this.usernameDescriptionToEdit
          this.usernameIdToEdit = ''
          this.usernameDescriptionToEdit = ''
          this.success('Username description updated')
        })
        .catch(error => {
          this.usernameIdToEdit = ''
          this.usernameDescriptionToEdit = ''
          this.error()
        })
    },
    activateUsername(id) {
      axios
        .post(
          `/active-usernames`,
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
    deactivateUsername(id) {
      axios
        .delete(`/active-usernames/${id}`)
        .then(response => {
          //
        })
        .catch(error => {
          this.error()
        })
    },
    deleteUsername(id) {
      this.deleteUsernameLoading = true

      axios
        .delete(`/usernames/${id}`)
        .then(response => {
          this.rows = _.reject(this.rows, username => username.id === id)
          this.deleteUsernameModalOpen = false
          this.deleteUsernameLoading = false
        })
        .catch(error => {
          this.error()
          this.deleteUsernameLoading = false
          this.deleteUsernameModalOpen = false
        })
    },
    validUsername(username) {
      let re = /^[a-zA-Z0-9]*$/
      return re.test(username)
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
