<template>
  <div>
    <Head title="Usernames" />
    <h1 id="primary-heading" class="sr-only">Usernames</h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900">Usernames</h1>
        <p class="mt-2 text-sm text-grey-700">
          A list of all the usernames {{ search ? 'found for your search' : 'in your account' }}
          <button @click="moreInfoOpen = !moreInfoOpen">
            <InformationCircleIcon
              class="h-6 w-6 inline-block cursor-pointer text-grey-500"
              title="Click for more information"
            />
          </button>
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button
          type="button"
          @click="openAddUsernameModal"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 font-bold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:w-auto"
        >
          Add Username
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
        <span v-if="props.column.label == 'Active'">
          {{ props.column.label }}
          <span
            class="tooltip outline-none"
            data-tippy-content="When a username is deactivated, any messages sent to its aliases will be silently discarded. The sender will not be notified of the unsuccessful delivery."
          >
            <icon name="info" class="inline-block w-4 h-4 text-grey-300 fill-current" />
          </span>
        </span>
        <span v-else-if="props.column.label == 'Catch-All'">
          {{ props.column.label }}
          <span
            class="tooltip outline-none"
            data-tippy-content="When catch-all is disabled, only aliases that already exist for the username will forward messages. They will not be automatically created on-the-fly."
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
          class="tooltip outline-none cursor-default text-sm text-grey-500"
          :data-tippy-content="$filters.formatDate(rows[props.row.originalIndex].created_at)"
          >{{ $filters.timeAgo(props.row.created_at) }}
        </span>
        <span v-else-if="props.column.field == 'username'">
          <button
            class="tooltip outline-none font-medium text-grey-700"
            data-tippy-content="Click to copy"
            @click="clipboard(rows[props.row.originalIndex].username)"
          >
            {{ $filters.truncate(props.row.username, 30) }}
          </button>
          <span
            v-if="isDefault(props.row.id)"
            class="ml-3 py-1 px-2 text-sm bg-yellow-200 text-yellow-900 rounded-full"
            >default</span
          >
          <span v-else class="block text-grey-400 text-sm py-1">
            <button @click="openMakeDefaultModal(props.row)">Make Default</button>
          </span>
        </span>
        <span v-else-if="props.column.field == 'description'">
          <div v-if="usernameIdToEdit === props.row.id" class="flex items-center">
            <input
              @keyup.enter="editUsername(rows[props.row.originalIndex])"
              @keyup.esc="usernameIdToEdit = usernameDescriptionToEdit = ''"
              v-model="usernameDescriptionToEdit"
              type="text"
              class="grow appearance-none bg-grey-50 border text-grey-700 focus:outline-none rounded px-2 py-1"
              :class="
                usernameDescriptionToEdit.length > 200 ? 'border-red-500' : 'border-transparent'
              "
              placeholder="Add description"
              tabindex="0"
              autofocus
            />
            <button @click="usernameIdToEdit = usernameDescriptionToEdit = ''">
              <Icon name="close" class="inline-block w-6 h-6 text-red-300 fill-current" />
            </button>
            <button @click="editUsername(rows[props.row.originalIndex])">
              <Icon name="save" class="inline-block w-6 h-6 text-cyan-500 fill-current" />
            </button>
          </div>
          <div v-else-if="props.row.description" class="flex items-centers">
            <span class="outline-none text-grey-500 mr-2">{{
              $filters.truncate(props.row.description, 60)
            }}</span>
            <button
              @click="
                ;(usernameIdToEdit = props.row.id),
                  (usernameDescriptionToEdit = props.row.description)
              "
            >
              <Icon name="edit" class="inline-block w-6 h-6 text-grey-300 fill-current" />
            </button>
          </div>
          <div v-else class="flex justify-center">
            <button @click=";(usernameIdToEdit = props.row.id), (usernameDescriptionToEdit = '')">
              <Icon name="plus" class="block w-6 h-6 text-grey-300 fill-current" />
            </button>
          </div>
        </span>
        <span v-else-if="props.column.field === 'default_recipient'">
          <div v-if="props.row.default_recipient">
            <span
              class="tooltip cursor-pointer font-medium text-grey-500 mr-2"
              data-tippy-content="Click to copy"
              @click="clipboard(rows[props.row.originalIndex].default_recipient.email)"
            >
              {{ $filters.truncate(props.row.default_recipient.email, 30) }}
            </span>
            <button @click="openUsernameDefaultRecipientModal(props.row)">
              <Icon name="edit" class="inline-block w-6 h-6 text-grey-300 fill-current" />
            </button>
          </div>
          <div class="flex justify-center" v-else>
            <button @click="openUsernameDefaultRecipientModal(props.row)">
              <Icon name="plus" class="block w-6 h-6 text-grey-300 fill-current" />
            </button>
          </div>
        </span>
        <span v-else-if="props.column.field === 'aliases_count'">
          <span v-if="props.row.aliases_count" class="text-grey-500">
            <Link
              :href="route('aliases.index', { username: props.row.id })"
              as="button"
              type="button"
              data-tippy-content="Click to view the aliases using this username"
              class="text-indigo-600 hover:text-indigo-900 font-medium tooltip"
              >{{ props.row.aliases_count.toLocaleString() }}</Link
            >
          </span>
          <span v-else class="text-grey-500"> {{ props.row.aliases_count }}</span>
        </span>
        <span v-else-if="props.column.field === 'active'" class="flex items-center">
          <Toggle
            v-model="rows[props.row.originalIndex].active"
            @on="activateUsername(props.row.id)"
            @off="deactivateUsername(props.row.id)"
          />
        </span>
        <span v-else-if="props.column.field === 'catch_all'" class="flex items-center">
          <Toggle
            v-model="rows[props.row.originalIndex].catch_all"
            @on="enableCatchAll(props.row.id)"
            @off="disableCatchAll(props.row.id)"
          />
        </span>
        <span v-else class="flex items-center justify-center outline-none" tabindex="-1">
          <Link
            :href="route('usernames.edit', props.row.id)"
            as="button"
            type="button"
            class="text-indigo-500 hover:text-indigo-800 font-medium"
            >Edit<span class="sr-only">, {{ props.row.username }}</span></Link
          >
          <button
            v-if="!isDefault(props.row.id)"
            @click="openDeleteModal(props.row.id)"
            as="button"
            type="button"
            class="text-indigo-500 hover:text-indigo-800 font-medium ml-4"
          >
            Delete<span class="sr-only">, {{ props.row.username }}</span>
          </button>
        </span>
      </template>
    </vue-good-table>

    <div v-else class="text-center">
      <UsersIcon class="mx-auto h-16 w-16 text-grey-400" />
      <h3 class="mt-2 text-lg font-medium text-grey-900">No Usernames found for that search</h3>
      <p class="mt-1 text-md text-grey-500">Try entering a different search term.</p>
      <div class="mt-6">
        <Link
          :href="route('usernames.index')"
          type="button"
          class="inline-flex items-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 text-sm font-medium shadow-sm focus:outline-none"
        >
          View All Usernames
        </Link>
      </div>
    </div>

    <Modal :open="addUsernameModalOpen" @close="addUsernameModalOpen = false">
      <template v-slot:title> Add new username </template>
      <template v-slot:content>
        <p v-if="usernameCount === 1" class="mt-4 text-grey-700">
          Please <b>upgrade your account</b> to add a new username. <br />You can login with
          <b>any of your usernames</b>.
        </p>
        <p v-else class="mt-4 text-grey-700">
          Please choose usernames carefully as you can only add a
          <b>maximum of {{ usernameCount }}</b
          >. You can login with <b>any of your usernames</b>.
        </p>
        <p class="mt-4 text-grey-700">
          You can prevent a username from being used to login by toggling the "can login" option.
        </p>
        <div class="mt-6">
          <p v-show="errors.newUsername" class="mb-3 text-red-500 text-sm">
            {{ errors.newUsername }}
          </p>
          <input
            v-model="newUsername"
            type="text"
            class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 mb-6"
            :class="errors.newUsername ? 'ring-red-500' : ''"
            placeholder="johndoe"
            autofocus
          />
          <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
            <button
              @click="validateNewUsername"
              class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
              :disabled="addUsernameLoading"
            >
              Add Username
              <loader v-if="addUsernameLoading" />
            </button>
            <button
              @click="addUsernameModalOpen = false"
              class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Cancel
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <Modal :open="usernameDefaultRecipientModalOpen" @close="closeUsernameDefaultRecipientModal">
      <template v-slot:title> Update Default Recipient </template>
      <template v-slot:content>
        <p class="my-4 text-grey-700">
          Select the default recipient for this username. This overrides the default recipient in
          your account settings. Leave it empty if you would like to use the default recipient in
          your account settings.
        </p>
        <multiselect
          v-model="defaultRecipientId"
          :options="recipientOptions"
          mode="single"
          value-prop="id"
          :close-on-select="true"
          :clear-on-select="false"
          :searchable="false"
          :allow-empty="true"
          placeholder="Select recipient"
          label="email"
          track-by="email"
        >
        </multiselect>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="editDefaultRecipient()"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="editDefaultRecipientLoading"
          >
            Update Default Recipient
            <Loader v-if="editDefaultRecipientLoading" />
          </button>
          <button
            @click="closeUsernameDefaultRecipientModal()"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="deleteUsernameModalOpen" @close="closeDeleteModal">
      <template v-slot:title> Delete username </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Are you sure you want to permanently delete this username? This will also
          <b>permanently remove all aliases associated with this username</b>. You will no longer be
          able to receive any emails at this username subdomain.
          <b>This username will not be able to be used again</b>.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="deleteUsername(usernameIdToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="deleteUsernameLoading"
          >
            Delete username
            <Loader v-if="deleteUsernameLoading" />
          </button>
          <button
            @click="closeDeleteModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="makeDefaultModalOpen" @close="closeMakeDefaultModal">
      <template v-slot:title> Make default username</template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          The default username for your account is used in the username reminder notification.
        </p>
        <p class="mt-4 text-grey-700">
          You will always be able to use your default username to login to your account.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="makeDefaultUsername(usernameToMakeDefault)"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="makeDefaultLoading"
          >
            Make default username
            <loader v-if="makeDefaultLoading" />
          </button>
          <button
            @click="closeMakeDefaultModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="moreInfoOpen" @close="moreInfoOpen = false">
      <template v-slot:title> More information </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          When you add a username here you will be able to use it exactly like the username you
          signed up with!
        </p>
        <p class="mt-4 text-grey-700">
          You can then separate aliases under your different usernames to reduce the chance of
          anyone linking ownership of them together. Great for compartmentalisation e.g. for work
          and personal emails.
        </p>
        <p v-if="usernameCount === 1" class="mt-4 text-grey-700">
          You need to upgrade your account to add any usernames.
        </p>
        <p v-else class="mt-4 text-grey-700">
          You can add a maximum of <b>{{ usernameCount }}</b> usernames.
        </p>

        <div class="mt-6 flex flex-col sm:flex-row">
          <button
            @click="moreInfoOpen = false"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Close
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, toRefs } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import Modal from '../../Components/Modal.vue'
import Toggle from '../../Components/Toggle.vue'
import { roundArrow } from 'tippy.js'
import tippy from 'tippy.js'
import Multiselect from '@vueform/multiselect'
import { VueGoodTable } from 'vue-good-table-next'
import { notify } from '@kyvg/vue3-notification'
import { InformationCircleIcon, UsersIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  initialRows: {
    type: Array,
    required: true,
  },
  usernameCount: {
    type: Number,
    required: true,
  },
  recipientOptions: {
    type: Array,
    required: true,
  },
  search: {
    type: String,
  },
})
const { recipientOptions } = toRefs(props)

const rows = ref(props.initialRows)
const newUsername = ref('')
const addUsernameLoading = ref(false)
const addUsernameModalOpen = ref(false)
const usernameIdToDelete = ref(null)
const usernameIdToEdit = ref('')
const usernameDescriptionToEdit = ref('')
const deleteUsernameLoading = ref(false)
const deleteUsernameModalOpen = ref(false)
const usernameDefaultRecipientModalOpen = ref(false)
const makeDefaultLoading = ref(false)
const makeDefaultModalOpen = ref(false)
const usernameToMakeDefault = ref(null)
const moreInfoOpen = ref(false)
const defaultRecipientUsernameToEdit = ref({})
const defaultRecipientId = ref(null)
const editDefaultRecipientLoading = ref(false)
const tippyInstance = ref(null)
const errors = ref({})

const columns = [
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
  },
  {
    label: 'Default Recipient',
    field: 'default_recipient',
    sortable: false,
    globalSearchDisabled: true,
  },
  {
    label: 'Alias Count',
    field: 'aliases_count',
    type: 'number',
    globalSearchDisabled: true,
  },
  {
    label: 'Active',
    field: 'active',
    type: 'boolean',
    sortable: false,
    globalSearchDisabled: true,
  },
  {
    label: 'Catch-All',
    field: 'catch_all',
    type: 'boolean',
    sortable: false,
    globalSearchDisabled: true,
  },
  {
    label: '',
    field: 'actions',
    sortable: false,
    globalSearchDisabled: true,
  },
]

const addNewUsername = () => {
  addUsernameLoading.value = true

  axios
    .post(
      '/api/v1/usernames',
      JSON.stringify({
        username: newUsername.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(({ data }) => {
      addUsernameLoading.value = false
      rows.value.push(data.data)
      newUsername.value = ''
      addUsernameModalOpen.value = false
      debounceToolips()
      successMessage('Username added')
    })
    .catch(error => {
      addUsernameLoading.value = false

      if (error.response.status === 403) {
        errorMessage(error.response.data)
      } else if (error.response.status == 422) {
        errorMessage(error.response.data.errors.username[0])
      } else {
        errorMessage()
      }
    })
}

const editUsername = username => {
  if (usernameDescriptionToEdit.value.length > 200) {
    return errorMessage('Description cannot be more than 200 characters')
  }

  axios
    .patch(
      `/api/v1/usernames/${username.id}`,
      JSON.stringify({
        description: usernameDescriptionToEdit.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      username.description = usernameDescriptionToEdit.value
      usernameIdToEdit.value = ''
      usernameDescriptionToEdit.value = ''
      successMessage('Username description updated')
    })
    .catch(error => {
      usernameIdToEdit.value = ''
      usernameDescriptionToEdit.value = ''
      errorMessage()
    })
}

const editDefaultRecipient = () => {
  editDefaultRecipientLoading.value = true
  axios
    .patch(
      `/api/v1/usernames/${defaultRecipientUsernameToEdit.value.id}/default-recipient`,
      JSON.stringify({
        default_recipient: defaultRecipientId.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      let username = _.find(rows.value, ['id', defaultRecipientUsernameToEdit.value.id])
      username.default_recipient = _.find(recipientOptions.value, ['id', defaultRecipientId.value])
      username.default_recipient_id = defaultRecipientId.value

      usernameDefaultRecipientModalOpen.value = false
      editDefaultRecipientLoading.value = false
      defaultRecipientId.value = null
      debounceToolips()
      successMessage("Username's default recipient updated")
    })
    .catch(error => {
      usernameDefaultRecipientModalOpen.value = false
      editDefaultRecipientLoading.value = false
      defaultRecipientId.value = null
      errorMessage()
    })
}

const makeDefaultUsername = username => {
  makeDefaultLoading.value = true

  axios
    .post(
      `/settings/default-username`,
      JSON.stringify({
        id: username.id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      makeDefaultLoading.value = false
      usePage().props.user.default_username_id = username.id
      document.getElementById('dropdown-username').innerText = username.username
      debounceToolips()
      closeMakeDefaultModal()
      successMessage('Default username updated')
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

const deleteUsername = id => {
  deleteUsernameLoading.value = true

  axios
    .delete(`/api/v1/usernames/${id}`)
    .then(response => {
      rows.value = _.reject(rows.value, username => username.id === id)
      deleteUsernameModalOpen.value = false
      deleteUsernameLoading.value = false
    })
    .catch(error => {
      errorMessage()
      deleteUsernameLoading.value = false
      deleteUsernameModalOpen.value = false
    })
}

const activateUsername = id => {
  axios
    .post(
      `/api/v1/active-usernames`,
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

const deactivateUsername = id => {
  axios
    .delete(`/api/v1/active-usernames/${id}`)
    .then(response => {
      //
    })
    .catch(error => {
      errorMessage()
    })
}

const enableCatchAll = id => {
  axios
    .post(
      `/api/v1/catch-all-usernames`,
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
      if (error.response !== undefined) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
}

const disableCatchAll = id => {
  axios
    .delete(`/api/v1/catch-all-usernames/${id}`)
    .then(response => {
      //
    })
    .catch(error => {
      if (error.response !== undefined) {
        errorMessage(error.response.data)
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

const isDefault = id => {
  return usePage().props.user.default_username_id === id
}

const openAddUsernameModal = () => {
  errors.value = {}
  newUsername.value = ''
  addUsernameModalOpen.value = true
}

const openDeleteModal = id => {
  deleteUsernameModalOpen.value = true
  usernameIdToDelete.value = id
}

const closeDeleteModal = () => {
  deleteUsernameModalOpen.value = false
  usernameIdToDelete.value = null
}

const openMakeDefaultModal = username => {
  makeDefaultModalOpen.value = true
  usernameToMakeDefault.value = username
}

const closeMakeDefaultModal = () => {
  makeDefaultModalOpen.value = false
  usernameToMakeDefault.value = null
}

const openUsernameDefaultRecipientModal = function (username) {
  usernameDefaultRecipientModalOpen.value = true
  defaultRecipientUsernameToEdit.value = username
  defaultRecipientId.value = username.default_recipient_id
}

const closeUsernameDefaultRecipientModal = function () {
  usernameDefaultRecipientModalOpen.value = false
  defaultRecipientUsernameToEdit.value = {}
  defaultRecipientId.value = null
}

const validateNewUsername = e => {
  errors.value = {}

  if (!newUsername.value) {
    errors.value.newUsername = 'Username is required'
  } else if (!validUsername(newUsername.value)) {
    errors.value.newUsername = 'Username must only contain letters and numbers'
  } else if (newUsername.value.length > 20) {
    errors.value.newUsername = 'Username cannot be greater than 20 characters'
  }

  if (!errors.value.newUsername) {
    addNewUsername()
  }

  e.preventDefault()
}

const validUsername = username => {
  let re = /^[a-zA-Z0-9]*$/
  return re.test(username)
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
