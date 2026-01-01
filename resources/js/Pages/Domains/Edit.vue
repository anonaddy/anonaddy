<template>
  <div>
    <Head title="Edit Domain" />
    <h1 id="primary-heading" class="sr-only">Edit Domain</h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900 dark:text-white">Edit Domain</h1>
        <p class="mt-2 text-sm text-grey-700 dark:text-grey-200">Make changes to your Domain</p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 dark:bg-grey-900">
      <div class="space-y-8 divide-y divide-grey-200">
        <div>
          <div class="flex items-center">
            <h3
              class="text-xl font-medium leading-6 text-grey-900 cursor-pointer tooltip dark:text-grey-100"
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
          <div v-if="domain.description" class="mt-2 text-sm text-grey-500 dark:text-grey-300">
            {{ domain.description }}
          </div>
        </div>
        <div class="pt-8">
          <div class="block text-lg font-medium text-grey-700 dark:text-grey-200">
            Domain 'From Name'
          </div>
          <p class="mt-1 text-base text-grey-700 dark:text-grey-200">
            The 'From Name' is shown when you send an email from an alias or reply anonymously to a
            forwarded email. If left blank, then the email alias itself will be used as the 'From
            Name' e.g. "example@{{ domain.domain }}".
          </p>
          <div class="mt-2 text-base text-grey-700 dark:text-grey-200">
            The 'From Name' that is used for an alias is determined by the following
            <b>priority</b>:

            <ul class="list-decimal list-inside text-grey-700 text-base mt-2 dark:text-grey-200">
              <li>Alias 'From Name'</li>
              <li>Username or <b>Custom Domain 'From Name'</b></li>
              <li>Global 'From Name' from the settings page</li>
            </ul>
          </div>
          <p class="mt-2 text-base text-grey-700 dark:text-grey-200">
            If you set the 'From Name' for this domain, it will override the global 'From Name'
            setting.
          </p>

          <div class="mb-6">
            <div class="mt-6 grid grid-cols-1 mb-4">
              <label
                for="from_name"
                class="block text-sm font-medium leading-6 text-grey-900 dark:text-white"
                >From Name</label
              >
              <div class="relative mt-2">
                <input
                  v-model="domain.from_name"
                  type="text"
                  name="from_name"
                  id="from_name"
                  class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 dark:bg-white/5 dark:text-white"
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
            class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
          >
            Update From Name
            <loader v-if="domain.fromNameLoading" />
          </button>
        </div>

        <div class="pt-8">
          <div class="block text-lg font-medium text-grey-700 dark:text-grey-200">
            Alias Auto Create Regex
          </div>
          <p class="mt-1 text-base text-grey-700 dark:text-grey-200">
            If you wish to create aliases on-the-fly but don't want to enable catch-all then you can
            enter a regular expression pattern below. If a new alias' local part matches the pattern
            then it will still be created on-the-fly even though catch-all is disabled.
          </p>
          <p class="mt-2 text-base text-grey-700 dark:text-grey-200">
            Note: <b>Catch-All must be disabled</b> to use alias automatic creation with regex.
          </p>
          <p class="mt-2 text-base text-grey-700 dark:text-grey-200">
            For example, if you only want aliases that start with "prefix" to be automatically
            created, use the regex
            <span class="bg-cyan-200 px-1 rounded-md dark:text-grey-900">^prefix</span>
          </p>
          <p class="mt-2 text-base text-grey-700 dark:text-grey-200">
            If you only want aliases that end with "suffix" to be automatically created, use the
            regex <span class="bg-cyan-200 px-1 rounded-md dark:text-grey-900">suffix$</span>
          </p>
          <p class="mt-2 text-base text-grey-700 dark:text-grey-200">
            If you want to make sure the local part is fully matched you can start your regex with
            <span class="bg-cyan-200 px-1 rounded-m dark:text-grey-900">^</span> and end it with
            <span class="bg-cyan-200 px-1 rounded-md dark:text-grey-900">$</span> e.g.
            <span class="bg-cyan-200 px-1 rounded-md dark:text-grey-900">^prefix.*suffix$</span>
            which would match "prefix-anything-here-suffix"
          </p>
          <p class="mt-2 text-base text-grey-700 dark:text-grey-200">
            You can use
            <a
              href="https://regex101.com/"
              class="text-indigo-800"
              target="_blank"
              rel="nofollow noreferrer noopener"
              >regex101.com</a
            >
            to help you write your regular expressions.
          </p>

          <div class="mb-6">
            <div class="mt-6 grid grid-cols-1 mb-4">
              <label
                for="auto_create_regex"
                class="block text-sm font-medium leading-6 text-grey-900 dark:text-white"
                >Auto Create Regex</label
              >
              <div class="relative mt-2">
                <input
                  v-model="domain.auto_create_regex"
                  type="text"
                  name="auto_create_regex"
                  id="auto_create_regex"
                  class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 dark:bg-white/5 dark:text-white"
                  :class="
                    errors.auto_create_regex
                      ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                      : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                  "
                  placeholder="^prefix"
                  aria-invalid="true"
                  aria-describedby="auto-create-regex-error"
                />
                <div
                  v-if="errors.auto_create_regex"
                  class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                >
                  <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                </div>
              </div>
              <p
                v-if="errors.auto_create_regex"
                class="mt-2 text-sm text-red-600"
                id="auto-create-regex-error"
              >
                {{ errors.auto_create_regex }}
              </p>
            </div>
          </div>

          <button
            @click="editAutoCreateRegex"
            :disabled="domain.autoCreateRegexLoading"
            class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
          >
            Update Auto Create Regex
            <loader v-if="domain.autoCreateRegexLoading" />
          </button>

          <div class="block text-lg font-medium text-grey-700 pt-8 dark:text-grey-200">
            Test Alias Auto Create Regex
          </div>
          <p class="mt-1 text-base text-grey-700 dark:text-grey-200">
            You can test whether an alias local part will match the above regex pattern and be
            automatically created by entering the local part (left of @ symbol) below.
          </p>
          <p class="mt-2 text-base text-grey-700 dark:text-grey-200">
            No aliases will be created when testing.
          </p>
          <div class="mb-6">
            <div class="mt-6 grid grid-cols-1 mb-4">
              <label
                for="auto_create_regex"
                class="block text-sm font-medium leading-6 text-grey-900 dark:text-white"
                >Alias Local Part</label
              >

              <div class="mt-2">
                <div class="flex">
                  <div class="relative w-full">
                    <input
                      v-model="domain.test_auto_create_regex_local_part"
                      type="text"
                      name="test_auto_create_regex_local_part"
                      id="test_auto_create_regex_local_part"
                      class="block w-full min-w-0 flex-1 rounded-none rounded-l-md border-0 py-2 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-sm sm:leading-6 dark:bg-white/5 dark:text-white"
                      :class="testAutoCreateRegexLocalPartClass"
                      placeholder="local-part"
                      aria-invalid="true"
                      aria-describedby="test-auto-create-regex-local-part-error"
                    />
                    <div
                      v-if="
                        errors.test_auto_create_regex_local_part ||
                        domain.testAutoCreateRegexSuccess === false
                      "
                      class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                    </div>
                    <div
                      v-if="domain.testAutoCreateRegexSuccess === true"
                      class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <CheckCircleIcon class="h-5 w-5 text-green-500" aria-hidden="true" />
                    </div>
                  </div>
                  <span
                    class="inline-flex items-center rounded-r-md border border-l-0 border-grey-300 px-3 text-grey-500 sm:text-sm dark:text-grey-300"
                    >@{{ domain.domain }}</span
                  >
                </div>
              </div>
              <p
                v-if="errors.test_auto_create_regex_local_part"
                class="mt-2 text-sm text-red-600"
                id="test-auto-create-regex-local-part-error"
              >
                {{ errors.test_auto_create_regex_local_part }}
              </p>
              <p
                v-if="domain.testAutoCreateRegexSuccess === false"
                class="mt-2 text-sm text-red-600"
                id="test-auto-create-regex-local-part-error"
              >
                The alias local part does not match the regular expression and would not be created
              </p>
              <p
                v-if="domain.testAutoCreateRegexSuccess === true"
                class="mt-2 text-sm text-green-600"
                id="test-auto-create-regex-local-part-error"
              >
                The alias local part matches the regular expression and would be created
              </p>
            </div>
          </div>

          <button
            @click="testAutoCreateRegex"
            :disabled="domain.testAutoCreateRegexLoading"
            class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
          >
            Test Auto Create Regex
            <loader v-if="domain.testAutoCreateRegexLoading" />
          </button>
        </div>

        <div class="pt-5">
          <span
            class="mt-2 text-sm text-grey-500 tooltip dark:text-grey-300"
            :data-tippy-content="$filters.formatDate(domain.updated_at)"
            >Last updated {{ $filters.timeAgo(domain.updated_at) }}.</span
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { notify } from '@kyvg/vue3-notification'
import { roundArrow } from 'tippy.js'
import tippy from 'tippy.js'
import { ExclamationCircleIcon, CheckCircleIcon } from '@heroicons/vue/20/solid'

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

const testAutoCreateRegexLocalPartClass = computed(() => {
  if (
    errors.value.test_auto_create_regex_local_part ||
    domain.value.testAutoCreateRegexSuccess === false
  ) {
    return 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
  }

  if (domain.value.testAutoCreateRegexSuccess === true) {
    return 'text-green-900 ring-green-300 placeholder:text-green-300 focus:ring-green-500'
  }

  return 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
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

const editAutoCreateRegex = () => {
  errors.value = {}

  if (domain.value.auto_create_regex !== null && domain.value.auto_create_regex.length > 100) {
    errors.value.auto_create_regex = "'Auto Create Regex' cannot be more than 100 characters"
    return errorMessage(errors.value.auto_create_regex)
  }

  domain.value.autoCreateRegexLoading = true

  axios
    .patch(
      `/api/v1/domains/${domain.value.id}`,
      JSON.stringify({
        auto_create_regex: domain.value.auto_create_regex,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      domain.value.autoCreateRegexLoading = false
      successMessage("Domain 'Auto Create Regex' updated")
    })
    .catch(error => {
      domain.value.autoCreateRegexLoading = false

      if (error.response.data.message !== undefined) {
        errors.value.auto_create_regex = error.response.data.message
        errorMessage(error.response.data.message)
      } else {
        errorMessage()
      }
    })
}

const testAutoCreateRegex = () => {
  domain.value.testAutoCreateRegexSuccess = null
  errors.value = {}

  if (domain.value.auto_create_regex === null) {
    return (errors.value.test_auto_create_regex_local_part =
      'You must first enter a regex pattern above')
  }

  // Validate alias local part
  if (
    domain.value.test_auto_create_regex_local_part !== null &&
    !validLocalPart(domain.value.test_auto_create_regex_local_part)
  ) {
    errors.value.test_auto_create_regex_local_part = "Invalid 'Alias Local Part'"
    return errorMessage(errors.value.test_auto_create_regex_local_part)
  }

  domain.value.testAutoCreateRegexLoading = true

  axios
    .post(
      '/test-auto-create-regex',
      JSON.stringify({
        resource: 'domain',
        local_part: domain.value.test_auto_create_regex_local_part,
        id: domain.value.id,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      domain.value.testAutoCreateRegexLoading = false

      if (response.data.success) {
        domain.value.testAutoCreateRegexSuccess = true
      } else {
        domain.value.testAutoCreateRegexSuccess = false
      }
    })
    .catch(error => {
      domain.value.testAutoCreateRegexLoading = false
      if (error.response.data.message !== undefined) {
        errors.value.test_auto_create_regex_local_part = error.response.data.message
        errorMessage(error.response.data.message)
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

const validLocalPart = part => {
  let re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))$/
  return re.test(part)
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
