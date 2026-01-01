<template>
  <div class="antialiased flex bg-grey-50 text-grey-700 dark:bg-grey-800 dark:text-white">
    <!-- Narrow sidebar -->
    <div class="h-screen hidden w-28 bg-indigo-900 overflow-y-auto md:block md:fixed">
      <div class="h-full pb-6 pt-4 flex flex-col items-center">
        <div class="shrink-0 flex items-center">
          <Link :href="route('dashboard.index')">
            <img class="h-10" alt="addy.io Logo" src="/svg/icon-logo.svg" />
          </Link>
        </div>
        <div class="flex-1 grow mt-6 w-full px-2 space-y-1">
          <Link
            v-for="item in sidebarNavigation"
            :key="item.name"
            :href="route(item.route)"
            :class="[
              item.route.startsWith(route().current().split('.')[0])
                ? 'bg-indigo-800 text-white'
                : 'text-indigo-50 hover:bg-indigo-800 hover:text-white',
              'group w-full p-3 rounded-md flex flex-col items-center text-center text-xs font-medium',
            ]"
            :aria-current="
              item.route.startsWith(route().current().split('.')[0]) ? 'page' : undefined
            "
          >
            <component
              :is="item.icon"
              :class="[
                item.route.startsWith(route().current().split('.')[0])
                  ? 'text-white'
                  : 'text-indigo-300 group-hover:text-white',
                'h-6 w-6',
              ]"
              aria-hidden="true"
            />
            <span class="mt-2">{{ item.name }}</span>
          </Link>
        </div>
        <div v-if="$page.props.version" class="text-indigo-200 shrink-0">
          <a
            :href="`https://github.com/anonaddy/anonaddy/releases/tag/v${$page.props.version}`"
            target="_blank"
            rel="nofollow noreferrer noopener"
            class="block sm:inline"
            >v{{ $page.props.version }}</a
          >
        </div>
        <div
          v-if="$page.props.updateAvailable"
          class="text-indigo-50 shrink-0 text-center text-sm font-semibold px-2"
        >
          <a
            href="https://github.com/anonaddy/anonaddy/releases/latest"
            target="_blank"
            rel="nofollow noreferrer noopener"
            class="block sm:inline"
            >Update Available</a
          >
        </div>
      </div>
    </div>

    <!-- Mobile menu -->
    <TransitionRoot as="template" :show="mobileMenuOpen">
      <Dialog as="div" class="relative z-20 md:hidden" @close="mobileMenuOpen = false">
        <TransitionChild
          as="template"
          enter="transition-opacity ease-linear duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="transition-opacity ease-linear duration-300"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-grey-600 bg-opacity-75" />
        </TransitionChild>

        <div class="fixed inset-0 z-40 flex">
          <TransitionChild
            as="template"
            enter="transition ease-in-out duration-300 transform"
            enter-from="-translate-x-full"
            enter-to="translate-x-0"
            leave="transition ease-in-out duration-300 transform"
            leave-from="translate-x-0"
            leave-to="-translate-x-full"
          >
            <DialogPanel
              class="relative max-w-xs w-full bg-indigo-900 pt-5 pb-4 flex-1 flex flex-col"
            >
              <TransitionChild
                as="template"
                enter="ease-in-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in-out duration-300"
                leave-from="opacity-100"
                leave-to="opacity-0"
              >
                <div class="absolute top-1 right-0 -mr-14 p-1">
                  <button
                    type="button"
                    class="h-12 w-12 rounded-full flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-white"
                    @click="mobileMenuOpen = false"
                  >
                    <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true" />
                    <span class="sr-only">Close sidebar</span>
                  </button>
                </div>
              </TransitionChild>
              <div class="flex-shrink-0 px-4 flex items-center">
                <Link @click="mobileMenuOpen = false" :href="route('dashboard.index')">
                  <img class="h-6" alt="addy.io Logo" src="/svg/icon-logo.svg" />
                </Link>
              </div>
              <div class="mt-5 flex-1 h-0 px-2 overflow-y-auto flex flex-col">
                <nav class="flex flex-col grow">
                  <div class="space-y-1">
                    <Link
                      v-for="item in sidebarNavigation"
                      :key="item.name"
                      @click="mobileMenuOpen = false"
                      :href="route(item.route)"
                      :class="[
                        item.route.startsWith(route().current().split('.')[0])
                          ? 'bg-indigo-800 text-white'
                          : 'text-indigo-100 hover:bg-indigo-800 hover:text-white',
                        'group py-2 px-3 rounded-md flex items-center text-sm font-medium',
                      ]"
                      :aria-current="$page.component.startsWith(item.name) ? 'page' : undefined"
                    >
                      <component
                        :is="item.icon"
                        :class="[
                          item.route.startsWith(route().current().split('.')[0])
                            ? 'text-white'
                            : 'text-indigo-300 group-hover:text-white',
                          'mr-3 h-6 w-6',
                        ]"
                        aria-hidden="true"
                      />
                      <span>{{ item.name }}</span>
                    </Link>
                  </div>
                </nav>
                <div v-if="$page.props.version" class="text-indigo-200 shrink-0 text-center">
                  <a
                    :href="`https://github.com/anonaddy/anonaddy/releases/tag/v${$page.props.version}`"
                    target="_blank"
                    rel="nofollow noreferrer noopener"
                    class="block sm:inline"
                    >v{{ $page.props.version }}</a
                  >
                </div>
                <div
                  v-if="$page.props.updateAvailable"
                  class="text-indigo-50 shrink-0 text-center text-sm font-semibold px-2"
                >
                  <a
                    href="https://github.com/anonaddy/anonaddy/releases/latest"
                    target="_blank"
                    rel="nofollow noreferrer noopener"
                    class="block sm:inline"
                    >Update Available</a
                  >
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
          <div class="flex-shrink-0 w-14" aria-hidden="true">
            <!-- Dummy element to force sidebar to shrink to fit close icon -->
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Content area -->
    <div class="flex-1 flex flex-col overflow-hidden min-h-screen md:pl-28">
      <header class="w-full">
        <div
          class="relative z-10 flex-shrink-0 h-16 bg-white border-b border-grey-200 shadow-sm flex dark:bg-grey-900 dark:border-grey-600"
        >
          <button
            type="button"
            class="border-r border-grey-200 px-4 text-grey-500 focus:outline-none md:hidden dark:border-grey-600"
            @click="mobileMenuOpen = true"
          >
            <span class="sr-only">Open sidebar</span>
            <Bars3Icon class="h-6 w-6" aria-hidden="true" />
          </button>
          <div class="max-w-screen-2xl mx-auto flex-1 flex justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex-1 flex items-center">
              <form @submit.prevent="submitSearchForm()" class="w-full flex md:ml-0">
                <label for="search-input" class="sr-only">Search all resources</label>
                <div
                  class="relative w-full text-grey-400 focus-within:text-grey-600 dark:text-white dark:focus-within:text-white"
                >
                  <div class="pointer-events-none absolute inset-y-0 left-1.5 flex items-center">
                    <MagnifyingGlassIcon class="flex-shrink-0 h-5 w-5" aria-hidden="true" />
                  </div>
                  <input
                    @keyup.esc="
                      search
                        ? $inertia.visit(
                            route(route().current(), omit(route().params, ['search', 'page'])),
                            {
                              only: ['initialRows', 'search'],
                            },
                          )
                        : null
                    "
                    name="search-input"
                    id="search-input"
                    v-model="searchForm.search"
                    class="h-full w-full leading-none border-indigo-50 border-2 py-2 pl-8 pr-3 text-base text-grey-900 placeholder-grey-500 focus:outline-none focus:border-indigo-50npm focus:ring-0 focus:placeholder-grey-400 outline-none rounded-l-md bg-indigo-50 dark:bg-white/5 dark:placeholder-grey-200 dark:border-grey-400 dark:text-white"
                    placeholder="Search"
                    type="search"
                  />
                  <div
                    v-if="search"
                    @click="
                      ;((searchForm.search = ''),
                        $inertia.visit(
                          route(route().current(), omit(route().params, ['search', 'page'])),
                          {
                            only: ['initialRows', 'search'],
                          },
                        ))
                    "
                    class="absolute inset-y-0 right-0 cursor-pointer flex items-center pr-3 rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                  >
                    <XMarkIcon class="h-5 w-5 text-grey-400 dark:text-white" aria-hidden="true" />
                  </div>
                </div>
              </form>
              <Listbox as="div" v-model="searchTypeSelected">
                <ListboxLabel class="sr-only"> Change Search Type </ListboxLabel>
                <div class="relative">
                  <div class="inline-flex shadow-sm rounded-r-md divide-x divide-indigo-600">
                    <div
                      class="relative z-0 inline-flex shadow-sm rounded-md divide-x divide-indigo-600"
                    >
                      <div
                        class="relative hidden md:inline-flex items-center bg-indigo-500 py-2 pl-3 pr-4 border border-transparent shadow-sm text-white"
                      >
                        <p class="text-sm font-medium whitespace-nowrap">
                          {{ searchTypeSelected.title }}
                        </p>
                      </div>
                      <ListboxButton
                        class="relative inline-flex items-center bg-indigo-500 p-2 rounded-l-none rounded-r-md text-sm font-medium text-white hover:bg-indigo-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 focus:z-10"
                        style="height: 39px"
                      >
                        <span class="sr-only">Change published status</span>
                        <ChevronDownIcon class="h-5 w-5 text-white" aria-hidden="true" />
                      </ListboxButton>
                    </div>
                  </div>

                  <transition
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                  >
                    <ListboxOptions
                      class="origin-top-right absolute z-10 right-0 mt-2 w-64 rounded-md shadow-lg overflow-hidden bg-white divide-y divide-grey-200 ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-grey-900 dark:text-white dark:divide-grey-500"
                    >
                      <ListboxOption
                        as="template"
                        v-for="option in searchOptions"
                        :key="option.title"
                        :value="option"
                        v-slot="{ active, selected }"
                      >
                        <li
                          :class="[
                            active
                              ? 'text-white bg-indigo-500'
                              : 'text-grey-900 dark:text-grey-100',
                            'cursor-pointer select-none relative p-3 text-sm',
                          ]"
                        >
                          <div class="flex flex-col">
                            <div class="flex justify-between">
                              <p :class="selected ? 'font-semibold' : 'font-normal'">
                                {{ option.title }}
                              </p>
                              <span
                                v-if="selected"
                                :class="
                                  active ? 'text-white' : 'text-indigo-500 dark:text-grey-100'
                                "
                              >
                                <CheckIcon class="h-5 w-5" aria-hidden="true" />
                              </span>
                            </div>
                            <p
                              :class="[
                                active
                                  ? 'text-indigo-50 dark:text-white'
                                  : 'text-grey-500 dark:text-grey-100',
                                'mt-2',
                              ]"
                            >
                              {{ option.description }}
                            </p>
                          </div>
                        </li>
                      </ListboxOption>
                    </ListboxOptions>
                  </transition>
                </div>
              </Listbox>
            </div>
            <div class="ml-2 flex items-center space-x-4 sm:ml-6 sm:space-x-6">
              <!-- Profile dropdown -->
              <Menu as="div" class="relative flex-shrink-0" role="menu">
                <div>
                  <MenuButton
                    class="bg-white rounded-sm flex text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-grey-900"
                  >
                    <span class="sr-only">Open user menu</span>
                    <span id="dropdown-username" class="ml-2 md:ml-0 font-medium">{{
                      $page.props.user.username
                    }}</span>
                    <svg
                      class="ml-1 h-5 w-5 fill-current"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 24 24"
                    >
                      <path
                        d="M15.3 9.3a1 1 0 0 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 1.4-1.4l3.3 3.29 3.3-3.3z"
                      ></path>
                    </svg>
                  </MenuButton>
                </div>
                <transition
                  enter-active-class="transition ease-out duration-100"
                  enter-from-class="transform opacity-0 scale-95"
                  enter-to-class="transform opacity-100 scale-100"
                  leave-active-class="transition ease-in duration-75"
                  leave-from-class="transform opacity-100 scale-100"
                  leave-to-class="transform opacity-0 scale-95"
                >
                  <MenuItems
                    class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-grey-900"
                  >
                    <MenuItem v-slot="{ active }" as="div">
                      <a
                        href="https://app.addy.io/docs/"
                        target="_blank"
                        rel="nofollow noreferrer noopener"
                        :class="[
                          active ? 'bg-indigo-500 text-white' : 'text-grey-700 dark:text-white',
                          'block w-full px-4 py-2 text-base leading-5 text-left focus:outline-none focus:bg-grey-100 transition dark:focus:bg-grey-700',
                        ]"
                      >
                        <span class="sr-only">API Docs</span>
                        <span class="flex justify-between items-center">
                          API Docs
                          <ArrowTopRightOnSquareIcon
                            class="h-4 w-4 inline-block ml-2"
                            aria-hidden="true"
                          />
                        </span>
                      </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }" as="div">
                      <a
                        href="https://addy.io/blog/"
                        target="_blank"
                        rel="nofollow noreferrer noopener"
                        :class="[
                          active ? 'bg-indigo-500 text-white' : 'text-grey-700 dark:text-white',
                          'block w-full px-4 py-2 text-base leading-5 text-left focus:outline-none focus:bg-grey-100 transition dark:focus:bg-grey-700',
                        ]"
                      >
                        <span class="sr-only">Blog</span>
                        <span class="flex justify-between items-center">
                          Blog
                          <ArrowTopRightOnSquareIcon
                            class="h-4 w-4 inline-block ml-2"
                            aria-hidden="true"
                          />
                        </span>
                      </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }" as="div">
                      <a
                        href="https://addy.io/help/"
                        target="_blank"
                        rel="nofollow noreferrer noopener"
                        :class="[
                          active ? 'bg-indigo-500 text-white' : 'text-grey-700 dark:text-white',
                          'block w-full px-4 py-2 text-base leading-5 text-left focus:outline-none focus:bg-grey-100 transition dark:focus:bg-grey-700',
                        ]"
                      >
                        <span class="sr-only">Help</span>
                        <span class="flex justify-between items-center">
                          Help
                          <ArrowTopRightOnSquareIcon
                            class="h-4 w-4 inline-block ml-2"
                            aria-hidden="true"
                          />
                        </span>
                      </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }" as="div">
                      <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        type="button"
                        class="w-full px-4 py-2 bg-transparent hover:bg-indigo-500 hover:text-white cursor-pointer text-left focus:bg-grey-100 dark:text-white dark:focus:bg-grey-700"
                      >
                        <span class="sr-only">Logout</span>
                        Logout
                      </Link>
                    </MenuItem>
                  </MenuItems>
                </transition>
              </Menu>
            </div>
          </div>
        </div>
      </header>

      <main class="overflow-y-visible">
        <section
          aria-labelledby="primary-heading"
          class="min-w-0 h-full px-4 sm:px-6 lg:px-8 max-w-screen-2xl mx-auto py-6"
        >
          <!-- Main content -->
          <slot />
        </section>
      </main>
    </div>
    <notifications position="bottom right" />

    <FlashNotification v-if="$page.props.flash">
      <template v-slot:icon>
        <CheckCircleIcon class="h-6 w-6 text-white" aria-hidden="true" />
      </template>
      <template v-slot:message>
        {{ $page.props.flash }}
      </template>
    </FlashNotification>
  </div>
</template>

<script setup>
import { router, useForm, usePage, Link } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import {
  Dialog,
  DialogPanel,
  Menu,
  MenuButton,
  MenuItem,
  MenuItems,
  TransitionChild,
  TransitionRoot,
  Listbox,
  ListboxButton,
  ListboxLabel,
  ListboxOption,
  ListboxOptions,
} from '@headlessui/vue'
import {
  Cog6ToothIcon,
  UsersIcon,
  HomeIcon,
  Bars3Icon,
  InboxArrowDownIcon,
  XMarkIcon,
  GlobeAltIcon,
  AtSymbolIcon,
  ExclamationTriangleIcon,
  FunnelIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/solid'
import { CheckIcon, ChevronDownIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/20/solid'
import FlashNotification from './../Components/FlashNotification.vue'

const props = defineProps({
  search: {
    type: String,
  },
})

const sidebarNavigation = [
  { name: 'Dashboard', route: 'dashboard.index', icon: HomeIcon },
  { name: 'Aliases', route: 'aliases.index', icon: AtSymbolIcon },
  { name: 'Recipients', route: 'recipients.index', icon: InboxArrowDownIcon },
  { name: 'Usernames', route: 'usernames.index', icon: UsersIcon },
  { name: 'Domains', route: 'domains.index', icon: GlobeAltIcon },
  { name: 'Rules', route: 'rules.index', icon: FunnelIcon },
  {
    name: 'Failed Deliveries',
    route: 'failed_deliveries.index',
    icon: ExclamationTriangleIcon,
  },
  { name: 'Settings', route: 'settings.show', icon: Cog6ToothIcon },
]

const mobileMenuOpen = ref(false)

const searchForm = useForm({
  search: props.search ?? '',
})

const searchOptions = [
  { title: 'Aliases', route: 'aliases.index', description: 'Search by email or description' },
  { title: 'Recipients', route: 'recipients.index', description: 'Search by email' },
  {
    title: 'Usernames',
    route: 'usernames.index',
    description: 'Search by username or description',
  },
  { title: 'Domains', route: 'domains.index', description: 'Search by domain or description' },
  { title: 'Rules', route: 'rules.index', description: 'Search by name' },
  {
    title: 'Failed Deliveries',
    route: 'failed_deliveries.index',
    description: 'Search by error message',
  },
]

const searchTypeSelected = ref(
  _.find(searchOptions, ['title', _.startCase(usePage().component.split('/')[0])]) ??
    searchOptions[0],
)

watch(
  () => usePage().component,
  function (component) {
    searchTypeSelected.value =
      _.find(searchOptions, ['title', _.startCase(component.split('/')[0])]) ?? searchOptions[0]

    if (!props.search) {
      searchForm.search = ''
    }
  },
)

watch(
  () => props.search,
  function (search) {
    if (!search) {
      searchForm.search = ''
    }
  },
)

const submitSearchForm = () => {
  if (!searchForm.search.length && props.search) {
    router.visit(route(route().current(), _.omit(route().params, ['search', 'page', 'id'])), {
      only: ['initialRows', 'search'],
    })
  } else if (searchForm.search.length > 1) {
    searchForm.get(
      route(searchTypeSelected.value.route, _.omit(route().params, ['search', 'page', 'id'])),
      {
        only: ['initialRows', 'search'],
      },
    )
  }
}

const omit = (object, key) => {
  return _.omit(object, key)
}
</script>
