<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Table from "@/Components/Table.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import SelectInput from "@/Components/SelectInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { useModal } from "@/Composables/useModal";

const props = defineProps({
    posts: Array,
});

const tableColumns = [
    { key: "image", label: "Cover", slot: "image" },
    { key: "details", label: "Article Details", slot: "details" },
    { key: "link", label: "Destination Link", slot: "link" },
    { key: "category", label: "Category", slot: "category" },
    { key: "date", label: "Publish Date", slot: "date" },
    { key: "status", label: "Visibility", slot: "status" },
    { key: "actions", label: "Actions", slot: "actions" },
];

const formatPostsData = (items) => {
    return (items || []).map((item) => ({
        id: item.id,
        image: item.image,
        title: item.title,
        category: item.category || "General",
        excerpt: item.excerpt,
        link: item.link || "",
        post_date: item.post_date,
        date: item.post_date
            ? new Date(item.post_date).toLocaleDateString("en-PH", {
                  month: "short",
                  day: "numeric",
                  year: "numeric",
              })
            : "—",
        status: item.status,
    }));
};

const tableData = ref(formatPostsData(props.posts));

watch(
    () => props.posts,
    (newPosts) => {
        tableData.value = formatPostsData(newPosts);
    },
);

const tableActions = {
    isDateFilterShow: false,
    isPerPageShow: true,
    isSearchShow: true,
};

const statusConfig = {
    active: {
        label: "Live & Published",
        classes: "bg-emerald-50 text-emerald-700 border border-emerald-200/80 ring-1 ring-emerald-500/20",
    },
    inactive: {
        label: "Draft / Archived",
        classes: "bg-rose-50 text-rose-700 border border-rose-200/80 ring-1 ring-rose-500/20",
    },
};

const modal = useModal();
const imagePreview = ref(null);

const handleImageChange = (e) => {
    const file = e.target.files[0];
    form.image = file;
    if (file) {
        imagePreview.value = URL.createObjectURL(file);
    } else {
        imagePreview.value = null;
    }
};

const addModalOpen = () => {
    form.reset();
    form.clearErrors();
    imagePreview.value = null;
    modal.title.value = "Create News Article";
    modal.type.value = "Add";
    modal.icon.value = "fa-solid fa-plus-circle";
    modal.openModal();
};

const editModalOpen = (item) => {
    form.clearErrors();
    form.id = item.id;
    form.title = item.title;
    form.category = item.category === "General" ? "" : item.category;
    form.excerpt = item.excerpt;
    form.link = item.link || "";
    form.post_date = item.post_date;
    form.status = item.status;
    form.image = null;
    imagePreview.value = item.image;

    modal.title.value = "Edit News Article";
    modal.type.value = "Edit";
    modal.icon.value = "fa-solid fa-pen-to-square";
    modal.openModal();
};

const deleteModalOpen = (item) => {
    form.id = item.id;
    modal.title.value = "Delete Article";
    modal.type.value = "Delete";
    modal.icon.value = "fa-solid fa-trash";
    modal.openModal();
};

const modalClose = () => {
    form.reset();
    form.clearErrors();
    imagePreview.value = null;
    modal.closeModal();
};

const statusFormat = ref([
    { value: "", label: "-- Select status --", disabled: true },
    { value: "active", label: "Active" },
    { value: "inactive", label: "Inactive" },
]);

const form = useForm({
    id: "",
    title: "",
    category: "",
    excerpt: "",
    link: "",
    post_date: "",
    status: "active",
    image: null,
});

const submit = () => {
    if (form.link && form.link.trim()) {
        const trimmed = form.link.trim();
        if (!/^https?:\/\//i.test(trimmed)) {
            form.link = "https://" + trimmed;
        } else {
            form.link = trimmed;
        }
    } else {
        form.link = "";
    }

    if (modal.type.value === "Add") {
        form.post(route("admin.posts.store"), {
            onSuccess: () => modalClose(),
            forceFormData: true,
        });
    } else if (modal.type.value === "Edit") {
        form.transform((data) => ({
            ...data,
            _method: "put",
        })).post(route("admin.posts.update", form.id), {
            onSuccess: () => modalClose(),
            forceFormData: true,
        });
    } else if (modal.type.value === "Delete") {
        form.delete(route("admin.posts.destroy", form.id), {
            onSuccess: () => modalClose(),
        });
    }
};
</script>

<template>
    <Head title="Blog & News CMS" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-slate-900 via-sky-950 to-slate-900 rounded-2xl p-6 lg:p-8 text-white shadow-xl shadow-slate-900/10 border border-slate-800 relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-orange-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute right-10 top-6 opacity-10 text-white pointer-events-none">
                    <font-awesome-icon icon="fa-solid fa-newspaper" class="text-9xl" />
                </div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/20 border border-orange-400/30 text-orange-300 text-xs font-bold tracking-wider uppercase mb-2.5">
                            <font-awesome-icon icon="fa-solid fa-pen-nib" />
                            Editorial Content CMS
                        </div>
                        <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-white">
                            Blog & News Articles
                        </h1>
                        <p class="text-slate-300 text-sm mt-1 max-w-xl">
                            Publish vessel stories, tourist guides, event highlights, and dining updates featured on the homepage.
                        </p>
                    </div>

                    <button
                        class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-sm px-6 py-3 rounded-xl shadow-lg shadow-orange-500/25 transition-all duration-200 cursor-pointer active:scale-95 shrink-0"
                        @click="addModalOpen"
                    >
                        <font-awesome-icon icon="fa-solid fa-plus-circle" />
                        <span>Publish New Article</span>
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 lg:p-6 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Published Articles & Highlights
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Total articles in database: {{ tableData.length }}
                        </p>
                    </div>
                </div>

                <div class="p-4 lg:p-6">
                    <Table :data="tableData" :columns="tableColumns" :actions="tableActions">
                        <!-- Image Thumbnail -->
                        <template #image="{ value }">
                            <div class="w-20 h-14 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shadow-sm shrink-0">
                                <img
                                    v-if="value"
                                    :src="value"
                                    alt="thumbnail"
                                    class="w-full h-full object-cover transition-transform duration-300 hover:scale-110"
                                />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 text-xs">
                                    <font-awesome-icon icon="fa-solid fa-image" />
                                    <span class="text-[9px] mt-0.5 font-bold">No Image</span>
                                </div>
                            </div>
                        </template>

                        <!-- Details -->
                        <template #details="{ row }">
                            <div class="space-y-1 max-w-sm">
                                <p class="text-sm font-black text-slate-900 hover:text-orange-600 transition-colors">
                                    {{ row.title }}
                                </p>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                    {{ row.excerpt }}
                                </p>
                            </div>
                        </template>

                        <!-- Destination Link -->
                        <template #link="{ row }">
                            <a
                                v-if="row.link"
                                :href="row.link"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-700 hover:text-orange-600 bg-sky-50 hover:bg-sky-100 border border-sky-200 px-2.5 py-1 rounded-lg transition-colors max-w-[170px] group"
                                title="Open destination link in new tab"
                            >
                                <font-awesome-icon icon="fa-solid fa-arrow-up-right-from-square" class="text-[10px] text-orange-500 group-hover:translate-x-0.5 transition-transform shrink-0" />
                                <span class="truncate">{{ row.link.replace(/^https?:\/\/(www\.)?/, '') }}</span>
                            </a>
                            <a
                                v-else
                                href="https://www.facebook.com/share/1KawkEhDiH/"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 text-[11px] text-slate-500 hover:text-orange-600 bg-slate-50 hover:bg-orange-50 border border-slate-200 px-2 py-1 rounded-lg transition-colors"
                                title="Default destination: Official Facebook Page"
                            >
                                <font-awesome-icon icon="fa-brands fa-facebook" class="text-[11px] text-sky-600" />
                                <span>Default: FB Page</span>
                            </a>
                        </template>

                        <!-- Category -->
                        <template #category="{ value }">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-sky-50 border border-sky-100 text-sky-800 text-xs font-bold">
                                🔖 {{ value }}
                            </span>
                        </template>

                        <!-- Date -->
                        <template #date="{ value }">
                            <span class="text-xs font-semibold text-slate-600">
                                {{ value }}
                            </span>
                        </template>

                        <!-- Status -->
                        <template #status="{ value }">
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full whitespace-nowrap"
                                :class="statusConfig[value]?.classes ?? 'bg-slate-100 text-slate-600'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="value === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                {{ statusConfig[value]?.label ?? value }}
                            </span>
                        </template>
                        
                        <!-- Actions -->
                        <template #actions="{ row }">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold px-3 py-1.5 rounded-lg border border-slate-200 transition-colors text-xs active:scale-95 cursor-pointer"
                                    @click="editModalOpen(row)"
                                    title="Edit Article"
                                >
                                    <font-awesome-icon icon="fa-solid fa-pen-to-square" class="text-sky-600" />
                                    <span>Edit</span>
                                </button>
                                <button
                                    class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold px-3 py-1.5 rounded-lg border border-rose-200 transition-colors text-xs active:scale-95 cursor-pointer"
                                    @click="deleteModalOpen(row)"
                                    title="Delete Article"
                                >
                                    <font-awesome-icon icon="fa-solid fa-trash" />
                                    <span>Delete</span>
                                </button>
                            </div>
                        </template>
                    </Table>
                </div>
            </div>

            <!-- Add & Edit Modal -->
            <Modal
                :show="modal.type.value === 'Add' || modal.type.value === 'Edit'"
                @close="!form.processing && modalClose()"
                :maxWidth="'lg'"
            >
                <div class="bg-gradient-to-r from-slate-900 to-sky-950 px-6 py-5 text-white flex items-center justify-between rounded-t-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-orange-500/20 text-orange-400 flex items-center justify-center text-lg border border-orange-500/30">
                            <font-awesome-icon :icon="modal.icon.value" />
                        </div>
                        <div>
                            <h2 class="font-bold text-base text-white">
                                {{ modal.title.value }}
                            </h2>
                            <p class="text-xs text-slate-300">
                                Curate stories, announcements, and featured photography
                            </p>
                        </div>
                    </div>
                    <button
                        @click="modalClose"
                        class="text-slate-400 hover:text-white transition-colors p-1"
                        :disabled="form.processing"
                    >
                        ✕
                    </button>
                </div>

                <div class="p-6 max-h-[75vh] overflow-y-auto space-y-4 bg-white">
                    <div>
                        <InputLabel for="title" value="Article Title" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                        <TextInput
                            id="title"
                            type="text"
                            class="mt-1.5 block w-full border-slate-300 focus:border-orange-500 focus:ring-orange-500 rounded-xl shadow-sm text-sm"
                            v-model="form.title"
                            required
                            placeholder="e.g. The Perfect Coastal Venue for Your Dream Event"
                        />
                        <InputError :message="form.errors.title" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="category" value="Category" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                            <TextInput
                                id="category"
                                type="text"
                                class="mt-1.5 block w-full border-slate-300 focus:border-orange-500 focus:ring-orange-500 rounded-xl shadow-sm text-sm"
                                v-model="form.category"
                                placeholder="e.g. Events, Destination, Dining"
                            />
                            <InputError :message="form.errors.category" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="post_date" value="Display Date" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                            <TextInput
                                id="post_date"
                                type="date"
                                class="mt-1.5 block w-full border-slate-300 focus:border-orange-500 focus:ring-orange-500 rounded-xl shadow-sm text-sm"
                                v-model="form.post_date"
                            />
                            <InputError :message="form.errors.post_date" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="excerpt" value="Short Excerpt (Landing Page Summary)" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                        <TextAreaInput
                            id="excerpt"
                            class="mt-1.5 block w-full border-slate-300 focus:border-orange-500 focus:ring-orange-500 rounded-xl shadow-sm text-sm resize-none"
                            v-model="form.excerpt"
                            rows="3"
                            placeholder="A short engaging teaser that will appear on the homepage blog section..."
                        />
                        <InputError :message="form.errors.excerpt" class="mt-1" />
                    </div>

                    <!-- Destination Link / URL -->
                    <div>
                        <InputLabel for="link" value="Article Destination Link / External URL" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                        <TextInput
                            id="link"
                            type="url"
                            class="mt-1.5 block w-full border-slate-300 focus:border-orange-500 focus:ring-orange-500 rounded-xl shadow-sm text-sm"
                            v-model="form.link"
                            placeholder="e.g. https://facebook.com/butalshiphauz/posts/... or https://example.com/article"
                        />
                        <p class="text-[11px] text-slate-500 mt-1">
                            Direct URL for this article (e.g. Facebook post, announcement, or blog page). When visitors click "Read Article" on the homepage, they will directly navigate to this link in a new tab.
                        </p>
                        <InputError :message="form.errors.link" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="image" value="Featured Cover Photograph" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                        <div class="mt-2 flex items-center gap-4">
                            <div v-if="imagePreview" class="w-20 h-20 rounded-xl overflow-hidden border border-slate-300 shrink-0 bg-slate-100 shadow-sm">
                                <img :src="imagePreview" alt="Preview" class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1">
                                <input 
                                    type="file" 
                                    id="image"
                                    accept="image/*"
                                    @change="handleImageChange"
                                    class="block w-full text-xs text-slate-600
                                           file:mr-4 file:py-2 file:px-4
                                           file:rounded-lg file:border-0
                                           file:text-xs file:font-bold
                                           file:bg-orange-50 file:text-orange-600
                                           hover:file:bg-orange-100 transition-colors cursor-pointer"
                                />
                                <p class="text-[11px] text-slate-500 mt-1.5">
                                    Optimal resolution: 1200x800px. JPG, PNG, or WebP.
                                </p>
                            </div>
                        </div>
                        <InputError :message="form.errors.image" class="mt-1" />
                    </div>

                    <div v-if="modal.type.value === 'Edit'">
                        <InputLabel for="status" value="Visibility Status" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                        <SelectInput
                            id="status"
                            :options="statusFormat"
                            class="mt-1.5 block w-full border-slate-300 focus:border-orange-500 focus:ring-orange-500 rounded-xl shadow-sm text-sm"
                            v-model="form.status"
                            required
                        />
                        <InputError :message="form.errors.status" class="mt-1" />
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3 rounded-b-xl">
                    <button
                        type="button"
                        class="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-sm rounded-xl transition-colors shadow-sm cursor-pointer"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        @click="modalClose"
                        :disabled="form.processing"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-sm rounded-xl shadow-md shadow-orange-500/25 transition-all flex items-center gap-2 cursor-pointer active:scale-95"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        @click="submit"
                        :disabled="form.processing"
                    >
                        <font-awesome-icon v-if="form.processing" icon="fa-solid fa-spinner" spin />
                        <span v-else>Publish Post</span>
                        <font-awesome-icon v-if="!form.processing" icon="fa-solid fa-arrow-right" />
                    </button>
                </div>
            </Modal>

            <!-- Delete Modal -->
            <Modal
                :show="modal.type.value === 'Delete'"
                @close="!form.processing && modalClose()"
                :maxWidth="'sm'"
            >
                <div class="p-6 bg-white rounded-xl">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl mb-4 border border-rose-200">
                        <font-awesome-icon icon="fa-solid fa-triangle-exclamation" />
                    </div>
                    <h2 class="font-bold text-lg text-slate-900">
                        Delete Blog Post?
                    </h2>
                    <p class="text-slate-600 text-sm mt-2 leading-relaxed">
                        Are you sure you want to remove this article? It will no longer appear on the public landing page.
                    </p>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition-colors cursor-pointer"
                            :disabled="form.processing"
                            @click="modalClose"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm rounded-xl shadow-md shadow-rose-600/20 transition-colors flex items-center gap-2 cursor-pointer"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            @click="submit"
                            :disabled="form.processing"
                        >
                            <font-awesome-icon v-if="form.processing" icon="fa-solid fa-spinner" spin />
                            <span v-else>Confirm Delete</span>
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>
