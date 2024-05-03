<template>
  <div>
    <Head :title="$page.component" />
    <h1 id="primary-heading" class="sr-only">
      {{ $page.component }}
    </h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900">Rules</h1>
        <p class="mt-2 text-sm text-grey-700">
          A list of all the rules {{ search ? 'found for your search' : 'in your account' }}
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
          @click="openCreateModal"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 font-bold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:w-auto"
        >
          Create Rule
        </button>
      </div>
    </div>

    <div v-if="rows.length" class="bg-white shadow">
      <table class="table-auto w-full">
        <thead class="border-b border-grey-100 text-grey-400">
          <tr>
            <th scope="col" class="p-3"></th>
            <th scope="col" class="p-3 text-left">Created</th>
            <th scope="col" class="p-3 text-left">Name</th>
            <th scope="col" class="p-3 text-left">Active</th>
            <th scope="col" class="p-3 text-left">
              Applied
              <span
                class="tooltip outline-none"
                data-tippy-content="This is the number of times that the rule has been applied. Hover over the count to see when it was last applied."
              >
                <icon name="info" class="inline-block w-4 h-4 text-grey-300 fill-current" />
              </span>
            </th>
            <th scope="col" class="p-3"></th>
          </tr>
        </thead>
        <draggable
          :component-data="{ type: 'transition', name: 'flip-list' }"
          v-model="rows"
          item-key="id"
          tag="tbody"
          handle=".handle"
          :group="{ name: 'description' }"
          ghost-class="ghost"
          @change="reorderRules"
          @update="debounceToolips"
        >
          <template #item="{ element }">
            <tr class="border-b border-grey-100 h-20">
              <td scope="row" class="p-3">
                <icon
                  name="menu"
                  class="handle block w-6 h-6 text-grey-300 fill-current cursor-pointer"
                />
              </td>
              <td scope="row" class="p-3">
                <span
                  class="tooltip outline-none cursor-default text-sm text-grey-500"
                  :data-tippy-content="$filters.formatDate(element.created_at)"
                  >{{ $filters.timeAgo(element.created_at) }}
                </span>
              </td>
              <td scope="row" class="p-3">
                <span class="font-medium text-grey-700">{{ element.name }}</span>
              </td>
              <td scope="row" class="p-3">
                <Toggle
                  v-model="element.active"
                  @on="activateRule(element.id)"
                  @off="deactivateRule(element.id)"
                />
              </td>
              <td scope="row" class="p-3">
                <span
                  v-if="element.last_applied"
                  class="tooltip outline-none cursor-default font-semibold text-indigo-800"
                  :data-tippy-content="
                    $filters.timeAgo(element.last_applied) +
                    ' (' +
                    $filters.formatDate(element.last_applied) +
                    ')'
                  "
                  >{{ element.applied.toLocaleString() }}
                </span>
                <span v-else>{{ element.applied.toLocaleString() }} </span>
              </td>
              <td scope="row" class="p-3 text-right w-0 min-w-fit whitespace-nowrap">
                <button
                  @click="openEditModal(element)"
                  as="button"
                  type="button"
                  class="text-indigo-500 hover:text-indigo-800 font-medium"
                >
                  Edit
                </button>
                <button
                  @click="openDeleteModal(element.id)"
                  as="button"
                  type="button"
                  class="text-indigo-500 hover:text-indigo-800 font-medium ml-4"
                >
                  Delete
                </button>
              </td>
            </tr>
          </template>
        </draggable>
      </table>
    </div>

    <div v-else-if="search" class="text-center">
      <FunnelIcon class="mx-auto h-16 w-16 text-grey-400" />
      <h3 class="mt-2 text-lg font-medium text-grey-900">No Rules found for that search</h3>
      <p class="mt-1 text-md text-grey-500">Try entering a different search term.</p>
      <div class="mt-6">
        <Link
          :href="route('rules.index')"
          type="button"
          class="inline-flex items-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 text-sm font-medium shadow-sm focus:outline-none"
        >
          View All Rules
        </Link>
      </div>
    </div>

    <div v-else class="text-center">
      <FunnelIcon class="mx-auto h-16 w-16 text-grey-400" />
      <h3 class="mt-2 text-lg font-medium text-grey-900">No Rules</h3>
      <p class="mt-1 text-md text-grey-500">Get started by creating a new rule.</p>
      <div class="mt-6">
        <button
          @click="openCreateModal"
          type="button"
          class="inline-flex items-center rounded-md border border-transparent bg-cyan-400 hover:bg-cyan-300 text-cyan-900 px-4 py-2 text-sm font-medium shadow-sm focus:outline-none"
        >
          <PlusIcon class="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
          Create a Rule
        </button>
      </div>
    </div>

    <Modal
      :open="createRuleModalOpen"
      @close="createRuleModalOpen = false"
      max-width="md:max-w-2xl"
    >
      <template v-slot:title> Create new rule </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Rules work on all emails, including replies and also send froms. New conditions and
          actions will be added over time.
        </p>

        <label for="rule_name" class="block font-medium leading-6 text-grey-600 text-sm my-2">
          Name
        </label>
        <p v-show="errors.ruleName" class="mb-3 text-red-500 text-sm">
          {{ errors.ruleName }}
        </p>
        <input
          v-model="createRuleObject.name"
          id="rule_name"
          type="text"
          class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
          :class="errors.ruleName ? 'ring-red-500' : ''"
          placeholder="Enter name"
          autofocus
        />

        <fieldset class="border border-cyan-400 p-4 my-4 rounded-sm">
          <legend class="px-2 leading-none text-sm">Conditions</legend>

          <!-- Loop for conditions -->
          <div v-for="(condition, key) in createRuleObject.conditions" :key="key">
            <!-- AND/OR operator -->
            <div v-if="key !== 0" class="flex justify-center my-2">
              <div class="relative">
                <select
                  v-model="createRuleObject.operator"
                  :id="`create_rule_operator_${key}`"
                  class="block appearance-none w-full text-grey-700 bg-white p-2 pr-8 rounded shadow focus:ring"
                  required
                >
                  <option value="AND">AND</option>
                  <option value="OR">OR</option>
                </select>
              </div>
            </div>

            <div class="p-2 w-full bg-grey-100">
              <div class="flex">
                <div
                  class="w-full flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0"
                >
                  <span>If</span>
                  <span class="sm:ml-2">
                    <div class="relative">
                      <select
                        v-model="createRuleObject.conditions[key].type"
                        :id="`create_rule_condition_types_${key}`"
                        class="block appearance-none w-full sm:w-32 text-grey-700 bg-white p-2 pr-8 rounded shadow focus:ring"
                        required
                      >
                        <option
                          v-for="option in conditionTypeOptions"
                          :key="option.value"
                          :value="option.value"
                        >
                          {{ option.label }}
                        </option>
                      </select>
                    </div>
                  </span>

                  <span
                    v-if="conditionMatchOptions(createRuleObject, key).length"
                    class="sm:ml-4 flex flex-col sm:flex-row space-y-2 sm:space-y-0"
                  >
                    <div class="relative sm:mr-4">
                      <select
                        v-model="createRuleObject.conditions[key].match"
                        :id="`create_rule_condition_matches_${key}`"
                        class="block appearance-none w-full sm:w-40 text-grey-700 bg-white p-2 pr-8 rounded shadow focus:ring"
                        required
                      >
                        <option
                          v-for="option in conditionMatchOptions(createRuleObject, key)"
                          :key="option"
                          :value="option"
                        >
                          {{ option }}
                        </option>
                      </select>
                    </div>

                    <div class="flex">
                      <input
                        v-model="createRuleObject.conditions[key].currentConditionValue"
                        @keyup.enter="addValueToCondition(createRuleObect, key)"
                        type="text"
                        class="w-full appearance-none bg-white border border-transparent rounded-l text-grey-700 focus:outline-none p-2"
                        :class="errors.ruleConditions ? 'border-red-500' : ''"
                        placeholder="Enter value"
                        autofocus
                      />
                      <button
                        @click="addValueToCondition(createRuleObject, key)"
                        class="p-2 bg-grey-200 rounded-r text-grey-600"
                      >
                        Insert
                      </button>
                    </div>
                  </span>
                </div>
                <div class="flex items-center">
                  <!-- delete button -->
                  <icon
                    v-if="createRuleObject.conditions.length > 1"
                    name="trash"
                    class="block ml-4 w-6 h-6 text-grey-300 fill-current cursor-pointer"
                    @click="deleteCondition(createRuleObject, key)"
                  />
                </div>
              </div>
              <div class="mt-2 text-left">
                <span
                  v-for="(value, index) in createRuleObject.conditions[key].values"
                  :key="index"
                >
                  <span class="bg-green-200 text-sm font-semibold rounded-sm pl-1">
                    {{ value }}
                    <icon
                      name="close"
                      class="inline-block w-4 h-4 text-grey-900 fill-current cursor-pointer"
                      @click="createRuleObject.conditions[key].values.splice(index, 1)"
                    />
                  </span>
                  <span
                    class="mx-1"
                    v-if="index + 1 !== createRuleObject.conditions[key].values.length"
                  >
                    or
                  </span>
                </span>
              </div>
            </div>
          </div>
          <!-- add condition button -->
          <button
            @click="addCondition(createRuleObject)"
            class="mt-4 p-2 text-grey-800 bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Add condition
          </button>

          <p v-show="errors.ruleConditions" class="mt-2 text-red-500 text-sm">
            {{ errors.ruleConditions }}
          </p>
        </fieldset>

        <fieldset class="border border-cyan-400 p-4 my-4 rounded-sm">
          <legend class="px-2 leading-none text-sm">Actions</legend>

          <!-- Loop for actions -->
          <div v-for="(action, key) in createRuleObject.actions" :key="key">
            <!-- AND/OR operator -->
            <div v-if="key !== 0" class="flex justify-center my-2">
              <div class="relative">AND</div>
            </div>

            <div class="p-2 w-full bg-grey-100">
              <div class="flex">
                <div
                  class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:items-center w-full"
                >
                  <span>Then</span>
                  <span class="sm:ml-2">
                    <div class="relative">
                      <select
                        v-model="createRuleObject.actions[key].type"
                        @change="ruleActionChange(createRuleObject.actions[key])"
                        :id="`rule_action_types_${key}`"
                        class="w-full block appearance-none text-grey-700 bg-white p-2 pr-8 rounded shadow focus:ring"
                        required
                      >
                        <option
                          v-for="option in actionTypeOptions"
                          :key="option.value"
                          :value="option.value"
                        >
                          {{ option.label }}
                        </option>
                      </select>
                    </div>
                  </span>

                  <span
                    v-if="
                      createRuleObject.actions[key].type === 'subject' ||
                      createRuleObject.actions[key].type === 'displayFrom'
                    "
                    class="sm:ml-4 flex"
                  >
                    <div class="flex w-full">
                      <input
                        v-model="createRuleObject.actions[key].value"
                        type="text"
                        class="w-full appearance-none bg-white border border-transparent rounded text-grey-700 focus:outline-none p-2"
                        :class="errors.ruleActions ? 'border-red-500' : ''"
                        placeholder="Enter value"
                        autofocus
                      />
                    </div>
                  </span>

                  <span
                    v-else-if="createRuleObject.actions[key].type === 'banner'"
                    class="sm:ml-4 flex"
                  >
                    <div class="relative sm:mr-4 w-full">
                      <select
                        v-model="createRuleObject.actions[key].value"
                        :id="`create_rule_action_banner_${key}`"
                        class="w-full block appearance-none sm:w-40 text-grey-700 bg-white p-2 pr-8 rounded shadow focus:ring"
                        required
                      >
                        <option value="top">Top</option>
                        <option value="bottom">Bottom</option>
                        <option value="off">Off</option>
                      </select>
                    </div>
                  </span>
                </div>
                <div class="flex items-center">
                  <!-- delete button -->
                  <icon
                    v-if="createRuleObject.actions.length > 1"
                    name="trash"
                    class="block ml-4 w-6 h-6 text-grey-300 fill-current cursor-pointer"
                    @click="deleteAction(createRuleObject, key)"
                  />
                </div>
              </div>
            </div>
          </div>
          <!-- add action button -->
          <button
            @click="addAction(createRuleObject)"
            class="mt-4 p-2 text-grey-800 bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Add action
          </button>

          <p v-show="errors.ruleActions" class="mt-2 text-red-500 text-sm">
            {{ errors.ruleActions }}
          </p>
        </fieldset>

        <fieldset class="border border-cyan-400 p-4 my-4 rounded-sm">
          <legend class="px-2 leading-none text-sm">Apply rule on</legend>
          <div class="w-full flex">
            <div class="relative flex items-center">
              <input
                v-model="createRuleObject.forwards"
                id="forwards"
                name="forwards"
                type="checkbox"
                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-grey-300 rounded"
              />
              <label for="forwards" class="ml-2 text-sm text-grey-700">Forwards</label>
            </div>
            <div class="relative flex items-center mx-4">
              <input
                v-model="createRuleObject.replies"
                id="replies"
                name="replies"
                type="checkbox"
                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-grey-300 rounded"
              />
              <label for="replies" class="ml-2 text-sm text-grey-700">Replies</label>
            </div>
            <div class="relative flex items-center">
              <input
                v-model="createRuleObject.sends"
                id="sends"
                name="sends"
                type="checkbox"
                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-grey-300 rounded"
              />
              <label for="sends" class="ml-2 text-sm text-grey-700">Sends</label>
            </div>
          </div>
        </fieldset>

        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            @click="createNewRule"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="createRuleLoading"
          >
            Create Rule
            <loader v-if="createRuleLoading" />
          </button>
          <button
            @click="createRuleModalOpen = false"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="editRuleModalOpen" @close="closeEditModal" max-width="md:max-w-2xl">
      <template v-slot:title> Edit rule </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Rules work on all emails, including replies and also send froms. New conditions and
          actions will be added over time.
        </p>

        <label for="edit_rule_name" class="block font-medium leading-6 text-grey-600 text-sm my-2">
          Name
        </label>
        <p v-show="errors.ruleName" class="mb-3 text-red-500 text-sm">
          {{ errors.ruleName }}
        </p>
        <input
          v-model="editRuleObject.name"
          id="edit_rule_name"
          type="text"
          class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
          :class="errors.ruleName ? 'ring-red-500' : ''"
          placeholder="Enter name"
          autofocus
        />

        <fieldset class="border border-cyan-400 p-4 my-4 rounded-sm">
          <legend class="px-2 leading-none text-sm">Conditions</legend>

          <!-- Loop for conditions -->
          <div v-for="(condition, key) in editRuleObject.conditions" :key="key">
            <!-- AND/OR operator -->
            <div v-if="key !== 0" class="flex justify-center my-2">
              <div class="relative">
                <select
                  v-model="editRuleObject.operator"
                  :id="`edit_rule_operator_${key}`"
                  class="block appearance-none w-full text-grey-700 bg-white p-2 pr-8 rounded shadow focus:ring"
                  required
                >
                  <option value="AND">AND</option>
                  <option value="OR">OR</option>
                </select>
              </div>
            </div>

            <div class="p-2 w-full bg-grey-100">
              <div class="flex">
                <div
                  class="w-full flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0"
                >
                  <span>If</span>
                  <span class="sm:ml-2">
                    <div class="relative">
                      <select
                        v-model="editRuleObject.conditions[key].type"
                        :id="`edit_rule_condition_types_${key}`"
                        class="block appearance-none w-full sm:w-32 text-grey-700 bg-white p-2 pr-8 rounded shadow focus:ring"
                        required
                      >
                        <option
                          v-for="option in conditionTypeOptions"
                          :key="option.value"
                          :value="option.value"
                        >
                          {{ option.label }}
                        </option>
                      </select>
                    </div>
                  </span>

                  <span
                    v-if="conditionMatchOptions(editRuleObject, key).length"
                    class="sm:ml-4 flex flex-col sm:flex-row space-y-2 sm:space-y-0"
                  >
                    <div class="relative sm:mr-4">
                      <select
                        v-model="editRuleObject.conditions[key].match"
                        :id="`edit_rule_condition_matches_${key}`"
                        class="block appearance-none w-full sm:w-40 text-grey-700 bg-white p-2 pr-8 rounded shadow focus:ring"
                        required
                      >
                        <option
                          v-for="option in conditionMatchOptions(editRuleObject, key)"
                          :key="option"
                          :value="option"
                        >
                          {{ option }}
                        </option>
                      </select>
                    </div>

                    <div class="flex">
                      <input
                        v-model="editRuleObject.conditions[key].currentConditionValue"
                        @keyup.enter="addValueToCondition(editRuleObect, key)"
                        type="text"
                        class="w-full appearance-none bg-white border border-transparent rounded-l text-grey-700 focus:outline-none p-2"
                        :class="errors.ruleConditions ? 'border-red-500' : ''"
                        placeholder="Enter value"
                        autofocus
                      />
                      <button
                        @click="addValueToCondition(editRuleObject, key)"
                        class="p-2 bg-grey-200 rounded-r text-grey-600"
                      >
                        Insert
                      </button>
                    </div>
                  </span>
                </div>
                <div class="flex items-center">
                  <!-- delete button -->
                  <icon
                    v-if="editRuleObject.conditions.length > 1"
                    name="trash"
                    class="block ml-4 w-6 h-6 text-grey-300 fill-current cursor-pointer"
                    @click="deleteCondition(editRuleObject, key)"
                  />
                </div>
              </div>
              <div class="mt-2 text-left">
                <span v-for="(value, index) in editRuleObject.conditions[key].values" :key="index">
                  <span class="bg-green-200 text-sm font-semibold rounded-sm pl-1">
                    {{ value }}
                    <icon
                      name="close"
                      class="inline-block w-4 h-4 text-grey-900 fill-current cursor-pointer"
                      @click="editRuleObject.conditions[key].values.splice(index, 1)"
                    />
                  </span>
                  <span
                    class="mx-1"
                    v-if="index + 1 !== editRuleObject.conditions[key].values.length"
                  >
                    or
                  </span>
                </span>
              </div>
            </div>
          </div>
          <!-- add condition button -->
          <button
            @click="addCondition(editRuleObject)"
            class="mt-4 p-2 text-grey-800 bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Add condition
          </button>

          <p v-show="errors.ruleConditions" class="mt-2 text-red-500 text-sm">
            {{ errors.ruleConditions }}
          </p>
        </fieldset>

        <fieldset class="border border-cyan-400 p-4 my-4 rounded-sm">
          <legend class="px-2 leading-none text-sm">Actions</legend>

          <!-- Loop for actions -->
          <div v-for="(action, key) in editRuleObject.actions" :key="key">
            <!-- AND/OR operator -->
            <div v-if="key !== 0" class="flex justify-center my-2">
              <div class="relative">AND</div>
            </div>

            <div class="p-2 w-full bg-grey-100">
              <div class="flex">
                <div
                  class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:items-center w-full"
                >
                  <span>Then</span>
                  <span class="sm:ml-2">
                    <div class="relative">
                      <select
                        v-model="editRuleObject.actions[key].type"
                        @change="ruleActionChange(editRuleObject.actions[key])"
                        :id="`rule_action_types_${key}`"
                        class="w-full block appearance-none text-grey-700 bg-white p-2 pr-8 rounded shadow focus:ring"
                        required
                      >
                        <option
                          v-for="option in actionTypeOptions"
                          :key="option.value"
                          :value="option.value"
                        >
                          {{ option.label }}
                        </option>
                      </select>
                    </div>
                  </span>

                  <span
                    v-if="
                      editRuleObject.actions[key].type === 'subject' ||
                      editRuleObject.actions[key].type === 'displayFrom'
                    "
                    class="sm:ml-4 flex"
                  >
                    <div class="flex w-full">
                      <input
                        v-model="editRuleObject.actions[key].value"
                        type="text"
                        class="w-full appearance-none bg-white border border-transparent rounded text-grey-700 focus:outline-none p-2"
                        :class="errors.ruleActions ? 'border-red-500' : ''"
                        placeholder="Enter value"
                        autofocus
                      />
                    </div>
                  </span>

                  <span
                    v-else-if="editRuleObject.actions[key].type === 'banner'"
                    class="sm:ml-4 flex"
                  >
                    <div class="relative sm:mr-4 w-full">
                      <select
                        v-model="editRuleObject.actions[key].value"
                        :id="`edit_rule_action_banner_${key}`"
                        class="w-full block appearance-none sm:w-40 text-grey-700 bg-white p-2 pr-8 rounded shadow focus:ring"
                        required
                      >
                        <option value="top">Top</option>
                        <option value="bottom">Bottom</option>
                        <option value="off">Off</option>
                      </select>
                    </div>
                  </span>
                </div>
                <div class="flex items-center">
                  <!-- delete button -->
                  <icon
                    v-if="editRuleObject.actions.length > 1"
                    name="trash"
                    class="block ml-4 w-6 h-6 text-grey-300 fill-current cursor-pointer"
                    @click="deleteAction(editRuleObject, key)"
                  />
                </div>
              </div>
            </div>
          </div>
          <!-- add action button -->
          <button
            @click="addAction(editRuleObject)"
            class="mt-4 p-2 text-grey-800 bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Add action
          </button>

          <p v-show="errors.ruleActions" class="mt-2 text-red-500 text-sm">
            {{ errors.ruleActions }}
          </p>
        </fieldset>

        <fieldset class="border border-cyan-400 p-4 my-4 rounded-sm">
          <legend class="px-2 leading-none text-sm">Apply rule on</legend>
          <div class="w-full flex">
            <div class="relative flex items-center">
              <input
                v-model="editRuleObject.forwards"
                id="forwards"
                name="forwards"
                type="checkbox"
                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-grey-300 rounded"
              />
              <label for="forwards" class="ml-2 text-sm text-grey-700">Forwards</label>
            </div>
            <div class="relative flex items-center mx-4">
              <input
                v-model="editRuleObject.replies"
                id="replies"
                name="replies"
                type="checkbox"
                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-grey-300 rounded"
              />
              <label for="replies" class="ml-2 text-sm text-grey-700">Replies</label>
            </div>
            <div class="relative flex items-center">
              <input
                v-model="editRuleObject.sends"
                id="sends"
                name="sends"
                type="checkbox"
                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-grey-300 rounded"
              />
              <label for="sends" class="ml-2 text-sm text-grey-700">Sends</label>
            </div>
          </div>
        </fieldset>

        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            @click="editRule"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="editRuleLoading"
          >
            Save
            <loader v-if="editRuleLoading" />
          </button>
          <button
            @click="closeEditModal"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="deleteRuleModalOpen" @close="closeDeleteModal">
      <template v-slot:title> Delete rule </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">Are you sure you want to delete this rule?</p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="deleteRule(ruleIdToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="deleteRuleLoading"
          >
            Delete rule
            <loader v-if="deleteRuleLoading" />
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

    <Modal :open="moreInfoOpen" @close="moreInfoOpen = false">
      <template v-slot:title> More information </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700">
          Rules can be used to perform different actions if certain conditions are met.
        </p>
        <p class="mt-4 text-grey-700">
          For example you could create a rule that checks if the alias is for your custom domain and
          if so then to replace the email subject.
        </p>
        <p class="mt-4 text-grey-700">
          You can choose to apply rules on forwards, replies and/or sends.
        </p>
        <p class="mt-4 text-grey-700">
          Rules are applied in the order displayed on this page from top to bottom. You can re-order
          your rules by dragging them using the icon on the left of each row.
        </p>

        <div class="mt-6 flex flex-col">
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
import { onMounted, ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Modal from '../Components/Modal.vue'
import Toggle from '../Components/Toggle.vue'
import { roundArrow } from 'tippy.js'
import tippy from 'tippy.js'
import draggable from 'vuedraggable'
import { notify } from '@kyvg/vue3-notification'
import { InformationCircleIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import { PlusIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  initialRows: {
    type: Array,
    required: true,
  },
  search: {
    type: String,
  },
})

const rows = ref(props.initialRows)

const editRuleObject = ref({})
const ruleIdToDelete = ref('')
const deleteRuleLoading = ref(false)
const deleteRuleModalOpen = ref(false)
const createRuleModalOpen = ref(false)
const editRuleModalOpen = ref(false)
const moreInfoOpen = ref(false)
const createRuleLoading = ref(false)
const editRuleLoading = ref(false)
const createRuleObject = ref({
  name: '',
  conditions: [
    {
      type: 'select',
      match: 'contains',
      values: [],
    },
  ],
  actions: [
    {
      type: 'select',
      value: '',
    },
  ],
  operator: 'AND',
  forwards: false,
  replies: false,
  sends: false,
})
const tippyInstance = ref(null)
const errors = ref({})

const conditionTypeOptions = [
  {
    value: 'select',
    label: 'Select',
  },
  {
    value: 'sender',
    label: 'the sender',
  },
  {
    value: 'subject',
    label: 'the subject',
  },
  {
    value: 'alias',
    label: 'the alias',
  },
]
const actionTypeOptions = [
  {
    value: 'select',
    label: 'Select',
  },
  {
    value: 'subject',
    label: 'replace the subject with',
  },
  {
    value: 'displayFrom',
    label: 'replace the "from name" with',
  },
  {
    value: 'encryption',
    label: 'turn PGP encryption off',
  },
  {
    value: 'banner',
    label: 'set the banner information location to',
  },
  {
    value: 'block',
    label: 'block the email',
  },
]

const indexToHuman = {
  0: 'first',
  1: 'second',
  2: 'third',
  3: 'forth',
  4: 'fifth',
}

onMounted(() => {
  addTooltips()
})

const activeRules = () => {
  return _.filter(rows.value, rule => rule.active)
}

const rowsIds = computed(() => {
  return _.map(rows.value, row => row.id)
})

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

const createNewRule = () => {
  errors.value = {}

  if (!createRuleObject.value.name.length) {
    return (errors.value.ruleName = 'Please enter a rule name')
  }

  if (createRuleObject.value.name.length > 50) {
    return (errors.value.ruleName = 'Rule name cannot exceed 50 characters')
  }

  Object.entries(createRuleObject.value.conditions).forEach(([key, condition]) => {
    if (!condition.values.length) {
      return (errors.value.ruleConditions = `You must add some values for the ${indexToHuman[key]} condition, make sure to click "Insert"`)
    }
  })

  if (errors.value.ruleConditions) {
    return
  }

  Object.entries(createRuleObject.value.actions).forEach(([key, action]) => {
    if (!action.value && action.value !== false) {
      return (errors.value.ruleActions = `You must add a value for the ${indexToHuman[key]} action`)
    }
  })

  if (errors.value.ruleActions) {
    return
  }

  createRuleLoading.value = true

  axios
    .post(
      '/api/v1/rules',
      JSON.stringify({
        name: createRuleObject.value.name,
        conditions: createRuleObject.value.conditions,
        actions: createRuleObject.value.actions,
        operator: createRuleObject.value.operator,
        forwards: createRuleObject.value.forwards,
        replies: createRuleObject.value.replies,
        sends: createRuleObject.value.sends,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(({ data }) => {
      createRuleLoading.value = false
      resetCreateRuleObject()
      rows.value.push(data.data)
      createRuleModalOpen.value = false
      debounceToolips()
      reorderRules(false)
      successMessage('New rule created successfully')
    })
    .catch(error => {
      createRuleLoading.value = false
      if (error.response.status === 403) {
        errorMessage(error.response.data)
      } else if (error.response.data) {
        errorMessage(Object.entries(error.response.data.errors)[0][1][0])
      } else {
        errorMessage()
      }
    })
}

const editRule = () => {
  errors.value = {}

  if (!editRuleObject.value.name.length) {
    return (errors.value.ruleName = 'Please enter a rule name')
  }

  if (editRuleObject.value.name.length > 50) {
    return (errors.value.ruleName = 'Rule name cannot exceed 50 characters')
  }

  Object.entries(editRuleObject.value.conditions).forEach(([key, condition]) => {
    if (!condition.values.length) {
      return (errors.value.ruleConditions = `You must add some values for the ${indexToHuman[key]} condition, make sure to click "Insert"`)
    }
  })

  if (errors.value.ruleConditions) {
    return
  }

  Object.entries(editRuleObject.value.actions).forEach(([key, action]) => {
    if (!action.value && action.value !== false) {
      return (errors.value.ruleActions = `You must add a value for the ${indexToHuman[key]} action`)
    }
  })

  if (errors.value.ruleActions) {
    return
  }

  editRuleLoading.value = true

  axios
    .patch(
      `/api/v1/rules/${editRuleObject.value.id}`,
      JSON.stringify({
        name: editRuleObject.value.name,
        conditions: editRuleObject.value.conditions,
        actions: editRuleObject.value.actions,
        operator: editRuleObject.value.operator,
        forwards: editRuleObject.value.forwards,
        replies: editRuleObject.value.replies,
        sends: editRuleObject.value.sends,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      let rule = _.find(rows.value, ['id', editRuleObject.value.id])

      editRuleLoading.value = false
      rule.name = editRuleObject.value.name
      rule.conditions = editRuleObject.value.conditions
      rule.actions = editRuleObject.value.actions
      rule.operator = editRuleObject.value.operator
      rule.forwards = editRuleObject.value.forwards
      rule.replies = editRuleObject.value.replies
      rule.sends = editRuleObject.value.sends
      closeEditModal()
      successMessage('Rule successfully updated')
    })
    .catch(error => {
      editRuleLoading.value = false
      if (error.response.data) {
        errorMessage(Object.entries(error.response.data.errors)[0][1][0])
      } else {
        errorMessage()
      }
    })
}

const deleteRule = id => {
  deleteRuleLoading.value = true

  axios
    .delete(`/api/v1/rules/${id}`)
    .then(response => {
      rows.value = _.reject(rows.value, rule => rule.id === id)
      deleteRuleModalOpen.value = false
      deleteRuleLoading.value = false
    })
    .catch(error => {
      errorMessage()
      deleteRuleModalOpen.value = false
      deleteRuleLoading.value = false
    })
}

const activateRule = id => {
  axios
    .post(
      `/api/v1/active-rules`,
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

const deactivateRule = id => {
  axios
    .delete(`/api/v1/active-rules/${id}`)
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

const reorderRules = (displaySuccess = true) => {
  axios
    .post(
      `/api/v1/reorder-rules`,
      JSON.stringify({
        ids: rowsIds.value,
      }),
      {
        headers: { 'Content-Type': 'application/json' },
      },
    )
    .then(response => {
      if (displaySuccess) {
        successMessage('Rule order successfully updated')
      }
    })
    .catch(error => {
      if (error.response !== undefined) {
        errorMessage(error.response.data)
      } else {
        errorMessage()
      }
    })
}

const conditionMatchOptions = (object, key) => {
  if (_.includes(['sender', 'subject', 'alias'], object.conditions[key].type)) {
    return [
      'contains',
      'does not contain',
      'is exactly',
      'is not',
      'starts with',
      'does not start with',
      'ends with',
      'does not end with',
    ]
  }

  return []
}

const addCondition = object => {
  if (object.conditions.length >= 5) {
    return (errors.value.ruleConditions = `You cannot add more than 5 conditions per rule`)
  }

  object.conditions.push({
    type: 'select',
    match: 'contains',
    values: [],
  })
}

const deleteCondition = (object, key) => {
  object.conditions.splice(key, 1)
}

const addValueToCondition = (object, key) => {
  if (object.conditions[key].values.length >= 10) {
    return (errors.value.ruleConditions = `You cannot add more than 10 values per condition`)
  }

  if (object.conditions[key].currentConditionValue) {
    object.conditions[key].values.push(object.conditions[key].currentConditionValue)
  }

  // Reset current conditon value input
  object.conditions[key].currentConditionValue = ''
}

const addAction = object => {
  if (object.actions.length >= 5) {
    return (errors.value.ruleActions = `You cannot add more than 5 actions per rule`)
  }

  object.actions.push({
    type: 'select',
    value: '',
  })
}

const deleteAction = (object, key) => {
  object.actions.splice(key, 1)
}

const resetCreateRuleObject = () => {
  createRuleObject.value = {
    name: '',
    conditions: [
      {
        type: 'select',
        match: 'contains',
        values: [],
      },
    ],
    actions: [
      {
        type: 'select',
        value: '',
      },
    ],
    operator: 'AND',
    forwards: false,
    replies: false,
    sends: false,
  }
}

const ruleActionChange = action => {
  if (action.type === 'subject' || action.type === 'displayFrom' || action.type === 'select') {
    action.value = ''
  } else if (action.type === 'encryption') {
    action.value = false
  } else if (action.type === 'banner') {
    action.value = 'top'
  } else if (action.type === 'block') {
    action.value = true
  }
}

const openCreateModal = () => {
  errors.value = {}
  createRuleModalOpen.value = true
}

const openDeleteModal = id => {
  deleteRuleModalOpen.value = true
  ruleIdToDelete.value = id
}

const closeDeleteModal = () => {
  deleteRuleModalOpen.value = false
  _.delay(() => (ruleIdToDelete.value = ''), 300)
}

const openEditModal = rule => {
  errors.value = {}
  editRuleModalOpen.value = true
  editRuleObject.value = _.cloneDeep(rule)
}

const closeEditModal = () => {
  editRuleModalOpen.value = false
  _.delay(() => (editRuleObject.value = {}), 300)
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

<style>
.ghost {
  opacity: 0.5;
  background: #c8ebfb;
}
</style>
