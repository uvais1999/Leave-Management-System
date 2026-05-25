<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    myApplications: { type: Array, default: () => [] },
    teamApplications: { type: Array, default: () => [] },
    allApplications: { type: Array, default: () => [] },
    leaveTypes: { type: Array, required: true },
    isAdmin: { type: Boolean, required: true },
    isManager: { type: Boolean, required: true },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// Active Tab state
// Default to 'subordinates' for managers/admins to quickly see team requests, otherwise 'my-leaves'
const activeTab = ref(props.isAdmin ? 'all-leaves' : props.isManager ? 'team-leaves' : 'my-leaves');

// Modals states
const isApplyModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isRejectModalOpen = ref(false);

const selectedLeave = ref(null);

// Forms using useForm
const applyForm = useForm({
    leave_type_id: '',
    from_date: '',
    to_date: '',
    reason: '',
    attachment: null,
});

const editForm = useForm({
    leave_type_id: '',
    from_date: '',
    to_date: '',
    reason: '',
    attachment: null,
});

const rejectForm = useForm({
    rejection_reason: '',
});

// Auto-calculation of total days (Real-time reactive calculation)
const calculateTotalDays = (fromDateStr, toDateStr) => {
    if (!fromDateStr || !toDateStr) return 0;
    const start = new Date(fromDateStr);
    const end = new Date(toDateStr);
    
    if (isNaN(start.getTime()) || isNaN(end.getTime())) return 0;
    if (end < start) return 0;
    
    // Difference in milliseconds
    const diffTime = Math.abs(end - start);
    // Convert to days and add 1 (inclusive)
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
};

const applyTotalDays = computed(() => calculateTotalDays(applyForm.from_date, applyForm.to_date));
const editTotalDays = computed(() => calculateTotalDays(editForm.from_date, editForm.to_date));

// Open & Close handlers
const openApplyModal = () => {
    applyForm.reset();
    isApplyModalOpen.value = true;
};

const closeApplyModal = () => {
    isApplyModalOpen.value = false;
    applyForm.clearErrors();
};

const openEditModal = (leave) => {
    selectedLeave.value = leave;
    editForm.leave_type_id = leave.leave_type_id;
    
    // Convert Date object/string to YYYY-MM-DD for date inputs
    editForm.from_date = leave.from_date ? leave.from_date.substring(0, 10) : '';
    editForm.to_date = leave.to_date ? leave.to_date.substring(0, 10) : '';
    editForm.reason = leave.reason;
    editForm.attachment = null;
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    selectedLeave.value = null;
    editForm.clearErrors();
};

const openDeleteModal = (leave) => {
    selectedLeave.value = leave;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    selectedLeave.value = null;
};

const openRejectModal = (leave) => {
    selectedLeave.value = leave;
    rejectForm.reset();
    isRejectModalOpen.value = true;
};

const closeRejectModal = () => {
    isRejectModalOpen.value = false;
    selectedLeave.value = null;
    rejectForm.clearErrors();
};

// Submissions
const submitApply = () => {
    // Form data upload mode is automatically handled by Inertia when passing files
    applyForm.post(route('leaves.store'), {
        onSuccess: () => {
            closeApplyModal();
        },
    });
};

const submitEdit = () => {
    // Since PHP doesn't natively parse multipart/form-data on PUT/PATCH requests,
    // we use Inertia's transform helper to spoof PUT over a standard POST request.
    // We also use editForm directly to automatically capture backend validation errors.
    editForm.transform((data) => {
        const formData = {
            ...data,
            _method: 'PUT',
        };
        // Avoid sending null or empty files to prevent backend mime/file validation failures
        if (!data.attachment) {
            delete formData.attachment;
        }
        return formData;
    }).post(route('leaves.update', selectedLeave.value.id), {
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const submitDelete = () => {
    router.delete(route('leaves.destroy', selectedLeave.value.id), {
        onSuccess: () => {
            closeDeleteModal();
        },
        onError: () => {
            closeDeleteModal();
        }
    });
};

const approveLeave = (leave) => {
    router.patch(route('leaves.approve', leave.id));
};

const submitReject = () => {
    rejectForm.patch(route('leaves.reject', selectedLeave.value.id), {
        onSuccess: () => {
            closeRejectModal();
        },
    });
};

// File upload listeners
const handleApplyFileUpload = (event) => {
    applyForm.attachment = event.target.files[0];
};

const handleEditFileUpload = (event) => {
    editForm.attachment = event.target.files[0];
};

// Pill status helper
const getStatusClasses = (status) => {
    switch (status) {
        case 'Approved':
            return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
        case 'Pending':
            return 'bg-amber-500/10 text-amber-400 border border-amber-500/20';
        case 'Rejected':
            return 'bg-rose-500/10 text-rose-400 border border-rose-500/20';
        default:
            return 'bg-slate-500/10 text-slate-400 border border-slate-500/20';
    }
};

// Summary metrics computed locally
const pendingCount = computed(() => {
    const arr = props.isAdmin ? props.allApplications : props.teamApplications;
    return arr.filter(a => a.status === 'Pending').length;
});

const approvedCount = computed(() => {
    const arr = props.isAdmin ? props.allApplications : props.myApplications;
    return arr.filter(a => a.status === 'Approved').length;
});
// Local toast notification states (avoids mutating read-only Inertia props)
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

// Pagination and Filtering States
const filterFromDate = ref('');
const filterToDate = ref('');
const filterStatus = ref('');
const searchEmployeeName = ref('');
const currentPage = ref(1);
const itemsPerPage = 10;

// Reset page count and clear query states on tab changes
watch(activeTab, () => {
    currentPage.value = 1;
    filterFromDate.value = '';
    filterToDate.value = '';
    filterStatus.value = '';
    searchEmployeeName.value = '';
});

// Reset page count on filter alterations
watch([filterFromDate, filterToDate, filterStatus, searchEmployeeName], () => {
    currentPage.value = 1;
});

// Reactively filter applications based on active tab, date ranges, status and search keys
const filteredApplications = computed(() => {
    let items = [];
    if (activeTab.value === 'my-leaves') {
        items = props.myApplications;
    } else if (activeTab.value === 'team-leaves') {
        items = props.teamApplications;
    } else if (activeTab.value === 'all-leaves') {
        items = props.allApplications;
    }

    return items.filter(app => {
        // 1. Date Range Filters
        if (app.from_date && app.to_date) {
            const appFrom = app.from_date.substring(0, 10);
            const appTo = app.to_date.substring(0, 10);

            if (filterFromDate.value && appFrom < filterFromDate.value) {
                return false;
            }
            if (filterToDate.value && appTo > filterToDate.value) {
                return false;
            }
        }

        // 2. Status Filter
        if (filterStatus.value && app.status !== filterStatus.value) {
            return false;
        }

        // 3. Search by Employee Name (Case-insensitive check, works when app.user exists)
        if (searchEmployeeName.value && app.user) {
            const nameLower = app.user.name.toLowerCase();
            const queryLower = searchEmployeeName.value.toLowerCase();
            if (!nameLower.includes(queryLower)) {
                return false;
            }
        }

        return true;
    });
});

// Slices filtered items into 10 items per page
const paginatedApplications = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredApplications.value.slice(start, end);
});

// Calculate total page counts
const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredApplications.value.length / itemsPerPage));
});
</script>

<template>
    <Head title="Leave Applications - System" />

    <AuthenticatedLayout>
        <template #header>
            <span>Leave Applications</span>
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

        <!-- TOP METRIC CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Stats 1: Status banner -->
            <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-800/80 shadow flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Leave Status</span>
                    <h3 class="text-xl font-extrabold text-white tracking-tight mt-2">Active Session</h3>
                    <p class="text-[10px] text-slate-500 mt-1 font-medium">Allowances set per year</p>
                </div>
                <div class="p-3 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 shadow">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>

            <!-- Stats 2: Approved Applications -->
            <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-800/80 shadow flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Leaves Approved</span>
                    <h3 class="text-2xl font-extrabold text-emerald-400 tracking-tight mt-2">{{ approvedCount }}</h3>
                    <p class="text-[10px] text-slate-500 mt-1 font-medium">Record updated instantly</p>
                </div>
                <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                    </svg>
                </div>
            </div>

            <!-- Stats 3: Awaiting Review -->
            <div v-if="isAdmin || isManager" class="p-6 rounded-2xl bg-slate-900/40 border border-slate-800/80 shadow flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Awaiting Approvals</span>
                    <h3 class="text-2xl font-extrabold text-amber-400 tracking-tight mt-2">{{ pendingCount }}</h3>
                    <p class="text-[10px] text-slate-500 mt-1 font-medium">Requires decision</p>
                </div>
                <div class="p-3 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 shadow">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- MENU AND TABS CONTROLS -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 border-b border-slate-800/80 pb-4">
            <!-- Tabs segment -->
            <div class="flex items-center gap-1 bg-slate-900/60 p-1 rounded-xl border border-slate-800/80 shrink-0 w-max">
                <button
                    v-if="!isAdmin && !isManager"
                    @click="activeTab = 'my-leaves'"
                    class="px-4 py-2 rounded-lg text-xs font-semibold tracking-wide transition-all"
                    :class="activeTab === 'my-leaves' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
                >
                    My Leave Requests
                </button>
                <button
                    v-if="isManager"
                    @click="activeTab = 'team-leaves'"
                    class="px-4 py-2 rounded-lg text-xs font-semibold tracking-wide transition-all"
                    :class="activeTab === 'team-leaves' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
                >
                    Subordinates Applications
                </button>
                <button
                    v-if="isAdmin"
                    @click="activeTab = 'all-leaves'"
                    class="px-4 py-2 rounded-lg text-xs font-semibold tracking-wide transition-all"
                    :class="activeTab === 'all-leaves' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
                >
                    Company Directory
                </button>
            </div>

            <!-- Apply Button -->
            <button 
                v-if="!isAdmin && !isManager"
                @click="openApplyModal"
                class="shrink-0 flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 active:scale-[0.98] text-white rounded-xl text-xs font-bold tracking-wide transition-all shadow shadow-indigo-500/20"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Apply for Leave
            </button>
        </div>

        <!-- FILTERS BAR -->
        <div class="mb-6 p-4 rounded-xl bg-slate-900/40 border border-slate-800/80 flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
            <div class="flex flex-wrap items-center gap-4">
                <!-- 1. Search by Employee Name (visible only for Manager & Admin tabs) -->
                <div v-if="activeTab !== 'my-leaves'" class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Search:</span>
                    <input
                        type="text"
                        v-model="searchEmployeeName"
                        placeholder="Employee Name..."
                        class="px-3 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs w-44"
                    />
                </div>

                <!-- 2. Filter by Date Range -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dates:</span>
                    <VueDatePicker
                        v-model="filterFromDate"
                        model-type="yyyy-MM-dd"
                        dark
                        auto-apply
                        :enable-time-picker="false"
                        placeholder="From Date"
                        input-class-name="!px-3 !py-1.5 !bg-slate-950 !border !border-slate-800 !rounded-lg !text-white focus:!outline-none focus:!ring-1 focus:!ring-indigo-500/50 focus:!border-indigo-500 !transition-all !text-xs !w-32"
                    />
                    <span class="text-slate-600 text-xs">to</span>
                    <VueDatePicker
                        v-model="filterToDate"
                        model-type="yyyy-MM-dd"
                        dark
                        auto-apply
                        :enable-time-picker="false"
                        placeholder="To Date"
                        input-class-name="!px-3 !py-1.5 !bg-slate-950 !border !border-slate-800 !rounded-lg !text-white focus:!outline-none focus:!ring-1 focus:!ring-indigo-500/50 focus:!border-indigo-500 !transition-all !text-xs !w-32"
                    />
                </div>

                <!-- 3. Filter by Status -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status:</span>
                    <div class="relative">
                        <select
                            v-model="filterStatus"
                            class="block px-3 py-1.5 pr-8 bg-slate-950 border border-slate-800 rounded-lg text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs appearance-none cursor-pointer w-28"
                        >
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Reset Button -->
                <button
                    v-if="filterFromDate || filterToDate || filterStatus || searchEmployeeName"
                    @click="filterFromDate = ''; filterToDate = ''; filterStatus = ''; searchEmployeeName = ''"
                    class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1 cursor-pointer active:scale-[0.98]"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Clear Filters
                </button>
            </div>
            
            <span class="text-xs font-medium text-slate-500 shrink-0">
                Showing {{ filteredApplications.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }} - {{ Math.min(currentPage * itemsPerPage, filteredApplications.length) }} of {{ filteredApplications.length }} requests
            </span>
        </div>

        <!-- 1. MY APPLICATIONS TABLE -->
        <div v-if="activeTab === 'my-leaves'" class="p-6 rounded-2xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800/60 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                            <th class="py-3 pr-4">Leave Type</th>
                            <th class="py-3 px-4">Period</th>
                            <th class="py-3 px-4 text-center">Total Length</th>
                            <th class="py-3 px-4">Reason</th>
                            <th class="py-3 px-4">Attachment</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 pl-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/30 text-sm">
                        <tr v-if="filteredApplications.length === 0">
                            <td colspan="7" class="py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <svg class="w-12 h-12 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="font-medium text-slate-400">
                                        {{ filterFromDate || filterToDate ? 'No leave requests found matching your date range filter.' : 'You haven\'t requested any leaves yet. Click "Apply for Leave" to submit.' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <tr 
                            v-else 
                            v-for="leave in paginatedApplications" 
                            :key="'my-' + leave.id" 
                            class="text-slate-300 hover:bg-slate-800/10 transition-colors"
                        >
                            <!-- Leave Type -->
                            <td class="py-4 pr-4">
                                <span class="font-bold text-white">{{ leave.leave_type?.name }}</span>
                            </td>

                            <!-- Period -->
                            <td class="py-4 px-4 text-slate-400 whitespace-nowrap">
                                <span class="text-xs">{{ leave.from_date.substring(0, 10) }} to {{ leave.to_date.substring(0, 10) }}</span>
                            </td>

                            <!-- Length -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-lg text-xs font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    {{ leave.total_days }} {{ leave.total_days > 1 ? 'Days' : 'Day' }}
                                </span>
                            </td>

                            <!-- Reason -->
                            <td class="py-4 px-4 text-slate-400 max-w-xs truncate" :title="leave.reason">
                                {{ leave.reason }}
                            </td>

                            <!-- Attachment -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <a 
                                    v-if="leave.attachment_path" 
                                    :href="'/storage/' + leave.attachment_path" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-xs text-indigo-400 hover:text-indigo-300 font-semibold"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    View file
                                </a>
                                <span v-else class="text-slate-600 text-xs">None</span>
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider" :class="getStatusClasses(leave.status)">
                                    {{ leave.status }}
                                </span>
                                <!-- Approver subtitle -->
                                <span v-if="leave.status !== 'Pending' && leave.approver" class="block text-[9px] text-slate-500 mt-1 uppercase font-bold tracking-wider">
                                    By {{ leave.approver.name }}
                                </span>
                            </td>

                            <!-- Actions (Visible ONLY for Pending status) -->
                            <td class="py-4 pl-4 text-right whitespace-nowrap">
                                <div v-if="leave.status === 'Pending'" class="flex items-center justify-end gap-2.5">
                                    <!-- Edit -->
                                    <button 
                                        @click="openEditModal(leave)"
                                        class="p-2 rounded-xl text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-all duration-200 shadow"
                                        title="Edit Request"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    <!-- Delete -->
                                    <button 
                                        @click="openDeleteModal(leave)"
                                        class="p-2 rounded-xl text-rose-400 bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all duration-200 shadow"
                                        title="Cancel Request"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-else class="text-xs text-slate-500 select-none pr-3">
                                    Locked / Processed
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. MANAGER SUBORDINATES VIEW -->
        <div v-if="activeTab === 'team-leaves'" class="p-6 rounded-2xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800/60 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                            <th class="py-3 pr-4">Employee</th>
                            <th class="py-3 px-4">Leave Type</th>
                            <th class="py-3 px-4">Period</th>
                            <th class="py-3 px-4 text-center">Total Length</th>
                            <th class="py-3 px-4">Reason</th>
                            <th class="py-3 px-4">Attachment</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 pl-4 text-right">Approvals / Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/30 text-sm">
                        <tr v-if="filteredApplications.length === 0">
                            <td colspan="8" class="py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <svg class="w-12 h-12 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span class="font-medium text-slate-400">
                                        {{ filterFromDate || filterToDate ? 'No subordinate leave requests found matching your date range filter.' : 'No leave requests from your subordinates found.' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <tr 
                            v-else 
                            v-for="leave in paginatedApplications" 
                            :key="'team-' + leave.id" 
                            class="text-slate-300 hover:bg-slate-800/10 transition-colors"
                        >
                            <!-- Employee Name -->
                            <td class="py-4 pr-4">
                                <span class="font-bold text-white">{{ leave.user?.name }}</span>
                                <span class="block text-[10px] text-slate-500 truncate mt-0.5">{{ leave.user?.email }}</span>
                            </td>

                            <!-- Leave Type -->
                            <td class="py-4 px-4 font-semibold text-slate-200">
                                {{ leave.leave_type?.name }}
                            </td>

                            <!-- Period -->
                            <td class="py-4 px-4 text-slate-400 whitespace-nowrap">
                                <span class="text-xs">{{ leave.from_date.substring(0, 10) }} to {{ leave.to_date.substring(0, 10) }}</span>
                            </td>

                            <!-- Length -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-lg text-xs font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    {{ leave.total_days }} {{ leave.total_days > 1 ? 'Days' : 'Day' }}
                                </span>
                            </td>

                            <!-- Reason -->
                            <td class="py-4 px-4 text-slate-400 max-w-xs truncate" :title="leave.reason">
                                {{ leave.reason }}
                            </td>

                            <!-- Attachment -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <a 
                                    v-if="leave.attachment_path" 
                                    :href="'/storage/' + leave.attachment_path" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-xs text-indigo-400 hover:text-indigo-300 font-semibold"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    View file
                                </a>
                                <span v-else class="text-slate-600 text-xs">None</span>
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider" :class="getStatusClasses(leave.status)">
                                    {{ leave.status }}
                                </span>
                                <!-- Rejection alert -->
                                <p v-if="leave.status === 'Rejected' && leave.rejection_reason" class="text-[10px] text-rose-500 mt-1 max-w-xs truncate" :title="leave.rejection_reason">
                                    Reason: {{ leave.rejection_reason }}
                                </p>
                            </td>

                            <!-- Actions (Approve/Reject triggers for Managers) -->
                            <td class="py-4 pl-4 text-right whitespace-nowrap">
                                <div v-if="leave.status === 'Pending'" class="flex items-center justify-end gap-2">
                                    <!-- Approve -->
                                    <button 
                                        @click="approveLeave(leave)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white transition-all duration-200"
                                        title="Approve Request"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Approve
                                    </button>

                                    <!-- Reject -->
                                    <button 
                                        @click="openRejectModal(leave)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all duration-200"
                                        title="Reject Request"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Reject
                                    </button>
                                </div>
                                <div v-else class="text-xs text-slate-500 select-none pr-3">
                                    Processed
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. ADMIN COMPANY DIRECTORY VIEW -->
        <div v-if="activeTab === 'all-leaves'" class="p-6 rounded-2xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800/60 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                            <th class="py-3 pr-4">Employee</th>
                            <th class="py-3 px-4">Leave Type</th>
                            <th class="py-3 px-4">Period</th>
                            <th class="py-3 px-4 text-center">Total Length</th>
                            <th class="py-3 px-4">Reason</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 pl-4 text-right">Review Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/30 text-sm">
                        <tr v-if="filteredApplications.length === 0">
                            <td colspan="7" class="py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <svg class="w-12 h-12 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span class="font-medium text-slate-400">
                                        {{ filterFromDate || filterToDate ? 'No company leave requests found matching your date range filter.' : 'No company leave applications found.' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <tr 
                            v-else 
                            v-for="leave in paginatedApplications" 
                            :key="'admin-' + leave.id" 
                            class="text-slate-300 hover:bg-slate-800/10 transition-colors"
                        >
                            <!-- Employee Name -->
                            <td class="py-4 pr-4">
                                <span class="font-bold text-white">{{ leave.user?.name }}</span>
                                <span class="block text-[10px] text-slate-500 truncate mt-0.5">{{ leave.user?.email }}</span>
                            </td>

                            <!-- Leave Type -->
                            <td class="py-4 px-4 font-semibold text-slate-200">
                                {{ leave.leave_type?.name }}
                            </td>

                            <!-- Period -->
                            <td class="py-4 px-4 text-slate-400 whitespace-nowrap">
                                <span class="text-xs">{{ leave.from_date.substring(0, 10) }} to {{ leave.to_date.substring(0, 10) }}</span>
                            </td>

                            <!-- Length -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-lg text-xs font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    {{ leave.total_days }} {{ leave.total_days > 1 ? 'Days' : 'Day' }}
                                </span>
                            </td>

                            <!-- Reason -->
                            <td class="py-4 px-4 text-slate-400 max-w-xs truncate" :title="leave.reason">
                                {{ leave.reason }}
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider" :class="getStatusClasses(leave.status)">
                                    {{ leave.status }}
                                </span>
                                <!-- Approver label -->
                                <span v-if="leave.status !== 'Pending' && leave.approver" class="block text-[9px] text-slate-500 mt-1 uppercase font-bold tracking-wider">
                                    By {{ leave.approver.name }}
                                </span>
                            </td>

                            <!-- Admin Actions (Can override/process any pending leaves) -->
                            <td class="py-4 pl-4 text-right whitespace-nowrap">
                                <div v-if="leave.status === 'Pending'" class="flex items-center justify-end gap-2">
                                    <!-- Approve -->
                                    <button 
                                        @click="approveLeave(leave)"
                                        class="p-2 rounded-xl text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white transition-colors duration-200"
                                        title="Approve Request"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    <!-- Reject -->
                                    <button 
                                        @click="openRejectModal(leave)"
                                        class="p-2 rounded-xl text-rose-400 bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-colors duration-200"
                                        title="Reject Request"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-else class="text-xs text-slate-500 select-none pr-3">
                                    Processed
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- GLOBAL PAGINATION CONTROLS -->
        <div v-if="totalPages > 1" class="mt-6 flex items-center justify-between border-t border-slate-800/60 pt-4 relative z-10">
            <span class="text-xs text-slate-500 font-medium">
                Page <span class="text-slate-300 font-bold">{{ currentPage }}</span> of <span class="text-slate-300 font-bold">{{ totalPages }}</span>
            </span>
            <div class="flex items-center gap-2">
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
        </div>

        <!-- APPLY FOR LEAVE MODAL -->
        <div v-if="isApplyModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity p-4">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800/80 p-6 md:p-8 rounded-2xl shadow-2xl space-y-6 relative overflow-hidden">
                <div class="absolute w-[150px] h-[150px] bg-indigo-500/5 rounded-full blur-[40px] -right-10 -top-10"></div>
                
                <div class="flex items-center justify-between relative z-10">
                    <h3 class="text-lg font-bold text-white tracking-tight">Apply for Leave</h3>
                    <button @click="closeApplyModal" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitApply" class="space-y-4 relative z-10">
                    <!-- Leave Type Select -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Leave Type</label>
                        <div class="relative">
                            <select
                                v-model="applyForm.leave_type_id"
                                required
                                class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm appearance-none cursor-pointer"
                            >
                                <option value="" disabled>Select Leave Type</option>
                                <option v-for="type in leaveTypes" :key="type.id" :value="type.id">
                                    {{ type.name }} (Max: {{ type.max_days_per_year }} Days)
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        <p v-if="applyForm.errors.leave_type_id" class="text-rose-500 text-xs font-medium">{{ applyForm.errors.leave_type_id }}</p>
                    </div>

                    <!-- Date range pickers -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">From Date</label>
                            <VueDatePicker
                                v-model="applyForm.from_date"
                                model-type="yyyy-MM-dd"
                                dark
                                auto-apply
                                :enable-time-picker="false"
                                placeholder="Select From Date"
                                input-class-name="!w-full !px-4 !py-3 !bg-slate-950 !border !border-slate-800 !rounded-xl !text-white focus:!outline-none focus:!ring-2 focus:!ring-indigo-500/50 focus:!border-indigo-500 !transition-all !text-sm"
                            />
                            <p v-if="applyForm.errors.from_date" class="text-rose-500 text-xs font-medium">{{ applyForm.errors.from_date }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">To Date</label>
                            <VueDatePicker
                                v-model="applyForm.to_date"
                                model-type="yyyy-MM-dd"
                                dark
                                auto-apply
                                :enable-time-picker="false"
                                placeholder="Select To Date"
                                input-class-name="!w-full !px-4 !py-3 !bg-slate-950 !border !border-slate-800 !rounded-xl !text-white focus:!outline-none focus:!ring-2 focus:!ring-indigo-500/50 focus:!border-indigo-500 !transition-all !text-sm"
                            />
                            <p v-if="applyForm.errors.to_date" class="text-rose-500 text-xs font-medium">{{ applyForm.errors.to_date }}</p>
                        </div>
                    </div>

                    <!-- Read-Only Auto-calculated Days -->
                    <div v-if="applyTotalDays > 0" class="p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-bold flex items-center justify-between shadow-inner">
                        <span>Total Leave Duration:</span>
                        <span class="px-2.5 py-0.5 rounded-md bg-indigo-500 text-white font-extrabold shadow">{{ applyTotalDays }} {{ applyTotalDays > 1 ? 'Days' : 'Day' }}</span>
                    </div>

                    <!-- Reason text area -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Reason</label>
                            <span class="text-[10px] text-slate-500 font-bold" :class="applyForm.reason.length < 10 ? 'text-amber-500' : 'text-slate-400'">
                                {{ applyForm.reason.length }} / 500 characters
                            </span>
                        </div>
                        <textarea
                            v-model="applyForm.reason"
                            required
                            rows="3"
                            placeholder="Explain the reason for your leave request (minimum 10 characters)..."
                            class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm"
                        ></textarea>
                        <p v-if="applyForm.errors.reason" class="text-rose-500 text-xs font-medium">{{ applyForm.errors.reason }}</p>
                    </div>

                    <!-- Attachment Upload -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Attachment <span class="text-slate-500">(Optional)</span></label>
                        <input
                            type="file"
                            @change="handleApplyFileUpload"
                            accept=".pdf,image/jpeg,image/jpg,image/png"
                            class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-indigo-400 file:hover:bg-slate-700 cursor-pointer"
                        />
                        <p class="text-[10px] text-slate-500 font-medium">Supports PDF, JPG, PNG only (Max: 2MB)</p>
                        <p v-if="applyForm.errors.attachment" class="text-rose-500 text-xs font-medium">{{ applyForm.errors.attachment }}</p>
                    </div>

                    <!-- Status Display (Pending, Read-Only) -->
                    <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800/60 flex items-center justify-between text-xs text-slate-400">
                        <span>Application Status:</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 uppercase tracking-wider">Pending (Auto-set)</span>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeApplyModal"
                            class="px-4 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 transition-all text-sm font-semibold"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="applyForm.processing || applyForm.reason.length < 10 || applyForm.reason.length > 500 || applyTotalDays === 0"
                            class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 active:scale-[0.98] disabled:opacity-50 text-white rounded-xl text-sm font-semibold tracking-wide transition-all shadow-lg shadow-indigo-500/20 flex items-center gap-2"
                        >
                            <svg v-if="applyForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- EDIT LEAVE MODAL -->
        <div v-if="isEditModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity p-4">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800/80 p-6 md:p-8 rounded-2xl shadow-2xl space-y-6 relative overflow-hidden">
                <div class="absolute w-[150px] h-[150px] bg-indigo-500/5 rounded-full blur-[40px] -right-10 -top-10"></div>
                
                <div class="flex items-center justify-between relative z-10">
                    <h3 class="text-lg font-bold text-white tracking-tight">Edit Leave Request</h3>
                    <button @click="closeEditModal" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4 relative z-10">
                    <!-- Leave Type Select -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Leave Type</label>
                        <div class="relative">
                            <select
                                v-model="editForm.leave_type_id"
                                required
                                class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm appearance-none cursor-pointer"
                            >
                                <option v-for="type in leaveTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        <p v-if="editForm.errors.leave_type_id" class="text-rose-500 text-xs font-medium mt-1">{{ editForm.errors.leave_type_id }}</p>
                    </div>

                    <!-- Date pickers -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">From Date</label>
                            <VueDatePicker
                                v-model="editForm.from_date"
                                model-type="yyyy-MM-dd"
                                dark
                                auto-apply
                                :enable-time-picker="false"
                                placeholder="Select From Date"
                                input-class-name="!w-full !px-4 !py-3 !bg-slate-950 !border !border-slate-800 !rounded-xl !text-white focus:!outline-none focus:!ring-2 focus:!ring-indigo-500/50 focus:!border-indigo-500 !transition-all !text-sm"
                            />
                            <p v-if="editForm.errors.from_date" class="text-rose-500 text-xs font-medium mt-1">{{ editForm.errors.from_date }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">To Date</label>
                            <VueDatePicker
                                v-model="editForm.to_date"
                                model-type="yyyy-MM-dd"
                                dark
                                auto-apply
                                :enable-time-picker="false"
                                placeholder="Select To Date"
                                input-class-name="!w-full !px-4 !py-3 !bg-slate-950 !border !border-slate-800 !rounded-xl !text-white focus:!outline-none focus:!ring-2 focus:!ring-indigo-500/50 focus:!border-indigo-500 !transition-all !text-sm"
                            />
                            <p v-if="editForm.errors.to_date" class="text-rose-500 text-xs font-medium mt-1">{{ editForm.errors.to_date }}</p>
                        </div>
                    </div>

                    <!-- Total Days -->
                    <div v-if="editTotalDays > 0" class="p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-bold flex items-center justify-between">
                        <span>Total Leave Duration:</span>
                        <span class="px-2.5 py-0.5 rounded-md bg-indigo-500 text-white font-extrabold">{{ editTotalDays }} {{ editTotalDays > 1 ? 'Days' : 'Day' }}</span>
                    </div>

                    <!-- Reason -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Reason</label>
                            <span class="text-[10px] text-slate-500 font-bold">
                                {{ editForm.reason.length }} / 500 characters
                            </span>
                        </div>
                        <textarea
                            v-model="editForm.reason"
                            required
                            rows="3"
                            class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm"
                        ></textarea>
                        <p v-if="editForm.errors.reason" class="text-rose-500 text-xs font-medium mt-1">{{ editForm.errors.reason }}</p>
                    </div>

                    <!-- Attachment Upload -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Replace Attachment <span class="text-slate-500">(Optional)</span></label>
                        <input
                            type="file"
                            @change="handleEditFileUpload"
                            accept=".pdf,image/jpeg,image/jpg,image/png"
                            class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-indigo-400 file:hover:bg-slate-700 cursor-pointer"
                        />
                        <p class="text-[10px] text-slate-500 mt-1 font-medium">Leave empty to keep existing file</p>
                        <p v-if="editForm.errors.attachment" class="text-rose-500 text-xs font-medium mt-1">{{ editForm.errors.attachment }}</p>
                    </div>

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
                            :disabled="editForm.processing || editForm.reason.length < 10 || editForm.reason.length > 500 || editTotalDays === 0"
                            class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 active:scale-[0.98] text-white rounded-xl text-sm font-semibold tracking-wide transition-all shadow-lg flex items-center gap-2"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- REJECT MODAL WITH REASON -->
        <div v-if="isRejectModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity p-4">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800/80 p-6 md:p-8 rounded-2xl shadow-2xl space-y-6 relative overflow-hidden">
                <div class="absolute w-[150px] h-[150px] bg-rose-500/5 rounded-full blur-[40px] -right-10 -top-10"></div>
                
                <div class="flex items-center justify-between relative z-10">
                    <h3 class="text-lg font-bold text-white tracking-tight">Reject Leave Request</h3>
                    <button @click="closeRejectModal" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitReject" class="space-y-4 relative z-10">
                    <div class="p-4 rounded-xl bg-slate-950/40 border border-slate-800/60 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-400 font-bold flex items-center justify-center shrink-0">
                            L
                        </div>
                        <div class="overflow-hidden">
                            <h4 class="text-sm font-semibold text-white truncate">Leave Request by {{ selectedLeave?.user?.name }}</h4>
                            <p class="text-xs text-slate-500 truncate mt-0.5">{{ selectedLeave?.leave_type?.name }} • {{ selectedLeave?.total_days }} days</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="reject_reason" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Specify Rejection Reason</label>
                        <textarea
                            id="reject_reason"
                            v-model="rejectForm.rejection_reason"
                            required
                            rows="3"
                            placeholder="Explain the reason for rejecting this application (required)..."
                            class="block w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-rose-500/50 focus:border-rose-500 transition-all text-sm shadow-inner"
                        ></textarea>
                        <p v-if="rejectForm.errors.rejection_reason" class="text-rose-500 text-xs font-medium">{{ rejectForm.errors.rejection_reason }}</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeRejectModal"
                            class="px-4 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 transition-all text-sm font-semibold"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="rejectForm.processing || rejectForm.rejection_reason.length < 5"
                            class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 active:scale-[0.98] text-white rounded-xl text-sm font-semibold tracking-wide transition-all shadow-lg shadow-rose-500/25"
                        >
                            Confirm Rejection
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
                    <h3 class="text-lg font-bold text-white tracking-tight">Cancel Leave Request</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Are you sure you want to withdraw your leave request for <span class="text-white font-semibold">"{{ selectedLeave?.leave_type?.name }}"</span>? 
                        This action cannot be undone.
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
                        Withdraw Request
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
