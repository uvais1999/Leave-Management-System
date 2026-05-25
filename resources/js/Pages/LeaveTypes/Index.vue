<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    leaveTypes: {
        type: Array,
        required: true,
    },
});

// Modal and state management
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);

const selectedLeaveType = ref(null);

// Forms using Inertia useForm
const addForm = useForm({
    name: '',
    max_days_per_year: '',
    description: '',
    is_active: true,
});

const editForm = useForm({
    name: '',
    max_days_per_year: '',
    description: '',
    is_active: true,
});

// Open and Close helper methods
const openAddModal = () => {
    addForm.reset();
    isAddModalOpen.value = true;
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
    addForm.clearErrors();
};

const openEditModal = (leaveType) => {
    selectedLeaveType.value = leaveType;
    editForm.name = leaveType.name;
    editForm.max_days_per_year = leaveType.max_days_per_year;
    editForm.description = leaveType.description || '';
    editForm.is_active = leaveType.is_active;
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    selectedLeaveType.value = null;
    editForm.clearErrors();
};

const openDeleteModal = (leaveType) => {
    selectedLeaveType.value = leaveType;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    selectedLeaveType.value = null;
};

// CRUD submissions
const submitAddForm = () => {
    addForm.post(route('leave-types.store'), {
        onSuccess: () => {
            closeAddModal();
        },
    });
};

const submitEditForm = () => {
    editForm.put(route('leave-types.update', selectedLeaveType.value.id), {
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const submitDelete = () => {
    router.delete(route('leave-types.destroy', selectedLeaveType.value.id), {
        onSuccess: () => {
            closeDeleteModal();
        },
    });
};

const toggleLeaveTypeStatus = (leaveType) => {
    router.patch(route('leave-types.toggle-status', leaveType.id));
};
</script>

<template>
    <Head title="Leave Types - Settings" />

    <AuthenticatedLayout>
        <template #header>
            <span>Leave Types Configuration</span>
        </template>

        <!-- TOP CARD HEADER -->
        <div class="mb-8 p-6 rounded-2xl bg-gradient-to-r from-slate-900/50 to-slate-900/20 border border-slate-800/80 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="space-y-1">
                <h2 class="text-xl font-bold text-white tracking-tight">Leave Categories</h2>
                <p class="text-slate-400 text-sm">Configure leave policies, maximum annual limits, and active status states for your organization.</p>
            </div>
            <button 
                @click="openAddModal"
                class="shrink-0 flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 active:scale-[0.98] text-white rounded-xl text-sm font-bold tracking-wide transition-all shadow-lg shadow-indigo-500/20"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Create Leave Type
            </button>
        </div>

        <!-- LEAVE TYPES DYNAMIC GRID DISPLAY -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div 
                v-for="type in leaveTypes" 
                :key="'card-' + type.id"
                class="p-6 rounded-2xl bg-slate-900/40 border border-slate-800/80 shadow flex flex-col justify-between relative overflow-hidden"
            >
                <!-- Backdrop highlights -->
                <div class="absolute w-[100px] h-[100px] bg-indigo-500/5 rounded-full blur-[20px] -right-5 -top-5"></div>
                
                <div class="space-y-4 relative z-10">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold border"
                              :class="type.is_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-800 text-slate-500 border-slate-700'"
                        >
                            {{ type.is_active ? 'ACTIVE' : 'INACTIVE' }}
                        </span>
                        
                        <span class="text-2xl font-extrabold text-white tracking-tight">
                            {{ type.max_days_per_year }} <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Days</span>
                        </span>
                    </div>

                    <div>
                        <h4 class="text-base font-bold text-white tracking-tight truncate">{{ type.name }}</h4>
                        <p class="text-xs text-slate-400 line-clamp-2 mt-1 leading-relaxed">{{ type.description || 'No description provided.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN TABLE VIEW -->
        <div class="p-6 rounded-2xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800/60 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                            <th class="py-3 pr-4">Leave Name</th>
                            <th class="py-3 px-4">Max Days / Year</th>
                            <th class="py-3 px-4">Description</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 pl-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/30 text-sm">
                        <tr v-if="leaveTypes.length === 0">
                            <td colspan="5" class="py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <svg class="w-12 h-12 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                                    </svg>
                                    <span class="font-medium text-slate-400">No leave types configured. Click "Create Leave Type" to begin.</span>
                                </div>
                            </td>
                        </tr>
                        <tr 
                            v-else 
                            v-for="type in leaveTypes" 
                            :key="type.id" 
                            class="text-slate-300 hover:bg-slate-800/10 transition-colors"
                        >
                            <!-- Leave Name -->
                            <td class="py-4 pr-4">
                                <span class="font-bold text-white">{{ type.name }}</span>
                            </td>

                            <!-- Max Days -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="inline-flex px-3 py-1 rounded-lg text-xs font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 shadow">
                                    {{ type.max_days_per_year }} Days
                                </span>
                            </td>

                            <!-- Description -->
                            <td class="py-4 px-4 text-slate-400 max-w-xs truncate">
                                <span>{{ type.description || '—' }}</span>
                            </td>

                            <!-- Status Toggle -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <button 
                                    @click="toggleLeaveTypeStatus(type)"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none shadow"
                                    :class="type.is_active ? 'bg-emerald-500/20 border-emerald-500/30' : 'bg-slate-800 border-slate-700'"
                                    :title="type.is_active ? 'Deactivate Leave' : 'Activate Leave'"
                                >
                                    <span 
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full shadow ring-0 transition duration-200 ease-in-out"
                                        :class="type.is_active ? 'translate-x-5 bg-emerald-400' : 'translate-x-0 bg-slate-500'"
                                    ></span>
                                </button>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 pl-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2.5">
                                    <!-- Edit Button (Emerald) -->
                                    <button 
                                        @click="openEditModal(type)"
                                        class="p-2 rounded-xl text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-all duration-200 shadow shadow-emerald-500/5" 
                                        title="Edit Leave Type"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    <!-- Delete Button (Rose) -->
                                    <button 
                                        @click="openDeleteModal(type)"
                                        class="p-2 rounded-xl text-rose-400 bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all duration-200 shadow shadow-rose-500/5" 
                                        title="Delete Leave Type"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CREATE LEAVE TYPE MODAL -->
        <div v-if="isAddModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity p-4">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800/80 p-6 md:p-8 rounded-2xl shadow-2xl space-y-6 relative overflow-hidden">
                <div class="absolute w-[150px] h-[150px] bg-indigo-500/5 rounded-full blur-[40px] -right-10 -top-10"></div>
                
                <div class="flex items-center justify-between relative z-10">
                    <h3 class="text-lg font-bold text-white tracking-tight">Create Leave Type</h3>
                    <button @click="closeAddModal" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAddForm" class="space-y-5 relative z-10">
                    <!-- Name Field -->
                    <div class="space-y-2">
                        <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Leave Name</label>
                        <input
                            id="name"
                            type="text"
                            v-model="addForm.name"
                            required
                            placeholder="e.g. Casual Leave"
                            class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                        />
                        <p v-if="addForm.errors.name" class="text-rose-500 text-xs font-medium">{{ addForm.errors.name }}</p>
                    </div>

                    <!-- Max Days -->
                    <div class="space-y-2">
                        <label for="max_days" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Max Days Per Year</label>
                        <input
                            id="max_days"
                            type="number"
                            v-model="addForm.max_days_per_year"
                            required
                            placeholder="e.g. 12"
                            class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                        />
                        <p v-if="addForm.errors.max_days_per_year" class="text-rose-500 text-xs font-medium">{{ addForm.errors.max_days_per_year }}</p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label for="description" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Description</label>
                        <textarea
                            id="description"
                            v-model="addForm.description"
                            rows="3"
                            placeholder="Provide a brief explanation of when to request this leave type..."
                            class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                        ></textarea>
                        <p v-if="addForm.errors.description" class="text-rose-500 text-xs font-medium">{{ addForm.errors.description }}</p>
                    </div>

                    <!-- Status Selection -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Status</label>
                        <div class="relative">
                            <select
                                v-model="addForm.is_active"
                                class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm appearance-none cursor-pointer shadow-inner"
                            >
                                <option :value="true">Active (Available for Employees)</option>
                                <option :value="false">Inactive (Suspended)</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeAddModal"
                            class="px-4 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 transition-all text-sm font-semibold"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="addForm.processing"
                            class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 active:scale-[0.98] disabled:opacity-50 text-white rounded-xl text-sm font-semibold tracking-wide transition-all shadow-lg shadow-indigo-500/20 flex items-center gap-2"
                        >
                            <svg v-if="addForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- EDIT LEAVE TYPE MODAL -->
        <div v-if="isEditModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity p-4">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800/80 p-6 md:p-8 rounded-2xl shadow-2xl space-y-6 relative overflow-hidden">
                <div class="absolute w-[150px] h-[150px] bg-indigo-500/5 rounded-full blur-[40px] -right-10 -top-10"></div>
                
                <div class="flex items-center justify-between relative z-10">
                    <h3 class="text-lg font-bold text-white tracking-tight">Edit Leave Type</h3>
                    <button @click="closeEditModal" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEditForm" class="space-y-5 relative z-10">
                    <!-- Name Field -->
                    <div class="space-y-2">
                        <label for="edit_name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Leave Name</label>
                        <input
                            id="edit_name"
                            type="text"
                            v-model="editForm.name"
                            required
                            placeholder="e.g. Casual Leave"
                            class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                        />
                        <p v-if="editForm.errors.name" class="text-rose-500 text-xs font-medium">{{ editForm.errors.name }}</p>
                    </div>

                    <!-- Max Days -->
                    <div class="space-y-2">
                        <label for="edit_max_days" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Max Days Per Year</label>
                        <input
                            id="edit_max_days"
                            type="number"
                            v-model="editForm.max_days_per_year"
                            required
                            placeholder="e.g. 12"
                            class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                        />
                        <p v-if="editForm.errors.max_days_per_year" class="text-rose-500 text-xs font-medium">{{ editForm.errors.max_days_per_year }}</p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label for="edit_description" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Description</label>
                        <textarea
                            id="edit_description"
                            v-model="editForm.description"
                            rows="3"
                            placeholder="Provide a brief explanation of when to request this leave type..."
                            class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                        ></textarea>
                        <p v-if="editForm.errors.description" class="text-rose-500 text-xs font-medium">{{ editForm.errors.description }}</p>
                    </div>

                    <!-- Status Selection -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Status</label>
                        <div class="relative">
                            <select
                                v-model="editForm.is_active"
                                class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm appearance-none cursor-pointer shadow-inner"
                            >
                                <option :value="true">Active (Available for Employees)</option>
                                <option :value="false">Inactive (Suspended)</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="px-4 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 transition-all text-sm font-semibold"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 active:scale-[0.98] disabled:opacity-50 text-white rounded-xl text-sm font-semibold tracking-wide transition-all shadow-lg shadow-indigo-500/20 flex items-center gap-2"
                        >
                            <svg v-if="editForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DELETE CONFIRMATION MODAL -->
        <div v-if="isDeleteModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity p-4">
            <div class="w-full max-w-sm bg-slate-900 border border-slate-800/80 p-6 rounded-2xl shadow-2xl space-y-6 relative overflow-hidden text-center">
                <div class="absolute w-[100px] h-[100px] bg-rose-500/5 rounded-full blur-[30px] -right-5 -top-5"></div>
                
                <div class="mx-auto w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center relative z-10">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <div class="space-y-2 relative z-10">
                    <h3 class="text-lg font-bold text-white tracking-tight">Delete Leave Type</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Are you sure you want to permanently delete <span class="text-white font-semibold">"{{ selectedLeaveType?.name }}"</span>? 
                        This action cannot be undone and may affect active employee balances.
                    </p>
                </div>

                <div class="flex items-center justify-center gap-3 pt-2 relative z-10">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="px-4 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 transition-all text-sm font-semibold"
                    >
                        Cancel
                    </button>
                    <button
                        @click="submitDelete"
                        class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 active:scale-[0.98] text-white rounded-xl text-sm font-semibold tracking-wide transition-all shadow-lg shadow-rose-500/25"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
