<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
});

// Search and filter states
const searchQuery = ref('');
const roleFilter = ref('All');
const statusFilter = ref('All');

// Pagination states
const currentPage = ref(1);
const itemsPerPage = 10;

// Reset page count on filter changes
watch([searchQuery, roleFilter, statusFilter], () => {
    currentPage.value = 1;
});

// Get active role from Spatie user object
const getUserRole = (user) => {
    if (user.roles && user.roles.length > 0) {
        return user.roles[0].name;
    }
    // Fallback based on email if Spatie is not fully populated
    return user.email === 'admin@test.com' ? 'Admin' : 'Employee';
};

// Get initials for avatar
const getUserInitials = (name) => {
    if (!name) return 'U';
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

// Helper for role pill styling
const getRoleClasses = (role) => {
    switch (role) {
        case 'Admin':
            return 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20';
        case 'Manager':
            return 'bg-purple-500/10 text-purple-400 border border-purple-500/20';
        case 'Employee':
            return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
        default:
            return 'bg-slate-500/10 text-slate-400 border border-slate-500/20';
    }
};

// Filtered users list (Instant client-side filter for premium speed)
const filteredUsers = computed(() => {
    return props.users.filter((user) => {
        const matchesSearch = 
            user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            user.email.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        const role = getUserRole(user);
        const matchesRole = roleFilter.value === 'All' || role === roleFilter.value;
        
        const userStatus = user.is_active ? 'Active' : 'Deactivated';
        const matchesStatus = statusFilter.value === 'All' || userStatus === statusFilter.value;
        return matchesSearch && matchesRole && matchesStatus;
    });
});

// Slices filtered items into 10 items per page
const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredUsers.value.slice(start, end);
});

// Calculate total page counts
const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredUsers.value.length / itemsPerPage));
});

// Change role modal states
const isRoleModalOpen = ref(false);
const selectedUserForRole = ref(null);

const roleForm = useForm({
    role: '',
});

const openRoleModal = (user) => {
    selectedUserForRole.value = user;
    roleForm.role = getUserRole(user);
    isRoleModalOpen.value = true;
};

const closeRoleModal = () => {
    isRoleModalOpen.value = false;
    selectedUserForRole.value = null;
    roleForm.clearErrors();
};

const submitRoleChange = () => {
    roleForm.patch(route('users.update-role', selectedUserForRole.value.id), {
        onSuccess: () => {
            closeRoleModal();
        },
    });
};

// Change manager modal states
const isManagerModalOpen = ref(false);
const selectedUserForManager = ref(null);

const managerForm = useForm({
    manager_id: '',
});

// Compute available managers (Managers or Admins, excluding the current user being edited)
const availableManagers = computed(() => {
    if (!selectedUserForManager.value) return [];
    return props.users.filter((u) => {
        const uRole = getUserRole(u);
        return u.id !== selectedUserForManager.value.id && (uRole === 'Manager' || uRole === 'Admin');
    });
});

const openManagerModal = (user) => {
    selectedUserForManager.value = user;
    managerForm.manager_id = user.manager_id || '';
    isManagerModalOpen.value = true;
};

const closeManagerModal = () => {
    isManagerModalOpen.value = false;
    selectedUserForManager.value = null;
    managerForm.clearErrors();
};

const submitManagerChange = () => {
    managerForm.patch(route('users.update-manager', selectedUserForManager.value.id), {
        onSuccess: () => {
            closeManagerModal();
        },
    });
};

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const toggleUserStatus = (user) => {
    // Safety check: Prevent deactivating your own logged-in account
    if (user.id === currentUser.value.id) {
        alert("For safety reasons, you cannot deactivate your own active session.");
        return;
    }
    
    router.patch(route('users.toggle-status', user.id));
};
</script>

<template>
    <Head title="Users Management" />

    <AuthenticatedLayout>
        <template #header>
            <span>Users Management</span>
        </template>

        <!-- TOP INTRODUCTION CARD -->
        <div class="mb-8 p-6 rounded-2xl bg-gradient-to-r from-slate-900/50 to-slate-900/20 border border-slate-800/80 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="space-y-1">
                <h2 class="text-xl font-bold text-white tracking-tight">Organization Directory</h2>
                <p class="text-slate-400 text-sm">View, manage, and assign reporting structures for all employees across the organization.</p>
            </div>
            <!-- Quick Add button (Visual demonstration) -->
            <button class="shrink-0 flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 active:scale-[0.98] text-white rounded-xl text-sm font-bold tracking-wide transition-all shadow-lg shadow-indigo-500/20">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add New Employee
            </button>
        </div>

        <!-- SEARCH AND FILTER CONTROLS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Search bar -->
            <div class="md:col-span-2 relative group">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 group-focus-within:text-indigo-400 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Search by name or email..."
                    class="block w-full pl-11 pr-4 py-2.5 bg-slate-900/40 border border-slate-800/80 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm"
                />
            </div>

            <!-- Role filter -->
            <div class="relative">
                <select
                    v-model="roleFilter"
                    class="block w-full px-4 py-2.5 bg-slate-900/40 border border-slate-800/80 rounded-xl text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm appearance-none cursor-pointer"
                >
                    <option value="All">All Roles</option>
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Employee">Employee</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <!-- Status filter -->
            <div class="relative">
                <select
                    v-model="statusFilter"
                    class="block w-full px-4 py-2.5 bg-slate-900/40 border border-slate-800/80 rounded-xl text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm appearance-none cursor-pointer"
                >
                    <option value="All">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Deactivated">Deactivated</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- USERS TABLE CONTAINER -->
        <div class="p-6 rounded-2xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800/60 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                            <th class="py-3 pr-4">Employee</th>
                            <th class="py-3 px-4">Role</th>
                            <th class="py-3 px-4">Direct Manager</th>
                            <th class="py-3 px-4">Account Status</th>
                            <th class="py-3 pl-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/30 text-sm">
                        <tr v-if="filteredUsers.length === 0">
                            <td colspan="5" class="py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <svg class="w-12 h-12 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span class="font-medium text-slate-400">No employees found matching the filters.</span>
                                </div>
                            </td>
                        </tr>
                        <tr 
                            v-else 
                            v-for="user in paginatedUsers" 
                            :key="user.id" 
                            class="text-slate-300 hover:bg-slate-800/10 transition-colors"
                        >
                            <!-- User profile (Avatar, Name, Email) -->
                            <td class="py-4 pr-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500/20 to-purple-500/20 border border-indigo-500/20 text-indigo-400 font-bold text-sm flex items-center justify-center shrink-0 shadow">
                                        {{ getUserInitials(user.name) }}
                                    </div>
                                    <div class="overflow-hidden">
                                        <span class="block font-semibold text-white truncate">{{ user.name }}</span>
                                        <span class="block text-xs text-slate-500 truncate mt-0.5">{{ user.email }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Spatie Role Badge -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span 
                                    class="inline-flex px-2.5 py-0.5 rounded-lg text-xs font-bold uppercase tracking-wider" 
                                    :class="getRoleClasses(getUserRole(user))"
                                >
                                    {{ getUserRole(user) }}
                                </span>
                            </td>

                            <!-- Reporting Manager -->
                            <td class="py-4 px-4 text-slate-400">
                                <div v-if="user.manager" class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md bg-slate-800 text-[10px] font-bold text-slate-300 flex items-center justify-center shrink-0">
                                        {{ getUserInitials(user.manager.name) }}
                                    </div>
                                    <span class="text-sm">{{ user.manager.name }}</span>
                                </div>
                                <span v-else class="text-slate-600 text-xs">Direct Report / None</span>
                            </td>

                            <!-- Active status -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span 
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold"
                                    :class="user.is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="user.is_active ? 'bg-emerald-400 shadow-lg shadow-emerald-400/50' : 'bg-rose-400 shadow-lg shadow-rose-400/50'"></span>
                                    {{ user.is_active ? 'Active' : 'Deactivated' }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 pl-4 text-right whitespace-nowrap">
                                <div v-if="getUserRole(user) !== 'Admin'" class="flex items-center justify-end gap-2.5">
                                    <!-- Assign Role Button (Indigo Theme) -->
                                    <button 
                                        @click="openRoleModal(user)"
                                        class="p-2 rounded-xl text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 hover:bg-indigo-500 hover:text-white hover:border-indigo-500 transition-all duration-200 shadow-md shadow-indigo-500/5" 
                                        title="Assign Role"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </button>
 
                                    <!-- Assign Manager Button (Purple Theme) -->
                                    <button 
                                        v-if="getUserRole(user) !== 'Admin'"
                                        @click="openManagerModal(user)"
                                        class="p-2 rounded-xl text-purple-400 bg-purple-500/10 border border-purple-500/20 hover:bg-purple-500 hover:text-white hover:border-purple-500 transition-all duration-200 shadow-md shadow-purple-500/5" 
                                        title="Assign Manager"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </button>
 
                                    <!-- Account Status Toggle (Tactile Slide Switch) -->
                                    <button 
                                        @click="toggleUserStatus(user)"
                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none shadow"
                                        :class="user.is_active ? 'bg-emerald-500/20 border-emerald-500/30' : 'bg-slate-800 border-slate-700'"
                                        :title="user.is_active ? 'Deactivate User' : 'Activate User'"
                                    >
                                        <span 
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full shadow ring-0 transition duration-200 ease-in-out"
                                            :class="user.is_active ? 'translate-x-5 bg-emerald-400' : 'translate-x-0 bg-slate-500'"
                                        ></span>
                                    </button>
 
                                    <!-- Options/More visual trigger (Slate/Grey Theme) -->
                                    <button 
                                        class="p-2 rounded-xl text-slate-300 bg-slate-800/60 border border-slate-700/50 hover:bg-slate-800 hover:text-white hover:border-slate-700 transition-all duration-200 shadow-md" 
                                        title="View Details"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-else class="text-xs font-semibold text-slate-500 italic pr-3 select-none tracking-wider">
                                    Protected Admin
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- PAGINATION SUMMARY & CONTROLS -->
            <div class="mt-6 pt-4 border-t border-slate-800/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-xs text-slate-500 font-medium relative z-10">
                <div class="flex items-center gap-2">
                    <span>
                        Showing 
                        <span class="text-slate-300 font-bold">{{ filteredUsers.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1 }}</span>
                        to 
                        <span class="text-slate-300 font-bold">{{ Math.min(currentPage * itemsPerPage, filteredUsers.length) }}</span>
                        of 
                        <span class="text-slate-300 font-bold">{{ filteredUsers.length }}</span> 
                        employees
                    </span>
                    <span v-if="filteredUsers.length !== users.length" class="text-slate-600">
                        (filtered from {{ users.length }} total)
                    </span>
                </div>
                
                <!-- Pagination Controls -->
                <div v-if="totalPages > 1" class="flex items-center gap-2">
                    <button
                        :disabled="currentPage === 1"
                        @click="currentPage--"
                        class="px-3 py-1.5 bg-slate-900 border border-slate-800 hover:bg-slate-800 disabled:opacity-40 disabled:hover:bg-slate-900 text-slate-300 rounded-lg text-xs font-semibold transition-all flex items-center gap-1 shadow cursor-pointer active:scale-[0.98]"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Previous
                    </button>
                    <span class="text-slate-400 font-medium px-2">
                        Page <span class="text-white font-bold">{{ currentPage }}</span> of <span class="text-white font-bold">{{ totalPages }}</span>
                    </span>
                    <button
                        :disabled="currentPage === totalPages"
                        @click="currentPage++"
                        class="px-3 py-1.5 bg-slate-900 border border-slate-800 hover:bg-slate-800 disabled:opacity-40 disabled:hover:bg-slate-900 text-slate-300 rounded-lg text-xs font-semibold transition-all flex items-center gap-1 shadow cursor-pointer active:scale-[0.98]"
                    >
                        Next
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
                
                <span v-else class="py-1 px-3.5 bg-slate-950/60 rounded border border-slate-800/80 w-max self-end sm:self-auto">Filtered instantly</span>
            </div>
        </div>

        <!-- CHANGE ROLE MODAL -->
        <div v-if="isRoleModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity p-4">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800/80 p-6 md:p-8 rounded-2xl shadow-2xl space-y-6 relative overflow-hidden">
                <div class="absolute w-[150px] h-[150px] bg-indigo-500/5 rounded-full blur-[40px] -right-10 -top-10"></div>
                
                <div class="flex items-center justify-between relative z-10">
                    <h3 class="text-lg font-bold text-white tracking-tight">Change User Role</h3>
                    <button @click="closeRoleModal" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="selectedUserForRole" class="p-4 rounded-xl bg-slate-950/40 border border-slate-800/60 flex items-center gap-3 relative z-10">
                    <div class="w-10 h-10 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 font-bold flex items-center justify-center shrink-0">
                        {{ getUserInitials(selectedUserForRole.name) }}
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-sm font-semibold text-white truncate">{{ selectedUserForRole.name }}</h4>
                        <p class="text-xs text-slate-500 truncate mt-0.5">{{ selectedUserForRole.email }}</p>
                    </div>
                </div>

                <form @submit.prevent="submitRoleChange" class="space-y-6 relative z-10">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Select New Role</label>
                        <div class="relative">
                            <select
                                v-model="roleForm.role"
                                required
                                class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm appearance-none cursor-pointer shadow-inner"
                            >
                                <option value="Admin">Admin (Full Control & Settings)</option>
                                <option value="Manager">Manager (Review & Approvals)</option>
                                <option value="Employee">Employee (Submit Leaves Only)</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        <p v-if="roleForm.errors.role" class="text-rose-500 text-xs mt-1 font-medium">{{ roleForm.errors.role }}</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeRoleModal"
                            class="px-4 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 transition-all text-sm font-semibold"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="roleForm.processing"
                            class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 active:scale-[0.98] disabled:opacity-50 text-white rounded-xl text-sm font-semibold tracking-wide transition-all shadow-lg shadow-indigo-500/20 flex items-center gap-2"
                        >
                            <svg v-if="roleForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- CHANGE MANAGER MODAL -->
        <div v-if="isManagerModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity p-4">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800/80 p-6 md:p-8 rounded-2xl shadow-2xl space-y-6 relative overflow-hidden">
                <div class="absolute w-[150px] h-[150px] bg-purple-500/5 rounded-full blur-[40px] -right-10 -top-10"></div>
                
                <div class="flex items-center justify-between relative z-10">
                    <h3 class="text-lg font-bold text-white tracking-tight">Assign Direct Manager</h3>
                    <button @click="closeManagerModal" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="selectedUserForManager" class="p-4 rounded-xl bg-slate-950/40 border border-slate-800/60 flex items-center gap-3 relative z-10">
                    <div class="w-10 h-10 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-400 font-bold flex items-center justify-center shrink-0">
                        {{ getUserInitials(selectedUserForManager.name) }}
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-sm font-semibold text-white truncate">{{ selectedUserForManager.name }}</h4>
                        <p class="text-xs text-slate-500 truncate mt-0.5">{{ selectedUserForManager.email }}</p>
                    </div>
                </div>

                <form @submit.prevent="submitManagerChange" class="space-y-6 relative z-10">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Select Reporting Manager</label>
                        <div class="relative">
                            <select
                                v-model="managerForm.manager_id"
                                class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition-all text-sm appearance-none cursor-pointer shadow-inner"
                            >
                                <option value="">No Manager (Direct Report)</option>
                                <option 
                                    v-for="mgr in availableManagers" 
                                    :key="mgr.id" 
                                    :value="mgr.id"
                                >
                                    {{ mgr.name }} ({{ getUserRole(mgr) }})
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        <p v-if="managerForm.errors.manager_id" class="text-rose-500 text-xs mt-1 font-medium">{{ managerForm.errors.manager_id }}</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeManagerModal"
                            class="px-4 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 transition-all text-sm font-semibold"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="managerForm.processing"
                            class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 active:scale-[0.98] disabled:opacity-50 text-white rounded-xl text-sm font-semibold tracking-wide transition-all shadow-lg shadow-purple-500/25 flex items-center gap-2"
                        >
                            <svg v-if="managerForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
