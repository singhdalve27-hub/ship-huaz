<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Table from "@/Components/Table.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { useModal } from "@/Composables/useModal";

const props = defineProps({
    tableData: Array,
});

const allNodes = ref([]);
const loadingNodes = ref(true);

onMounted(async () => {
    try {
        const res = await fetch("/api/chatbot/nodes");
        const data = await res.json();
        allNodes.value = data.map((n) => ({
            value: String(n.id),
            label: n.node_key,
        }));
    } catch (e) {
        console.error("Failed to load nodes", e);
    } finally {
        loadingNodes.value = false;
    }
});

const parentNodeOptions = computed(() => [
    { value: "", label: "-- Select a node --", disabled: true },
    ...allNodes.value,
]);

const tableColumns = [
    { key: "id",       label: "ID" },
    { key: "node_key", label: "Node" },
    { key: "message",  label: "Message", slot: "message" },
    { key: "options",  label: "Options", slot: "options" },
    { key: "status",   label: "Status",  slot: "status" },
    { key: "actions",  label: "Actions", slot: "actions" },
];

const tableActions = {
    isDateFilterShow: false,
    isPerPageShow: true,
    isSearchShow: true,
};

const statusConfig = {
    active: {
        label: "Active",
        classes: "bg-blue-100 text-blue-700 border border-blue-200",
    },
    inactive: {
        label: "Inactive",
        classes: "bg-red-100 text-red-700 border border-red-200",
    },
};

const modal = useModal();

const editModalOpen = (item) => {
    form.id              = item.id;
    form.node_id         = String(item.id);
    form.option_node_ids = item.options.map((o) => String(o.next_node_id));
    form.status          = item.status;

    modal.title.value = "Edit Chat Node Options";
    modal.type.value  = "Edit";
    modal.icon.value  = "fa-solid fa-pen-to-square";
    modal.openModal();
};

const deleteModalOpen = (item) => {
    form.id = item.id;

    modal.title.value = "Delete Chat Node Options";
    modal.type.value  = "Delete";
    modal.icon.value  = "fa-solid fa-trash";
    modal.openModal();
};

const modalClose = () => {
    form.reset();
    modal.closeModal();
};

const form = useForm({
    id:              "",
    node_id:         "",
    option_node_ids: [],
    status:          "",
});

const statusFormat = ref([
    { value: "",         label: "-- Select status --", disabled: true },
    { value: "active",   label: "Active" },
    { value: "inactive", label: "Inactive" },
]);

function toggleOption(nodeId) {
    const idx = form.option_node_ids.indexOf(nodeId);
    if (idx === -1) {
        form.option_node_ids.push(nodeId);
    } else {
        form.option_node_ids.splice(idx, 1);
    }
}

function isSelected(nodeId) {
    return form.option_node_ids.includes(nodeId);
}

const submit = () => {
     if (modal.type.value === "Edit") {
        form.put(route("admin.chat-node-options.update", form.id), {
            onSuccess: () => modalClose(),
        });
    } else if (modal.type.value === "Delete") {
        form.delete(route("admin.chat-node-options.destroy", form.id), {
            onSuccess: () => modalClose(),
        });
    }
};
</script>

<template>
    <Head title="Chat Node Options" />

    <AdminLayout>
        <div>
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden px-4 py-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-2">
                    <h1 class="text-xl font-bold text-stone-800">Chat Node Options</h1>
                </div>

                <Table :data="tableData" :columns="tableColumns" :actions="tableActions">
                    <template #message="{ value }">
                        <span class="text-sm text-gray-600 line-clamp-2 text-wrap">{{ value }}</span>
                    </template>

                    <template #options="{ value }">
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="(opt, i) in value"
                                :key="i"
                                class="inline-flex items-center text-xs bg-gray-100 text-gray-700 border border-gray-200 px-2 py-0.5 rounded-full"
                            >
                                {{ opt.label }}
                            </span>
                            <span
                                v-if="!value || value.length === 0"
                                class="text-xs text-gray-400 italic"
                            >
                                No options
                            </span>
                        </div>
                    </template>

                    <template #status="{ value }">
                        <span
                            class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full capitalize"
                            :class="statusConfig[value]?.classes ?? 'bg-gray-100 text-gray-600'"
                        >
                            {{ statusConfig[value]?.label ?? value }}
                        </span>
                    </template>

                    <template #actions="{ row }">
                        <div class="flex items-center justify-center gap-1">
                            <button
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold px-3 py-2 rounded-md transition-colors"
                                @click="editModalOpen(row)"
                            >
                                <font-awesome-icon icon="fa-solid fa-pen-to-square" />
                                Edit
                            </button>
                            <button
                                class="bg-red-500 hover:bg-red-600 text-white font-semibold px-3 py-2 rounded-md transition-colors"
                                @click="deleteModalOpen(row)"
                            >
                                <font-awesome-icon icon="fa-solid fa-trash" />
                                Delete
                            </button>
                        </div>
                    </template>
                </Table>
            </div>

            <!-- Edit Modal -->
            <Modal
                :show="modal.type.value === 'Edit'"
                @close="!form.processing && modalClose()"
                :maxWidth="'lg'"
            >
                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h2 class="text-lg font-medium text-gray-900 mb-6">
                        <font-awesome-icon :icon="modal.icon.value" />
                        {{ modal.title.value }}
                    </h2>

                    <!-- Parent node picker -->
                    <div>
                        <InputLabel for="node_id" value="Parent Node" />
                        <p class="text-xs text-gray-400 mt-0.5 mb-1">
                            Which node are you configuring options for?
                        </p>
                        <SelectInput
                            id="node_id"
                            :options="parentNodeOptions"
                            class="mt-1 block w-full"
                            v-model="form.node_id"
                            :disabled="loadingNodes || modal.type.value === 'Edit'"
                            required
                        />
                        <InputError :message="form.errors.node_id" class="mt-2" />
                    </div>

                    <!-- Option nodes — checkbox list -->
                    <div class="mt-5">
                        <InputLabel value="Linked Option Nodes" />
                        <p class="text-xs text-gray-400 mt-0.5 mb-2">
                            Select which nodes will appear as buttons under this node.
                            The <strong>node_key</strong> will be used as the button label.
                        </p>

                        <!-- Loading state -->
                        <div
                            v-if="loadingNodes"
                            class="text-sm text-gray-400 py-6 text-center border border-gray-200 rounded-lg"
                        >
                            <font-awesome-icon icon="fa-solid fa-spinner" spin class="mr-1" />
                            Loading nodes…
                        </div>

                        <!-- Checkbox list -->
                        <div
                            v-else
                            class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-56 overflow-y-auto"
                        >
                            <label
                                v-for="node in allNodes"
                                :key="node.value"
                                class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50 transition-colors select-none"
                                :class="{ 'bg-blue-50 hover:bg-blue-50': isSelected(node.value) }"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isSelected(node.value)"
                                    @change="toggleOption(node.value)"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 flex-shrink-0"
                                />
                                <span
                                    class="text-sm"
                                    :class="isSelected(node.value) ? 'text-blue-700 font-medium' : 'text-gray-700'"
                                >
                                    {{ node.label }}
                                </span>
                                <span
                                    v-if="isSelected(node.value)"
                                    class="ml-auto text-xs text-blue-500"
                                >
                                    ✓
                                </span>
                            </label>

                            <div
                                v-if="allNodes.length === 0"
                                class="px-4 py-4 text-sm text-gray-400 text-center"
                            >
                                No nodes available.
                            </div>
                        </div>

                        <!-- Selected badges summary -->
                        <div class="mt-2 min-h-[24px]">
                            <div class="flex flex-wrap gap-1" v-if="form.option_node_ids.length > 0">
                                <span
                                    v-for="nodeId in form.option_node_ids"
                                    :key="nodeId"
                                    class="inline-flex items-center gap-1 text-xs bg-blue-100 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full"
                                >
                                    {{ allNodes.find((n) => n.value === nodeId)?.label ?? nodeId }}
                                    <button
                                        type="button"
                                        @click="toggleOption(nodeId)"
                                        class="hover:text-blue-900 leading-none font-bold"
                                    >
                                        ×
                                    </button>
                                </span>
                            </div>
                            <p v-else class="text-xs text-gray-400 italic">
                                No options selected yet.
                            </p>
                        </div>

                        <InputError :message="form.errors.option_node_ids" class="mt-2" />
                    </div>

                    <!-- Status (edit only) -->
                    <div class="mt-4" v-if="modal.type.value === 'Edit'">
                        <InputLabel for="status" value="Status" />
                        <SelectInput
                            id="status"
                            :options="statusFormat"
                            class="mt-1 block w-full"
                            v-model="form.status"
                            required
                        />
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>

                    <!-- Buttons -->
                    <div class="mt-6 flex justify-between">
                        <SecondaryButton
                            class="flex items-center"
                            :class="{ 'opacity-25': form.processing }"
                            @click="modalClose"
                            :disabled="form.processing"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            class="flex items-center gap-1"
                            :class="{ 'opacity-25': form.processing }"
                            @click="submit"
                            :disabled="form.processing"
                        >
                            <font-awesome-icon
                                v-if="form.processing"
                                icon="fa-solid fa-spinner"
                                spin
                                class="text-sm"
                            />
                            Save
                            <font-awesome-icon icon="fa-solid fa-paper-plane" />
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

            <!-- Delete Modal -->
            <Modal
                :show="modal.type.value === 'Delete'"
                @close="!form.processing && modalClose()"
                :maxWidth="'sm'"
            >
                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h2 class="text-lg font-medium text-gray-900">
                        <font-awesome-icon :icon="modal.icon.value" />
                        {{ modal.title.value }}
                    </h2>

                    <div class="mt-6">
                        <p class="text-gray-600">
                            Are you sure you want to delete all options for this node?
                            This action cannot be undone.
                        </p>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <SecondaryButton
                            :class="{ 'opacity-25': form.processing }"
                            @click="modalClose"
                            :disabled="form.processing"
                        >
                            Cancel
                        </SecondaryButton>
                        <DangerButton
                            class="flex items-center gap-1"
                            :class="{ 'opacity-25': form.processing }"
                            @click="submit"
                            :disabled="form.processing"
                        >
                            <font-awesome-icon
                                v-if="form.processing"
                                icon="fa-solid fa-spinner"
                                spin
                                class="text-sm"
                            />
                            Confirm Delete
                            <font-awesome-icon icon="fa-solid fa-trash" />
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>