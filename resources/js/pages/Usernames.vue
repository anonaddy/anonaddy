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
    <div class="bg-white rounded shadow overflow-x-auto">
      <table v-if="initialUsernames.length" class="w-full whitespace-no-wrap">
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
              Username
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('username', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('v', 'asc') }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('username', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('username', 'desc') }"
                />
              </div>
            </div>
          </th>
          <th class="p-4">
            <div class="flex items-center">
              Description
            </div>
          </th>
          <th class="p-4 items-center" colspan="2">
            <div class="flex items-center">
              Active
              <div class="inline-flex flex-col">
                <icon
                  name="chevron-up"
                  @click.native="sort('active', 'asc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('active', 'asc') }"
                />
                <icon
                  name="chevron-down"
                  @click.native="sort('active', 'desc')"
                  class="w-4 h-4 text-grey-300 fill-current cursor-pointer"
                  :class="{ 'text-grey-800': isCurrentSort('active', 'desc') }"
                />
              </div>
            </div>
          </th>
        </tr>
        <tr
          v-for="username in queriedUsernames"
          :key="username.id"
          class="hover:bg-grey-50 focus-within:bg-grey-50 h-20"
        >
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center">
              <span
                class="tooltip outline-none text-sm"
                :data-tippy-content="username.created_at | formatDate"
                >{{ username.created_at | timeAgo }}</span
              >
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center focus:text-indigo-500">
              <span
                class="tooltip cursor-pointer outline-none"
                data-tippy-content="Click to copy"
                v-clipboard="() => username.username"
                v-clipboard:success="clipboardSuccess"
                v-clipboard:error="clipboardError"
                >{{ username.username | truncate(30) }}</span
              >
            </div>
          </td>
          <td class="border-grey-200 border-t w-64">
            <div class="p-4 text-sm">
              <div
                v-if="usernameIdToEdit === username.id"
                class="w-full flex items-center justify-between"
              >
                <input
                  @keyup.enter="editUsername(username)"
                  @keyup.esc="usernameIdToEdit = usernameDescriptionToEdit = ''"
                  v-model="usernameDescriptionToEdit"
                  type="text"
                  class="appearance-none bg-grey-100 border text-grey-700 focus:outline-none rounded px-2 py-1"
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
                  @click.native="editUsername(username)"
                />
              </div>
              <div
                v-else-if="username.description"
                class="flex items-center justify-between w-full"
              >
                <span class="tooltip outline-none" :data-tippy-content="username.description">{{
                  username.description | truncate(25)
                }}</span>
                <icon
                  name="edit"
                  class="inline-block w-6 h-6 text-grey-200 fill-current cursor-pointer"
                  @click.native="
                    ;(usernameIdToEdit = username.id),
                      (usernameDescriptionToEdit = username.description)
                  "
                />
              </div>
              <div v-else class="w-full flex justify-center">
                <icon
                  name="plus"
                  class="block w-6 h-6 text-grey-200 fill-current cursor-pointer"
                  @click.native="usernameIdToEdit = username.id"
                />
              </div>
            </div>
          </td>
          <td class="border-grey-200 border-t">
            <div class="p-4 flex items-center">
              <Toggle
                v-model="username.active"
                @on="activateUsername(username)"
                @off="deactivateUsername(username)"
              />
            </div>
          </td>
          <td class="border-grey-200 border-t w-px">
            <div
              class="px-4 flex items-center cursor-pointer outline-none focus:text-indigo-500"
              @click="openDeleteModal(username.id)"
              tabindex="-1"
            >
              <icon name="trash" class="block w-6 h-6 text-grey-200 fill-current" />
            </div>
          </td>
        </tr>
        <tr v-if="queriedUsernames.length === 0">
          <td
            class="border-grey-200 border-t p-4 text-center h-24 text-lg text-grey-700"
            colspan="4"
          >
            No usernames found for that search!
          </td>
        </tr>
      </table>

      <div v-else class="p-8 text-center text-lg text-grey-700">
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
      usernames: this.initialUsernames,
      newUsername: '',
      search: '',
      addUsernameLoading: false,
      addUsernameModalOpen: false,
      usernameIdToDelete: null,
      usernameIdToEdit: '',
      usernameDescriptionToEdit: '',
      deleteUsernameLoading: false,
      deleteUsernameModalOpen: false,
      currentSort: 'created_at',
      currentSortDir: 'desc',
      errors: {},
    }
  },
  watch: {
    queriedUsernames: _.debounce(function() {
      this.addTooltips()
    }, 50),
    usernameIdToEdit: _.debounce(function() {
      this.addTooltips()
    }, 50),
  },
  computed: {
    queriedUsernames() {
      return _.filter(this.usernames, username => username.username.includes(this.search))
    },
  },
  methods: {
    addTooltips() {
      tippy('.tooltip', {
        arrow: true,
        arrowType: 'round',
      })
    },
    isCurrentSort(col, dir) {
      return this.currentSort === col && this.currentSortDir === dir
    },
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
          this.usernames.push(data.data)
          this.reSort()
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
    activateUsername(username) {
      axios
        .post(
          `/active-usernames`,
          JSON.stringify({
            id: username.id,
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
    deactivateUsername(username) {
      axios
        .delete(`/active-usernames/${username.id}`)
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
          this.usernames = _.filter(this.usernames, username => username.id !== id)
          this.deleteUsernameModalOpen = false
          this.deleteUsernameLoading = false
        })
        .catch(error => {
          this.error()
          this.deleteUsernameLoading = false
          this.deleteUsernameModalOpen = false
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
    reSort() {
      this.usernames = _.orderBy(this.usernames, [this.currentSort], [this.currentSortDir])
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
