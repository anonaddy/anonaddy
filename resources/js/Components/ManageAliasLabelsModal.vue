<template>
  <Modal :open="open" @close="$emit('close')">
    <template v-slot:title>Manage labels</template>
    <template v-slot:content>
      <p class="my-4 text-grey-700 dark:text-grey-200">
        Create labels to organise your aliases. Deleting a label removes it from all aliases.
      </p>

      <div v-if="labels.length" class="space-y-2 mb-6 max-h-48 overflow-y-auto">
        <div
          v-for="label in labels"
          :key="label.id"
          class="flex items-center justify-between gap-2 rounded-md border border-grey-200 px-3 py-2 dark:border-grey-600"
        >
          <div class="flex items-center gap-2 min-w-0">
            <span
              class="h-3 w-3 shrink-0 rounded-full"
              :style="{ backgroundColor: label.colour }"
            />
            <span class="truncate text-sm text-grey-900 dark:text-grey-100">{{ label.name }}</span>
          </div>
          <div class="flex h-5 shrink-0 items-center gap-2">
            <template v-if="labelIdPendingDelete === label.id">
              <span class="text-sm leading-5 text-red-700 dark:text-red-300">Delete?</span>
              <button
                type="button"
                class="text-sm leading-5 font-semibold text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 disabled:cursor-not-allowed"
                :disabled="deletingLabelId === label.id"
                @click="deleteLabel(label)"
              >
                Yes
                <loader v-if="deletingLabelId === label.id" />
              </button>
              <button
                type="button"
                class="text-sm leading-5 text-grey-600 hover:text-grey-800 dark:text-grey-300 dark:hover:text-grey-100 disabled:cursor-not-allowed"
                :disabled="deletingLabelId === label.id"
                @click="cancelDeleteRequest"
              >
                No
              </button>
            </template>
            <template v-else>
              <button
                type="button"
                class="text-sm leading-5 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                @click="startEdit(label)"
              >
                Edit
              </button>
              <button
                type="button"
                class="text-sm leading-5 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                @click="requestDelete(label.id)"
              >
                Delete
              </button>
            </template>
          </div>
        </div>
      </div>
      <p v-else class="mb-6 text-sm text-grey-500 dark:text-grey-400">No labels yet.</p>

      <div class="border-t border-grey-200 pt-4 dark:border-grey-600">
        <p class="text-sm font-medium text-grey-900 dark:text-grey-100 mb-3">
          {{ editingLabel ? 'Edit label' : 'New label' }}
        </p>
        <label class="block text-sm text-grey-700 dark:text-grey-200 mb-1" for="label_name"
          >Name</label
        >
        <input
          id="label_name"
          v-model="form.name"
          type="text"
          maxlength="50"
          class="block w-full rounded-md border-0 py-2 ring-1 ring-inset ring-grey-300 sm:text-sm dark:text-white dark:bg-white/5"
          placeholder="e.g. Shopping"
        />
        <p class="mt-3 text-sm text-grey-700 dark:text-grey-200 mb-2">Colour</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="colour in colourPalette"
            :key="colour"
            type="button"
            class="h-8 w-8 rounded-full ring-2 ring-offset-2 dark:ring-offset-grey-900"
            :class="
              form.colour === colour ? 'ring-indigo-600' : 'ring-transparent hover:ring-grey-300'
            "
            :style="{ backgroundColor: colour }"
            :aria-label="'Colour ' + colour"
            @click="form.colour = colour"
          />
        </div>
        <p v-if="formError" class="mt-2 text-sm text-red-500">{{ formError }}</p>
        <div class="mt-4 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            class="px-4 py-3 text-cyan-900 font-semibold bg-cyan-400 hover:bg-cyan-300 rounded disabled:cursor-not-allowed"
            :disabled="saving"
            @click="editingLabel ? updateLabel() : createLabel()"
          >
            {{ editingLabel ? 'Save' : 'Create' }}
            <loader v-if="saving" />
          </button>
          <button
            v-if="editingLabel"
            type="button"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded dark:bg-grey-600 dark:text-grey-100 dark:hover:bg-grey-700 dark:border-grey-700"
            @click="cancelEdit"
          >
            Cancel edit
          </button>
          <button
            type="button"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded dark:bg-grey-600 dark:text-grey-100 dark:hover:bg-grey-700 dark:border-grey-700"
            @click="$emit('close')"
          >
            Close
          </button>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import axios from 'axios'
import { notify } from '@kyvg/vue3-notification'
import { ref, watch } from 'vue'
import Modal from './Modal.vue'

const colourPalette = [
  '#06b6d4',
  '#22c55e',
  '#eab308',
  '#f97316',
  '#ef4444',
  '#8b5cf6',
  '#64748b',
  '#ec4899',
  '#14b8a6',
  '#3b82f6',
]

const props = defineProps({
  open: { type: Boolean, required: true },
  labels: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'updated'])

const labels = ref([...props.labels])
const editingLabel = ref(null)
const labelIdPendingDelete = ref(null)
const deletingLabelId = ref(null)
const saving = ref(false)
const formError = ref('')
const form = ref({ name: '', colour: colourPalette[0] })

watch(
  () => props.labels,
  value => {
    labels.value = [...value]
  },
  { deep: true },
)

watch(
  () => props.open,
  open => {
    if (open) {
      labels.value = [...props.labels]
      cancelEdit()
      cancelDeleteRequest()
    }
  },
)

function startEdit(label) {
  cancelDeleteRequest()
  editingLabel.value = label
  form.value = { name: label.name, colour: label.colour }
  formError.value = ''
}

function cancelEdit() {
  editingLabel.value = null
  form.value = { name: '', colour: colourPalette[0] }
  formError.value = ''
}

function requestDelete(labelId) {
  cancelEdit()
  labelIdPendingDelete.value = labelId
}

function cancelDeleteRequest() {
  labelIdPendingDelete.value = null
  deletingLabelId.value = null
}

function createLabel() {
  formError.value = ''
  if (!form.value.name.trim()) {
    formError.value = 'Name is required'

    return
  }
  saving.value = true
  axios
    .post('/api/v1/labels', {
      name: form.value.name.trim(),
      colour: form.value.colour,
    })
    .then(({ data }) => {
      labels.value.push(data.data)
      form.value = { name: '', colour: colourPalette[0] }
      emit('updated')
      notify({ type: 'success', text: 'Label created' })
    })
    .catch(handleError)
    .finally(() => {
      saving.value = false
    })
}

function updateLabel() {
  formError.value = ''
  if (!form.value.name.trim()) {
    formError.value = 'Name is required'

    return
  }
  saving.value = true
  axios
    .patch(`/api/v1/labels/${editingLabel.value.id}`, {
      name: form.value.name.trim(),
      colour: form.value.colour,
    })
    .then(({ data }) => {
      const index = labels.value.findIndex(l => l.id === editingLabel.value.id)
      if (index !== -1) {
        labels.value[index] = data.data
      }
      cancelEdit()
      emit('updated')
      notify({ type: 'success', text: 'Label updated' })
    })
    .catch(handleError)
    .finally(() => {
      saving.value = false
    })
}

function deleteLabel(label) {
  deletingLabelId.value = label.id
  axios
    .delete(`/api/v1/labels/${label.id}`)
    .then(() => {
      labels.value = labels.value.filter(l => l.id !== label.id)
      cancelDeleteRequest()
      emit('updated')
      notify({ type: 'success', text: 'Label deleted' })
    })
    .catch(handleError)
    .finally(() => {
      deletingLabelId.value = null
    })
}

function handleError(error) {
  if (error.response?.status === 422) {
    formError.value = error.response.data.message || 'Validation failed'
  } else if ([403, 429].includes(error.response?.status)) {
    formError.value = error.response.data
  } else {
    formError.value = 'Something went wrong'
  }
}
</script>
