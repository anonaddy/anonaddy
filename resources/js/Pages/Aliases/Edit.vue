<template>
  <div>
    <Head title="Edit Alias" />
    <h1 id="primary-heading" class="sr-only">Edit Alias</h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900 dark:text-white">Edit Alias</h1>
        <p class="mt-2 text-sm text-grey-700 dark:text-grey-200">Make changes to your alias</p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 dark:bg-grey-900">
      <div class="space-y-8 divide-y divide-grey-200 dark:divide-grey-400">
        <div>
          <div class="flex items-center">
            <span
              :class="`bg-${getAliasStatus().colour}-100`"
              class="tooltip outline-none h-4 w-4 rounded-full flex items-center justify-center mr-2"
              :data-tippy-content="getAliasStatus().status"
              tabindex="-1"
            >
              <span
                :class="`bg-${getAliasStatus().colour}-400`"
                class="h-2 w-2 rounded-full"
              ></span>
            </span>
            <h3
              class="text-xl font-medium leading-6 text-grey-900 cursor-pointer tooltip"
              data-tippy-content="Click to copy"
              @click="clipboard(getAliasEmail(alias))"
            >
              <span class="font-semibold text-indigo-800 dark:text-indigo-400">{{
                getAliasLocalPart(alias)
              }}</span
              ><span class="font-semibold text-grey-500 dark:text-grey-200">{{
                '@' + alias.domain
              }}</span>
            </h3>
          </div>
          <div v-if="alias.description" class="mt-2 text-sm text-grey-500 dark:text-grey-300">
            {{ alias.description }}
          </div>
        </div>
        <div class="pt-8">
          <div class="block text-lg font-medium text-grey-700 dark:text-grey-200">
            Alias 'From Name'
          </div>
          <p class="mt-1 text-base text-grey-700 dark:text-grey-200">
            The 'From Name' is shown when you send an email from an alias or reply anonymously to a
            forwarded email. If left blank, then the email alias itself will be used as the 'From
            Name' e.g. "{{ alias.email }}".
          </p>
          <div class="mt-2 text-base text-grey-700 dark:text-grey-200">
            The 'From Name' that is used for an alias is determined by the following
            <b>priority</b>:

            <ul class="list-decimal list-inside text-grey-700 text-base mt-2 dark:text-grey-200">
              <li><b>Alias 'From Name'</b></li>
              <li>Username or Custom Domain 'From Name'</li>
              <li>Global 'From Name' from the settings page</li>
            </ul>
          </div>
          <p class="mt-2 text-base text-grey-700 dark:text-grey-200">
            If you set the 'From Name' for this specific alias, it will override the other settings.
          </p>

          <div class="mb-6">
            <div class="mt-6 grid grid-cols-1 mb-4">
              <label
                for="from_name"
                class="block text-sm font-medium leading-6 text-grey-900 dark:text-white"
                >Alias From Name</label
              >
              <div class="relative mt-2">
                <input
                  v-model="alias.from_name"
                  type="text"
                  name="from_name"
                  id="from_name"
                  class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 dark:text-white dark:bg-white/5"
                  :class="
                    errors.from_name
                      ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                      : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                  "
                  placeholder="John Doe"
                  aria-invalid="true"
                  aria-describedby="from-name-error"
                />
                <div
                  v-if="errors.from_name"
                  class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                >
                  <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                </div>
              </div>
              <p v-if="errors.from_name" class="mt-2 text-sm text-red-600" id="from-name-error">
                {{ errors.from_name }}
              </p>
            </div>
          </div>

          <button
            @click="editFromName"
            :disabled="alias.fromNameLoading"
            class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
          >
            Update Alias From Name
            <loader v-if="alias.fromNameLoading" />
          </button>
        </div>

        <div class="pt-8">
          <label
            for="can_reply_send"
            class="block font-medium text-grey-700 dark:text-grey-200 text-lg pointer-events-none cursor-default"
            >Limit Replies/Sends to attached recipients only</label
          >
          <p class="mt-1 text-base text-grey-700 dark:text-grey-200">
            Toggle this option to only allow verified recipients that are <b>directly</b> attached
            to this alias to reply or send from it. If this option is enabled and no recipients are
            directly attached then it will <b>not be possible to reply/send</b> from this alias.
          </p>
          <Toggle
            id="can_reply_send"
            class="mt-4"
            v-model="alias.attached_recipients_only"
            @on="enableAttachedRecipientsOnly"
            @off="disableAttachedRecipientsOnly"
          />
        </div>

        <div class="pt-5">
          <span
            class="mt-2 text-sm text-grey-500 dark:text-grey-300 tooltip"
            :data-tippy-content="$filters.formatDate(alias.updated_at)"
            >Last updated {{ $filters.timeAgo(alias.updated_at) }}.</span
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { notify } from '@kyvg/vue3-notification'
import { roundArrow } from 'tippy.js'
import tippy from 'tippy.js'
import { ExclamationCircleIcon } from '@heroicons/vue/20/solid'
import Toggle from '../../Components/Toggle.vue'

const props = defineProps({
  initialAlias: {
    type: Object,
    required: true,
  },
})

const alias = ref(props.initialAlias)

const errors = ref({})

const tippyInstance = ref(null)

onMounted(() => {
  addTooltips()
})

const editFromName = () => {
  errors.value = {}

  if (alias.value.from_name !== null && alias.value.from_name.length > 50) {
    errors.value.from_name = "'From Name' cannot be more than 50 characters"
    return errorMessage(errors.value.from_name)
  }

  alias.value.fromNameLoading = true

  axios
    .patch(
      `/api/v1/aliases/${alias.value.id}`,
      JSON.stringify({
        from_name: alias.value.from_name,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      alias.value.fromNameLoading = false
      successMessage("Alias 'From Name' updated")
    })
    .catch(error => {
      alias.value.fromNameLoading = false
      errorMessage()
    })
}

const enableAttachedRecipientsOnly = () => {
  axios
    .post(
      `/api/v1/attached-recipients-only`,
      JSON.stringify({
        id: alias.value.id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      successMessage('Attached recipients only enabled')
    })
    .catch(error => {
      errorMessage()
    })
}

const disableAttachedRecipientsOnly = () => {
  axios
    .delete(`/api/v1/attached-recipients-only/${alias.value.id}`)
    .then(response => {
      successMessage('Attached recipients only disabled')
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

const getAliasEmail = alias => {
  return alias.extension ? `${alias.local_part}+${alias.extension}@${alias.domain}` : alias.email
}

const getAliasLocalPart = alias => {
  return alias.extension ? `${alias.local_part}+${alias.extension}` : alias.local_part
}

const getAliasStatus = () => {
  if (alias.value.deleted_at) {
    return {
      colour: 'red',
      status: 'Deleted',
    }
  } else {
    return {
      colour: alias.value.active ? 'green' : 'grey',
      status: alias.value.active ? 'Active' : 'Inactive',
    }
  }
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
