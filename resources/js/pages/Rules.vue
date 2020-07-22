<template>
  <div>
    <div class="mb-6 flex flex-col md:flex-row justify-between md:items-center">
      <div class="flex items-center">
        <icon name="move" class="block w-6 h-6 mr-2 text-grey-200 fill-current" />
        You can drag and drop rules to order them.
      </div>
      <button
        @click="openCreateModal"
        class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none ml-auto"
      >
        Add New Rule
      </button>
    </div>

    <div v-if="initialRules.length" class="bg-white shadow">
      <draggable
        tag="ul"
        v-model="rows"
        v-bind="dragOptions"
        handle=".handle"
        @change="reorderRules"
      >
        <transition-group type="transition" name="flip-list">
          <li
            class="relative flex items-center py-3 px-5 border-b border-grey-100"
            v-for="row in rows"
            :key="row.name"
          >
            <div class="flex items-center w-3/5">
              <icon
                name="menu"
                class="handle block w-6 h-6 text-grey-200 fill-current cursor-pointer"
              />

              <span class="m-4">{{ row.name }} </span>
            </div>

            <div class="w-1/5 relative flex">
              <Toggle
                v-model="row.active"
                @on="activateRule(row.id)"
                @off="deactivateRule(row.id)"
              />
            </div>

            <div class="w-1/5 flex justify-end">
              <icon
                name="edit"
                class="block w-6 h-6 mr-3 text-grey-200 fill-current cursor-pointer"
                @click.native="openEditModal(row)"
              />
              <icon
                name="trash"
                class="block w-6 h-6 text-grey-200 fill-current cursor-pointer"
                @click.native="openDeleteModal(row.id)"
              />
            </div>
          </li>
        </transition-group>
      </draggable>
    </div>

    <div v-else class="bg-white rounded shadow overflow-x-auto">
      <div class="p-8 text-center text-lg text-grey-700">
        <h1 class="mb-6 text-2xl text-indigo-800 font-semibold">
          It doesn't look like you have any rules yet!
        </h1>
        <div class="mx-auto mb-6 w-24 border-b-2 border-grey-200"></div>
        <p class="mb-4">
          Click the button above to create a new rule.
        </p>
      </div>
    </div>

    <Modal :open="createRuleModalOpen" @close="createRuleModalOpen = false" :overflow="true">
      <div class="max-w-2xl w-full bg-white rounded-lg shadow-2xl p-6 my-12">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Create new rule
        </h2>
        <p class="mt-4 text-grey-700">
          Rules work on all emails, including replies and also send froms. New conditions and
          actions will be added over time.
        </p>

        <label for="rule_name" class="block text-grey-700 text-sm my-2">
          Name:
        </label>
        <p v-show="errors.ruleName" class="mb-3 text-red-500 text-sm">
          {{ errors.ruleName }}
        </p>
        <input
          v-model="createRuleObject.name"
          id="rule_name"
          type="text"
          class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-2"
          :class="errors.ruleName ? 'border-red-500' : ''"
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
                  id="rule_operator"
                  class="block appearance-none w-full text-grey-700 bg-white p-2 pr-6 rounded shadow focus:shadow-outline"
                  required
                >
                  <option value="AND">AND </option>
                  <option value="OR">OR </option>
                </select>
                <div
                  class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
                >
                  <svg
                    class="fill-current h-4 w-4"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                  >
                    <path
                      d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                    />
                  </svg>
                </div>
              </div>
            </div>

            <div class="p-2 w-full bg-grey-100">
              <div class="flex">
                <div class="flex items-center">
                  <span>If</span>
                  <span class="ml-2">
                    <div class="relative">
                      <select
                        v-model="createRuleObject.conditions[key].type"
                        id="rule_condition_types"
                        class="block appearance-none w-32 text-grey-700 bg-white p-2 pr-6 rounded shadow focus:shadow-outline"
                        required
                      >
                        <option
                          v-for="option in conditionTypeOptions"
                          :key="option.value"
                          :value="option.value"
                          >{{ option.label }}
                        </option>
                      </select>
                      <div
                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
                      >
                        <svg
                          class="fill-current h-4 w-4"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                        >
                          <path
                            d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                          />
                        </svg>
                      </div>
                    </div>
                  </span>

                  <span
                    v-if="conditionMatchOptions(createRuleObject, key).length"
                    class="ml-4 flex"
                  >
                    <div class="relative mr-4">
                      <select
                        v-model="createRuleObject.conditions[key].match"
                        id="rule_condition_matches"
                        class="block appearance-none w-40 text-grey-700 bg-white p-2 pr-6 rounded shadow focus:shadow-outline"
                        required
                      >
                        <option
                          v-for="option in conditionMatchOptions(createRuleObject, key)"
                          :key="option"
                          :value="option"
                          >{{ option }}
                        </option>
                      </select>
                      <div
                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
                      >
                        <svg
                          class="fill-current h-4 w-4"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                        >
                          <path
                            d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                          />
                        </svg>
                      </div>
                    </div>

                    <div class="flex">
                      <input
                        v-model="createRuleObject.conditions[key].currentConditionValue"
                        @keyup.enter="addValueToCondition(createRuleObject, key)"
                        type="text"
                        class="w-full appearance-none bg-white border border-transparent rounded-l text-grey-700 focus:outline-none p-2"
                        :class="errors.createRuleValues ? 'border-red-500' : ''"
                        placeholder="Enter value"
                        autofocus
                      />
                      <button class="p-2 bg-grey-200 rounded-r text-grey-600">
                        <icon
                          name="check"
                          class="block w-6 h-6 text-grey-600 fill-current cursor-pointer"
                          @click.native="addValueToCondition(createRuleObject, key)"
                        />
                      </button>
                    </div>
                  </span>
                </div>
                <div class="flex items-center">
                  <!-- delete button -->
                  <icon
                    v-if="createRuleObject.conditions.length > 1"
                    name="trash"
                    class="block ml-4 w-6 h-6 text-grey-200 fill-current cursor-pointer"
                    @click.native="deleteCondition(createRuleObject, key)"
                  />
                </div>
              </div>
              <div class="mt-2">
                <span
                  v-for="(value, index) in createRuleObject.conditions[key].values"
                  :key="index"
                >
                  <span class="bg-green-200 text-sm font-semibold rounded-sm pl-1">
                    {{ value }}
                    <icon
                      name="close"
                      class="inline-block w-4 h-4 text-grey-900 fill-current cursor-pointer"
                      @click.native="createRuleObject.conditions[key].values.splice(index, 1)"
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
            class="mt-4 p-2 text-grey-800 bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
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
              <div class="relative">
                AND
              </div>
            </div>

            <div class="p-2 w-full bg-grey-100">
              <div class="flex">
                <div class="flex items-center">
                  <span>Then</span>
                  <span class="ml-2">
                    <div class="relative">
                      <select
                        v-model="createRuleObject.actions[key].type"
                        @change="ruleActionChange(createRuleObject.actions[key])"
                        id="rule_action_types"
                        class="block appearance-none text-grey-700 bg-white p-2 pr-6 rounded shadow focus:shadow-outline"
                        required
                      >
                        <option
                          v-for="option in actionTypeOptions"
                          :key="option.value"
                          :value="option.value"
                          >{{ option.label }}
                        </option>
                      </select>
                      <div
                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
                      >
                        <svg
                          class="fill-current h-4 w-4"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                        >
                          <path
                            d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                          />
                        </svg>
                      </div>
                    </div>
                  </span>

                  <span
                    v-if="
                      createRuleObject.actions[key].type === 'subject' ||
                        createRuleObject.actions[key].type === 'displayFrom'
                    "
                    class="ml-4 flex"
                  >
                    <div class="flex">
                      <input
                        v-model="createRuleObject.actions[key].value"
                        type="text"
                        class="w-full appearance-none bg-white border border-transparent rounded text-grey-700 focus:outline-none p-2"
                        :class="errors.createRuleActionValue ? 'border-red-500' : ''"
                        placeholder="Enter value"
                        autofocus
                      />
                    </div>
                  </span>

                  <span
                    v-else-if="createRuleObject.actions[key].type === 'banner'"
                    class="ml-4 flex"
                  >
                    <div class="relative mr-4">
                      <select
                        v-model="createRuleObject.actions[key].value"
                        id="create_rule_action_banner"
                        class="block appearance-none w-40 text-grey-700 bg-white p-2 pr-6 rounded shadow focus:shadow-outline"
                        required
                      >
                        <option selected value="top">Top </option>
                        <option selected value="bottom">Bottom </option>
                        <option selected value="off">Off </option>
                      </select>
                      <div
                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
                      >
                        <svg
                          class="fill-current h-4 w-4"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                        >
                          <path
                            d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                          />
                        </svg>
                      </div>
                    </div>
                  </span>
                </div>
                <div class="flex items-center">
                  <!-- delete button -->
                  <icon
                    v-if="createRuleObject.actions.length > 1"
                    name="trash"
                    class="block ml-4 w-6 h-6 text-grey-200 fill-current cursor-pointer"
                    @click.native="deleteAction(createRuleObject, key)"
                  />
                </div>
              </div>
            </div>
          </div>
          <!-- add action button -->
          <button
            @click="addAction(createRuleObject)"
            class="mt-4 p-2 text-grey-800 bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Add action
          </button>

          <p v-show="errors.ruleActions" class="mt-2 text-red-500 text-sm">
            {{ errors.ruleActions }}
          </p>
        </fieldset>

        <div class="mt-6">
          <button
            @click="createNewRule"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            :class="createRuleLoading ? 'cursor-not-allowed' : ''"
            :disabled="createRuleLoading"
          >
            Create Rule
            <loader v-if="createRuleLoading" />
          </button>
          <button
            @click="createRuleModalOpen = false"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <Modal :open="editRuleModalOpen" @close="closeEditModal" :overflow="true">
      <div class="max-w-2xl w-full bg-white rounded-lg shadow-2xl p-6 my-12">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Edit rule
        </h2>
        <p class="mt-4 text-grey-700">
          Rules work on all emails, including replies and also send froms. New conditions and
          actions will be added over time.
        </p>

        <label for="edit_rule_name" class="block text-grey-700 text-sm my-2">
          Name:
        </label>
        <p v-show="errors.ruleName" class="mb-3 text-red-500 text-sm">
          {{ errors.ruleName }}
        </p>
        <input
          v-model="editRuleObject.name"
          id="edit_rule_name"
          type="text"
          class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-2"
          :class="errors.ruleName ? 'border-red-500' : ''"
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
                  id="edit_rule_operator"
                  class="block appearance-none w-full text-grey-700 bg-white p-2 pr-6 rounded shadow focus:shadow-outline"
                  required
                >
                  <option value="AND">AND </option>
                  <option value="OR">OR </option>
                </select>
                <div
                  class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
                >
                  <svg
                    class="fill-current h-4 w-4"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                  >
                    <path
                      d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                    />
                  </svg>
                </div>
              </div>
            </div>

            <div class="p-2 w-full bg-grey-100">
              <div class="flex">
                <div class="flex items-center">
                  <span>If</span>
                  <span class="ml-2">
                    <div class="relative">
                      <select
                        v-model="editRuleObject.conditions[key].type"
                        id="edit_rule_condition_types"
                        class="block appearance-none w-32 text-grey-700 bg-white p-2 pr-6 rounded shadow focus:shadow-outline"
                        required
                      >
                        <option
                          v-for="option in conditionTypeOptions"
                          :key="option.value"
                          :value="option.value"
                          >{{ option.label }}
                        </option>
                      </select>
                      <div
                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
                      >
                        <svg
                          class="fill-current h-4 w-4"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                        >
                          <path
                            d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                          />
                        </svg>
                      </div>
                    </div>
                  </span>

                  <span v-if="conditionMatchOptions(editRuleObject, key).length" class="ml-4 flex">
                    <div class="relative mr-4">
                      <select
                        v-model="editRuleObject.conditions[key].match"
                        id="edit_rule_condition_matches"
                        class="block appearance-none w-40 text-grey-700 bg-white p-2 pr-6 rounded shadow focus:shadow-outline"
                        required
                      >
                        <option
                          v-for="option in conditionMatchOptions(editRuleObject, key)"
                          :key="option"
                          :value="option"
                          >{{ option }}
                        </option>
                      </select>
                      <div
                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
                      >
                        <svg
                          class="fill-current h-4 w-4"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                        >
                          <path
                            d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                          />
                        </svg>
                      </div>
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
                      <button class="p-2 bg-grey-200 rounded-r text-grey-600">
                        <icon
                          name="check"
                          class="block w-6 h-6 text-grey-600 fill-current cursor-pointer"
                          @click.native="addValueToCondition(editRuleObject, key)"
                        />
                      </button>
                    </div>
                  </span>
                </div>
                <div class="flex items-center">
                  <!-- delete button -->
                  <icon
                    v-if="editRuleObject.conditions.length > 1"
                    name="trash"
                    class="block ml-4 w-6 h-6 text-grey-200 fill-current cursor-pointer"
                    @click.native="deleteCondition(editRuleObject, key)"
                  />
                </div>
              </div>
              <div class="mt-2">
                <span v-for="(value, index) in editRuleObject.conditions[key].values" :key="index">
                  <span class="bg-green-200 text-sm font-semibold rounded-sm pl-1">
                    {{ value }}
                    <icon
                      name="close"
                      class="inline-block w-4 h-4 text-grey-900 fill-current cursor-pointer"
                      @click.native="editRuleObject.conditions[key].values.splice(index, 1)"
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
            class="mt-4 p-2 text-grey-800 bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
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
              <div class="relative">
                AND
              </div>
            </div>

            <div class="p-2 w-full bg-grey-100">
              <div class="flex">
                <div class="flex items-center">
                  <span>Then</span>
                  <span class="ml-2">
                    <div class="relative">
                      <select
                        v-model="editRuleObject.actions[key].type"
                        @change="ruleActionChange(editRuleObject.actions[key])"
                        id="rule_action_types"
                        class="block appearance-none text-grey-700 bg-white p-2 pr-6 rounded shadow focus:shadow-outline"
                        required
                      >
                        <option
                          v-for="option in actionTypeOptions"
                          :key="option.value"
                          :value="option.value"
                          >{{ option.label }}
                        </option>
                      </select>
                      <div
                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
                      >
                        <svg
                          class="fill-current h-4 w-4"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                        >
                          <path
                            d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                          />
                        </svg>
                      </div>
                    </div>
                  </span>

                  <span
                    v-if="
                      editRuleObject.actions[key].type === 'subject' ||
                        editRuleObject.actions[key].type === 'displayFrom'
                    "
                    class="ml-4 flex"
                  >
                    <div class="flex">
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

                  <span v-else-if="editRuleObject.actions[key].type === 'banner'" class="ml-4 flex">
                    <div class="relative mr-4">
                      <select
                        v-model="editRuleObject.actions[key].value"
                        id="edit_rule_action_banner"
                        class="block appearance-none w-40 text-grey-700 bg-white p-2 pr-6 rounded shadow focus:shadow-outline"
                        required
                      >
                        <option value="top">Top </option>
                        <option value="bottom">Bottom </option>
                        <option value="off">Off </option>
                      </select>
                      <div
                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
                      >
                        <svg
                          class="fill-current h-4 w-4"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                        >
                          <path
                            d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                          />
                        </svg>
                      </div>
                    </div>
                  </span>
                </div>
                <div class="flex items-center">
                  <!-- delete button -->
                  <icon
                    v-if="editRuleObject.actions.length > 1"
                    name="trash"
                    class="block ml-4 w-6 h-6 text-grey-200 fill-current cursor-pointer"
                    @click.native="deleteAction(editRuleObject, key)"
                  />
                </div>
              </div>
            </div>
          </div>
          <!-- add action button -->
          <button
            @click="addAction(editRuleObject)"
            class="mt-4 p-2 text-grey-800 bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Add action
          </button>

          <p v-show="errors.ruleActions" class="mt-2 text-red-500 text-sm">
            {{ errors.ruleActions }}
          </p>
        </fieldset>

        <div class="mt-6">
          <button
            @click="editRule"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            :class="editRuleLoading ? 'cursor-not-allowed' : ''"
            :disabled="editRuleLoading"
          >
            Save
            <loader v-if="editRuleLoading" />
          </button>
          <button
            @click="closeEditModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <Modal :open="deleteRuleModalOpen" @close="closeDeleteModal">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl px-6 py-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Delete rule
        </h2>
        <p class="mt-4 text-grey-700">
          Are you sure you want to delete this rule?
        </p>
        <div class="mt-6">
          <button
            type="button"
            @click="deleteRule(ruleIdToDelete)"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus:outline-none"
            :class="deleteRuleLoading ? 'cursor-not-allowed' : ''"
            :disabled="deleteRuleLoading"
          >
            Delete rule
            <loader v-if="deleteRuleLoading" />
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
import draggable from 'vuedraggable'

export default {
  props: {
    initialRules: {
      type: Array,
      required: true,
    },
  },
  components: {
    Modal,
    Toggle,
    draggable,
  },
  mounted() {
    this.addTooltips()
  },
  data() {
    return {
      editRuleObject: {},
      ruleIdToDelete: '',
      deleteRuleLoading: false,
      deleteRuleModalOpen: false,
      createRuleModalOpen: false,
      editRuleModalOpen: false,
      createRuleLoading: false,
      editRuleLoading: false,
      createRuleObject: {
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
      },
      rows: this.initialRules,
      conditionTypeOptions: [
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
      ],
      actionTypeOptions: [
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
      ],
      errors: {},
    }
  },
  watch: {
    editRuleObject: _.debounce(function() {
      this.addTooltips()
    }, 50),
  },
  computed: {
    activeRules() {
      return _.filter(this.rows, rule => rule.active)
    },
    dragOptions() {
      return {
        animation: 0,
        group: 'description',
        disabled: false,
        ghostClass: 'ghost',
      }
    },
    rowsIds() {
      return _.map(this.rows, row => row.id)
    },
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
    openCreateModal() {
      this.errors = {}
      this.createRuleModalOpen = true
    },
    openDeleteModal(id) {
      this.deleteRuleModalOpen = true
      this.ruleIdToDelete = id
    },
    closeDeleteModal() {
      this.deleteRuleModalOpen = false
      this.ruleIdToDelete = ''
    },
    openEditModal(rule) {
      this.errors = {}
      this.editRuleModalOpen = true
      this.editRuleObject = _.cloneDeep(rule)
    },
    closeEditModal() {
      this.editRuleModalOpen = false
      this.editRuleObject = {}
    },
    deleteRule(id) {
      this.deleteRuleLoading = true

      axios
        .delete(`/api/v1/rules/${id}`)
        .then(response => {
          this.rows = _.reject(this.rows, rule => rule.id === id)
          this.deleteRuleModalOpen = false
          this.deleteRuleLoading = false
        })
        .catch(error => {
          this.error()
          this.deleteRuleModalOpen = false
          this.deleteRuleLoading = false
        })
    },
    createNewRule() {
      this.errors = {}

      if (!this.createRuleObject.name.length) {
        return (this.errors.ruleName = 'Please enter a rule name')
      }

      if (this.createRuleObject.name.length > 50) {
        return (this.errors.ruleName = 'Rule name cannot exceed 50 characters')
      }

      if (!this.createRuleObject.conditions[0].values.length) {
        return (this.errors.ruleConditions = 'You must add some values for the condition')
      }

      if (
        !this.createRuleObject.actions[0].value &&
        this.createRuleObject.actions[0].value !== false
      ) {
        return (this.errors.ruleActions = 'You must add a value for the action')
      }

      this.createRuleLoading = true

      axios
        .post(
          '/api/v1/rules',
          JSON.stringify({
            name: this.createRuleObject.name,
            conditions: this.createRuleObject.conditions,
            actions: this.createRuleObject.actions,
            operator: this.createRuleObject.operator,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(({ data }) => {
          this.createRuleLoading = false
          this.resetCreateRuleObject()
          this.rows.push(data.data)
          this.createRuleModalOpen = false
          this.success('New rule created successfully')
        })
        .catch(error => {
          this.createRuleLoading = false
          this.error()
        })
    },
    editRule() {
      this.errors = {}

      if (!this.editRuleObject.name.length) {
        return (this.errors.ruleName = 'Please enter a rule name')
      }

      if (this.editRuleObject.name.length > 50) {
        return (this.errors.ruleName = 'Rule name cannot exceed 50 characters')
      }

      if (!this.editRuleObject.conditions[0].values.length) {
        return (this.errors.ruleConditions = 'You must add some values for the condition')
      }

      if (!this.editRuleObject.actions[0].value && this.editRuleObject.actions[0].value !== false) {
        return (this.errors.ruleActions = 'You must add a value for the action')
      }

      this.editRuleLoading = true

      axios
        .patch(
          `/api/v1/rules/${this.editRuleObject.id}`,
          JSON.stringify({
            name: this.editRuleObject.name,
            conditions: this.editRuleObject.conditions,
            actions: this.editRuleObject.actions,
            operator: this.editRuleObject.operator,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          let rule = _.find(this.rows, ['id', this.editRuleObject.id])

          this.editRuleLoading = false
          rule.name = this.editRuleObject.name
          rule.conditions = this.editRuleObject.conditions
          rule.actions = this.editRuleObject.actions
          rule.operator = this.editRuleObject.operator
          this.editRuleObject = {}
          this.editRuleModalOpen = false
          this.success('Rule successfully updated')
        })
        .catch(error => {
          this.editRuleLoading = false
          if (error.response.data) {
            this.error(Object.entries(error.response.data.errors)[0][1][0])
          } else {
            this.error()
          }
        })
    },
    activateRule(id) {
      axios
        .post(
          `/api/v1/active-rules`,
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
          if (error.response !== undefined) {
            this.error(error.response.data)
          } else {
            this.error()
          }
        })
    },
    deactivateRule(id) {
      axios
        .delete(`/api/v1/active-rules/${id}`)
        .then(response => {
          //
        })
        .catch(error => {
          if (error.response !== undefined) {
            this.error(error.response.data)
          } else {
            this.error()
          }
        })
    },
    reorderRules() {
      axios
        .post(
          `/api/v1/reorder-rules`,
          JSON.stringify({
            ids: this.rowsIds,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          this.success('Rule order successfully updated')
        })
        .catch(error => {
          if (error.response !== undefined) {
            this.error(error.response.data)
          } else {
            this.error()
          }
        })
    },
    conditionMatchOptions(object, key) {
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
    },
    addCondition(object) {
      object.conditions.push({
        type: 'select',
        match: 'contains',
        values: [],
      })
    },
    deleteCondition(object, key) {
      object.conditions.splice(key, 1)
    },
    addValueToCondition(object, key) {
      if (object.conditions[key].currentConditionValue) {
        object.conditions[key].values.push(object.conditions[key].currentConditionValue)
      }

      // Reset current conditon value input
      object.conditions[key].currentConditionValue = ''
    },
    addAction(object) {
      object.actions.push({
        type: 'select',
        value: '',
      })
    },
    deleteAction(object, key) {
      object.actions.splice(key, 1)
    },
    resetCreateRuleObject() {
      this.createRuleObject = {
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
      }
    },
    ruleActionChange(action) {
      if (action.type === 'subject' || action.type === 'displayFrom' || action.type === 'select') {
        action.value = ''
      } else if (action.type === 'encryption') {
        action.value = false
      } else if (action.type === 'banner') {
        action.value = 'top'
      } else if (action.type === 'block') {
        action.value = true
      }
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

<style>
.flip-list-move {
  transition: transform 0.5s;
}

.ghost {
  opacity: 0.5;
  background: #c8ebfb;
}
</style>
