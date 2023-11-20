<template>
  <div>
    <Head title="Edit Domain" />
    <h1 id="primary-heading" class="sr-only">Edit Domain</h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900">Edit Domain</h1>
        <p class="mt-2 text-sm text-grey-700">Make changes to your Domain</p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
      <div class="space-y-8 divide-y divide-grey-200">
        <div>
          <div class="flex items-center">
            <h3
              class="text-xl font-medium leading-6 text-grey-900 cursor-pointer tooltip"
              data-tippy-content="Click to copy"
              @click="clipboard(domain.domain)"
            >
              {{ domain.domain }}
            </h3>
            <div
              v-if="domain.domain_sending_verified_at || domain.domain_mx_validated_at"
              class="ml-2"
            >
              <svg
                v-if="domain.domain_sending_verified_at && domain.domain_mx_validated_at"
                class="h-5 w-5 inline-block tooltip"
                data-tippy-content="Domain fully verified"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
              >
                <g fill="none" fill-rule="evenodd">
                  <circle class="text-green-100 fill-current" cx="10" cy="10" r="10"></circle>
                  <polyline
                    class="text-green-800 stroke-current"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    points="6 10 8.667 12.667 14 7.333"
                  ></polyline>
                </g>
              </svg>
              <svg
                v-else-if="!domain.domain_mx_validated_at"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                class="h-5 w-5 inline-block tooltip"
                data-tippy-content="MX records invalid"
              >
                <g fill="none" fill-rule="evenodd">
                  <circle cx="10" cy="10" r="10" fill="#FF9B9B"></circle>
                  <polyline
                    stroke="#AB091E"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    points="14 6 6 14"
                  ></polyline>
                  <polyline
                    stroke="#AB091E"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    points="6 6 14 14"
                  ></polyline>
                </g>
              </svg>
              <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                class="h-5 w-5 inline-block tooltip"
                data-tippy-content="DNS records for sending invalid"
              >
                <g fill="none" fill-rule="evenodd">
                  <circle cx="10" cy="10" r="10" fill="#FF9B9B"></circle>
                  <polyline
                    stroke="#AB091E"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    points="14 6 6 14"
                  ></polyline>
                  <polyline
                    stroke="#AB091E"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    points="6 6 14 14"
                  ></polyline>
                </g>
              </svg>
            </div>
          </div>
          <div v-if="domain.description" class="mt-2 text-sm text-grey-500">
            {{ domain.description }}
          </div>
        </div>
        <div class="pt-8">
          <div class="block text-lg font-medium text-grey-700">Domain 'From Name'</div>
          <p class="mt-1 text-base text-grey-700">
            The 'From Name' is shown when you send an email from an alias or reply anonymously to a
            forwarded email. If left blank, then the email alias itself will be used as the 'From
            Name' e.g. "example@{{ domain.domain }}".
          </p>
          <div class="mt-2 text-base text-grey-700">
            The 'From Name' that is used for an alias is determined by the following
            <b>priority</b>:

            <ul class="list-decimal list-inside text-grey-700 text-base mt-2">
              <li>Alias 'From Name'</li>
              <li>Username or <b>Custom Domain 'From Name'</b></li>
              <li>Global 'From Name' from the settings page</li>
            </ul>
          </div>
          <p class="mt-2 text-base text-grey-700">
            If you set the 'From Name' for this domain, it will override the global 'From Name'
            setting.
          </p>

          <div class="mb-6">
            <div class="mt-6 grid grid-cols-1 mb-4">
              <label for="from_name" class="block text-sm font-medium leading-6 text-grey-900"
                >From Name</label
              >
              <div class="relative mt-2">
                <input
                  v-model="domain.from_name"
                  type="text"
                  name="from_name"
                  id="from_name"
                  class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
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
            :disabled="domain.fromNameLoading"
            class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none disabled:cursor-not-allowed"
          >
            Update From Name
            <loader v-if="domain.fromNameLoading" />
          </button>
        </div>

        <div class="pt-5">
          <span
            class="mt-2 text-sm text-grey-500 tooltip"
            :data-tippy-content="$filters.formatDate(domain.updated_at)"
            >Last updated {{ $filters.timeAgo(domain.updated_at) }}.</span
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

const props = defineProps({
  initialDomain: {
    type: Object,
    required: true,
  },
})

const domain = ref(props.initialDomain)

const errors = ref({})

const tippyInstance = ref(null)

onMounted(() => {
  addTooltips()
})

const editFromName = () => {
  errors.value = {}

  if (domain.value.from_name !== null && domain.value.from_name.length > 50) {
    errors.value.from_name = "'From Name' cannot be more than 50 characters"
    return errorMessage(errors.value.from_name)
  }

  domain.value.fromNameLoading = true

  axios
    .patch(
      `/api/v1/domains/${domain.value.id}`,
      JSON.stringify({
        from_name: domain.value.from_name,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      domain.value.fromNameLoading = false
      successMessage("Domain 'From Name' updated")
    })
    .catch(error => {
      domain.value.fromNameLoading = false
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
