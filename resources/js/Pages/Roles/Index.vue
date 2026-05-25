<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    roles: {
        type: Array,
        required: true,
    },
    permissions: {
        type: Array,
        required: true,
    },
});

const page = usePage();

// Modals state
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedRole = ref(null);

// Forms
const addForm = useForm({
    name: '',
    permissions: [],
});

const editForm = useForm({
    name: '',
    permissions: [],
});

// Group permissions into logical categories for visual excellence
const permissionGroups = {
    'User Directory': {
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        color: 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20',
        items: ['users.view', 'users.create', 'users.edit', 'users.delete', 'roles.assign']
    },
    'Leave Types & Policy': {
        icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
        color: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
        items: ['leave-types.manage']
    },
    'Subordinate & Admin Review': {
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        color: 'text-purple-400 bg-purple-500/10 border-purple-500/20',
        items: ['leaves.view-all', 'leaves.view-subordinates', 'leaves.approve', 'leaves.reject']
    },
    'Employee Portal': {
        icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        color: 'text-amber-400 bg-amber-500/10 border-amber-500/20',
        items: ['leaves.create', 'leaves.view', 'leaves.update', 'leaves.delete']
    }
};

// Help helper to get permission description
const getPermissionLabel = (permName) => {
    switch (permName) {
        case 'users.view': return 'View Employees Directory';
        case 'users.create': return 'Register New Employees';
        case 'users.edit': return 'Edit Employee Accounts';
        case 'users.delete': return 'Delete Employee Records';
        case 'roles.assign': return 'Assign Roles / Manage Structure';
        case 'leave-types.manage': return 'Leave Policies Full CRUD';
        case 'leaves.view-all': return 'Review Global Company Feed';
        case 'leaves.view-subordinates': return 'Review Direct Reports Feed';
        case 'leaves.approve': return 'Approve Pending Applications';
        case 'leaves.reject': return 'Reject Applications with Reason';
        case 'leaves.create': return 'Submit Own Leave Requests';
        case 'leaves.view': return 'View Own Request History';
        case 'leaves.update': return 'Edit Own Pending Requests';
        case 'leaves.delete': return 'Cancel/Delete Own Pending Requests';
        default: return permName;
    }
};

// Local toast notification states
const showToast = ref(false);
const toastType = ref('success');
const toastMessage = ref('');
let toastTimeout = null;

const triggerToast = (type, message) => {
    if (toastTimeout) clearTimeout(toastTimeout);
    toastType.value = type;
    toastMessage.value = message;
    showToast.value = true;
    
    toastTimeout = setTimeout(() => {
        showToast.value = false;
    }, 4000);
};

// Reactively watch for new flash updates from the server
watch(() => page.props.flash, (newVal) => {
    if (newVal?.success) {
        triggerToast('success', newVal.success);
    } else if (newVal?.error) {
        triggerToast('error', newVal.error);
    }
}, { deep: true, immediate: true });

// Check if a role is a system core default role
const isCoreRole = (roleName) => {
    return ['Admin', 'Manager', 'Employee'].includes(roleName);
};

// Role styling helpers
const getRoleHeaderColor = (roleName) => {
    switch (roleName) {
        case 'Admin': return 'from-indigo-500/20 to-purple-500/5 border-indigo-500/30 text-indigo-400';
        case 'Manager': return 'from-purple-500/20 to-pink-500/5 border-purple-500/30 text-purple-400';
        case 'Employee': return 'from-emerald-500/20 to-teal-500/5 border-emerald-500/30 text-emerald-400';
        default: return 'from-slate-800/50 to-slate-900/5 border-slate-700/50 text-slate-300';
    }
};

// Modal helpers
const openAddModal = () => {
    addForm.reset();
    isAddModalOpen.value = true;
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
    addForm.clearErrors();
};

const openEditModal = (role) => {
    selectedRole.value = role;
    editForm.name = role.name;
    editForm.permissions = role.permissions.map(p => p.name);
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    selectedRole.value = null;
    editForm.clearErrors();
};

const openDeleteModal = (role) => {
    selectedRole.value = role;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    selectedRole.value = null;
};

// Submissions
const submitAddRole = () => {
    addForm.post(route('roles.store'), {
        onSuccess: () => {
            closeAddModal();
        },
    });
};

const submitEditRole = () => {
    editForm.put(route('roles.update', selectedRole.value.id), {
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const submitDeleteRole = () => {
    router.delete(route('roles.destroy', selectedRole.value.id), {
        onSuccess: () => {
            closeDeleteModal();
        },
        onError: () => {
            closeDeleteModal();
        }
    });
};
</script>

<template>
    <Head title="Roles & Permissions - Structure" />

    <AuthenticatedLayout>
        <template #header>
            <span>Roles & Permissions Settings</span>
        </template>

        <!-- FLASH TOAST NOTIFICATIONS -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showToast" class="fixed top-20 right-6 z-50 max-w-sm w-full bg-slate-900/95 backdrop-blur-2xl border rounded-xl shadow-2xl p-4 flex items-start gap-3" :class="toastType === 'success' ? 'border-emerald-500/20 shadow-emerald-500/5' : 'border-rose-500/20 shadow-rose-500/5'">
                <!-- Success icon -->
                <div v-if="toastType === 'success'" class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                    </svg>
                </div>
                <!-- Error icon -->
                <div v-else class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <!-- Text -->
                <div class="flex-1 overflow-hidden">
                    <h4 class="text-sm font-semibold text-white">{{ toastType === 'success' ? 'Success' : 'Error Occurred' }}</h4>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">{{ toastMessage }}</p>
                </div>
                <!-- Close -->
                <button @click="showToast = false" class="text-slate-500 hover:text-slate-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- TOP CARD HEADER -->
        <div class="mb-8 p-6 rounded-2xl bg-gradient-to-r from-slate-900/50 to-slate-900/20 border border-slate-800/80 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="space-y-1">
                <h2 class="text-xl font-bold text-white tracking-tight">Access Control & Structures</h2>
                <p class="text-slate-400 text-sm">Define Spatie system roles and link specific functional capabilities to manage platform security boundaries.</p>
            </div>
            <button 
                @click="openAddModal"
                class="shrink-0 flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 active:scale-[0.98] text-white rounded-xl text-sm font-bold tracking-wide transition-all shadow-lg shadow-indigo-500/20"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Create Custom Role
            </button>
        </div>

        <!-- ROLES OVERVIEW GRID CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div 
                v-for="role in roles" 
                :key="'card-' + role.id"
                class="p-6 rounded-2xl bg-gradient-to-tr border shadow flex flex-col justify-between relative overflow-hidden transition-all duration-300 hover:shadow-lg hover:shadow-indigo-500/5"
                :class="getRoleHeaderColor(role.name)"
            >
                <!-- Glow backdrop -->
                <div class="absolute w-[120px] h-[120px] bg-indigo-500/5 rounded-full blur-[25px] -right-5 -top-5"></div>
                
                <div class="space-y-4 relative z-10">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold border tracking-wider bg-slate-950/65"
                              :class="isCoreRole(role.name) ? 'text-indigo-400 border-indigo-500/20' : 'text-slate-500 border-slate-800'"
                        >
                            {{ isCoreRole(role.name) ? 'SYSTEM CORE' : 'CUSTOM ROLE' }}
                        </span>
                        
                        <span class="text-2xl font-extrabold text-white tracking-tight">
                            {{ role.permissions?.length || 0 }} <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Capabilities</span>
                        </span>
                    </div>

                    <div>
                        <h4 class="text-lg font-bold text-white tracking-tight truncate">{{ role.name }}</h4>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed line-clamp-2">
                            {{ isCoreRole(role.name) ? `System core default ${role.name} role mapped to critical organization workflows.` : 'Custom administrator-defined role targeting isolated security operations.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROLES LIST TABLE VIEW -->
        <div class="p-6 rounded-2xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800/60 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                            <th class="py-3 pr-4 w-1/5">Role Name</th>
                            <th class="py-3 px-4 w-3/5">Associated Spatie Permissions</th>
                            <th class="py-3 pl-4 text-right w-1/5">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/30 text-sm">
                        <tr v-if="roles.length === 0">
                            <td colspan="3" class="py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <svg class="w-12 h-12 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01" />
                                    </svg>
                                    <span class="font-medium text-slate-400">No roles configured. Click "Create Custom Role" to begin.</span>
                                </div>
                            </td>
                        </tr>
                        <tr 
                            v-else 
                            v-for="role in roles" 
                            :key="role.id" 
                            class="text-slate-300 hover:bg-slate-800/10 transition-colors"
                        >
                            <!-- Role Name -->
                            <td class="py-4 pr-4 font-bold text-white whitespace-nowrap align-top">
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span>{{ role.name }}</span>
                                    <span v-if="isCoreRole(role.name)" class="text-[8px] bg-slate-800 text-indigo-400 px-1.5 py-0.5 rounded font-extrabold uppercase tracking-wide border border-indigo-500/10">Core</span>
                                </div>
                            </td>

                            <!-- Permissions List -->
                            <td class="py-4 px-4 align-top">
                                <div class="flex flex-wrap gap-1.5">
                                    <span 
                                        v-for="perm in role.permissions" 
                                        :key="perm.id"
                                        class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-950/60 border border-slate-800/80 text-slate-400 shadow-sm"
                                    >
                                        {{ perm.name }}
                                    </span>
                                    <span v-if="role.permissions.length === 0" class="text-xs italic text-slate-600 select-none">No active permissions synced</span>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 pl-4 text-right whitespace-nowrap align-top">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit Button (Always enabled, allows updating permissions of core roles!) -->
                                    <button 
                                        @click="openEditModal(role)"
                                        class="p-2 rounded-xl text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-all duration-200 shadow shadow-emerald-500/5" 
                                        title="Configure Role Permissions"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    <!-- Delete Button (Rose - disabled for Core System Roles) -->
                                    <button 
                                        v-if="!isCoreRole(role.name)"
                                        @click="openDeleteModal(role)"
                                        class="p-2 rounded-xl text-rose-400 bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all duration-200 shadow shadow-rose-500/5" 
                                        title="Delete Role"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    <span v-else class="text-[9px] font-bold text-slate-600 italic select-none pr-2">Protected Core</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CREATE ROLE MODAL -->
        <div v-if="isAddModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-start justify-center z-50 transition-opacity p-4 overflow-y-auto py-10 md:py-20">
            <form @submit.prevent="submitAddRole" class="w-full max-w-2xl bg-slate-900 border border-slate-800/80 p-6 md:p-8 rounded-2xl shadow-2xl space-y-6 relative overflow-hidden my-auto text-left">
                <div class="absolute w-[200px] h-[200px] bg-indigo-500/5 rounded-full blur-[50px] -right-10 -top-10"></div>
                
                <div class="flex items-center justify-between relative z-10 shrink-0">
                    <h3 class="text-lg font-bold text-white tracking-tight">Create Custom Role</h3>
                    <button type="button" @click="closeAddModal" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="space-y-6 relative z-10 text-left">
                    <!-- Role Name -->
                    <div class="space-y-2">
                        <label for="add_role_name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Role Name</label>
                        <input
                            id="add_role_name"
                            type="text"
                            v-model="addForm.name"
                            required
                            placeholder="e.g. HR Specialist"
                            class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-655 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                        />
                        <p v-if="addForm.errors.name" class="text-rose-500 text-xs font-medium">{{ addForm.errors.name }}</p>
                    </div>

                    <!-- Permissions Grid grouped nicely -->
                    <div class="space-y-4">
                        <h4 class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Assign Capabilities / System Permissions</h4>
                        <div class="space-y-4">
                            <div 
                                v-for="(group, groupName) in permissionGroups" 
                                :key="groupName"
                                class="p-5 rounded-2xl bg-slate-950/40 border border-slate-800/60 shadow flex flex-col space-y-4"
                            >
                                <div class="flex items-center gap-3 border-b border-slate-800/40 pb-3">
                                    <div class="p-1.5 rounded-lg border text-sm" :class="group.color">
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" :d="group.icon" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold text-white uppercase tracking-wider">{{ groupName }}</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <label 
                                        v-for="perm in group.items" 
                                        :key="perm"
                                        class="flex items-start gap-3 cursor-pointer group text-slate-400 hover:text-slate-200 transition-colors select-none"
                                    >
                                        <input 
                                            type="checkbox" 
                                            :value="perm" 
                                            v-model="addForm.permissions"
                                            class="rounded border-slate-805 bg-slate-950 text-indigo-600 focus:ring-indigo-500/50 focus:ring-offset-slate-900 w-4.5 h-4.5 mt-0.5 transition-all cursor-pointer"
                                        />
                                        <div class="space-y-0.5">
                                            <span class="block text-xs font-bold text-slate-200 group-hover:text-white transition-colors">{{ perm }}</span>
                                            <span class="block text-[10px] text-slate-500 leading-normal">{{ getPermissionLabel(perm) }}</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer (Fixed) -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800/40 shrink-0 relative z-10">
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
                        Create Role
                    </button>
                </div>
            </form>
        </div>

        <!-- EDIT ROLE MODAL -->
        <div v-if="isEditModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-start justify-center z-50 transition-opacity p-4 overflow-y-auto py-10 md:py-20">
            <form @submit.prevent="submitEditRole" class="w-full max-w-2xl bg-slate-900 border border-slate-800/80 p-6 md:p-8 rounded-2xl shadow-2xl space-y-6 relative overflow-hidden my-auto text-left">
                <div class="absolute w-[200px] h-[200px] bg-indigo-500/5 rounded-full blur-[50px] -right-10 -top-10"></div>
                
                <div class="flex items-center justify-between relative z-10 shrink-0">
                    <div class="space-y-1">
                        <h3 class="text-lg font-bold text-white tracking-tight">Configure Role Permissions</h3>
                        <p class="text-xs text-slate-400" v-if="isCoreRole(selectedRole?.name)">You are customizing Spatie permissions for core role <span class="text-white font-bold">"{{ selectedRole?.name }}"</span>.</p>
                    </div>
                    <button type="button" @click="closeEditModal" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="space-y-6 relative z-10 text-left">
                    <!-- Role Name (Readonly for core roles) -->
                    <div class="space-y-2">
                        <label for="edit_role_name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Role Name</label>
                        <input
                            id="edit_role_name"
                            type="text"
                            v-model="editForm.name"
                            required
                            :readonly="isCoreRole(selectedRole?.name)"
                            class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                            :class="isCoreRole(selectedRole?.name) ? 'opacity-60 cursor-not-allowed border-slate-800 bg-slate-950/40 text-slate-400' : ''"
                        />
                        <p v-if="isCoreRole(selectedRole?.name)" class="text-[10px] text-indigo-400/80 font-medium tracking-wide">Core default role name cannot be modified to preserve system stability.</p>
                        <p v-if="editForm.errors.name" class="text-rose-500 text-xs font-medium">{{ editForm.errors.name }}</p>
                    </div>

                    <!-- Permissions Grid grouped nicely -->
                    <div class="space-y-4">
                        <h4 class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Assign Capabilities / System Permissions</h4>
                        <div class="space-y-4">
                            <div 
                                v-for="(group, groupName) in permissionGroups" 
                                :key="groupName"
                                class="p-5 rounded-2xl bg-slate-950/40 border border-slate-800/60 shadow flex flex-col space-y-4.5"
                            >
                                <div class="flex items-center gap-3 border-b border-slate-800/40 pb-3">
                                    <div class="p-1.5 rounded-lg border text-sm" :class="group.color">
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" :d="group.icon" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold text-white uppercase tracking-wider">{{ groupName }}</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <label 
                                        v-for="perm in group.items" 
                                        :key="perm"
                                        class="flex items-start gap-3 cursor-pointer group text-slate-400 hover:text-slate-200 transition-colors select-none"
                                    >
                                        <input 
                                            type="checkbox" 
                                            :value="perm" 
                                            v-model="editForm.permissions"
                                            class="rounded border-slate-805 bg-slate-950 text-indigo-600 focus:ring-indigo-500/50 focus:ring-offset-slate-900 w-4.5 h-4.5 mt-0.5 transition-all cursor-pointer"
                                        />
                                        <div class="space-y-0.5">
                                            <span class="block text-xs font-bold text-slate-200 group-hover:text-white transition-colors">{{ perm }}</span>
                                            <span class="block text-[10px] text-slate-500 leading-normal">{{ getPermissionLabel(perm) }}</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer (Fixed) -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800/40 shrink-0 relative z-10">
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
                    <h3 class="text-lg font-bold text-white tracking-tight">Delete Custom Role</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Are you sure you want to permanently delete custom role <span class="text-white font-semibold">"{{ selectedRole?.name }}"</span>? 
                        This action cannot be undone and will automatically unassign this role from all related employee accounts.
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
                        @click="submitDeleteRole"
                        class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 active:scale-[0.98] text-white rounded-xl text-sm font-semibold tracking-wide transition-all shadow-lg shadow-rose-500/25"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom styled scrolls for the visual modal permissions grid */
.show-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.show-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.show-scrollbar::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 9999px;
}
.show-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
</style>
