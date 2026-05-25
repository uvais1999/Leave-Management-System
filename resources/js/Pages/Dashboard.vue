<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Array, required: true },
    recentApplications: { type: Array, required: true },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// Dynamic check for user persona/role
const isAdmin = computed(() => {
    return user.value.email === 'admin@test.com' || 
           (user.value.roles && user.value.roles.some(r => r.name === 'Admin'));
});

const isManager = computed(() => {
    return user.value.roles && user.value.roles.some(r => r.name === 'Manager');
});

// Helper for status classes
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
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <span>Dashboard</span>
        </template>

        <!-- WELCOME SECTION -->
        <div class="mb-8 p-6 rounded-2xl bg-gradient-to-r from-indigo-950/40 via-purple-950/20 to-slate-900/40 border border-slate-800/80 relative overflow-hidden">
            <div class="absolute w-[200px] h-[200px] bg-indigo-500/10 rounded-full blur-[80px] -right-10 -top-10"></div>
            <div class="relative z-10 space-y-2">
                <h2 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">
                    Welcome back, <span class="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">{{ user.name }}</span>!
                </h2>
                <p class="text-slate-400 text-sm max-w-xl leading-relaxed">
                    {{ isAdmin ? 'You are logged in as Administrator. You have full access to manage employees, edit user roles, configure leave types, and review company-wide leave applications.' : 
                       isManager ? 'You are logged in as Manager. You can easily track the leave history of your direct reports, approve or reject applications, and view your team calendar.' : 
                       'Manage your leaves, view your remaining allowances, and submit new leave requests directly to your supervisor.' }}
                </p>
            </div>
        </div>

        <!-- STATS GRID -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div 
                v-for="stat in stats" 
                :key="stat.name"
                class="p-6 rounded-2xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 hover:border-slate-700/50 transition-all duration-300 shadow-xl flex flex-col justify-between"
            >
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ stat.name }}</span>
                    <!-- Icon container -->
                    <div 
                        class="p-2.5 rounded-xl border"
                        :class="[
                            stat.color === 'indigo' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' : '',
                            stat.color === 'amber' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '',
                            stat.color === 'purple' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : '',
                            stat.color === 'pink' ? 'bg-pink-500/10 text-pink-400 border-pink-500/20' : '',
                            stat.color === 'emerald' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : ''
                        ]"
                    >
                        <!-- Icons -->
                        <svg v-if="stat.icon === 'users'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg v-else-if="stat.icon === 'clock'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else-if="stat.icon === 'calendar'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <svg v-else-if="stat.icon === 'tag'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <svg v-else-if="stat.icon === 'balance' || stat.icon === 'progress'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" />
                        </svg>
                        <svg v-else-if="stat.icon === 'check'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-extrabold text-white tracking-tight leading-none mb-2">{{ stat.value }}</h3>
                    <p class="text-xs text-slate-500 font-medium">{{ stat.change }}</p>
                </div>
            </div>
        </div>

        <!-- QUICK ACTIONS & TABLE ROW -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Leave Applications (Col span 2) -->
            <div class="lg:col-span-2 p-6 rounded-2xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-white tracking-tight">Recent Leave Applications</h3>
                        <p class="text-slate-400 text-xs mt-0.5">Summary of leave requests within the department</p>
                    </div>
                    <Link href="/leaves" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                        View all
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800/60 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                                <th class="py-3 pr-4">Employee</th>
                                <th class="py-3 px-4">Leave Type</th>
                                <th class="py-3 px-4">Duration</th>
                                <th class="py-3 pl-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40 text-sm">
                            <tr v-for="app in recentApplications" :key="app.id" class="text-slate-300 hover:bg-slate-800/10 transition-colors">
                                <td class="py-3.5 pr-4 font-medium text-white">{{ app.employee }}</td>
                                <td class="py-3.5 px-4 text-slate-400">{{ app.type }}</td>
                                <td class="py-3.5 px-4 text-xs text-slate-400">{{ app.duration }}</td>
                                <td class="py-3.5 pl-4 text-right">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider" :class="getStatusClasses(app.status)">
                                        {{ app.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions Panel (Col span 1) -->
            <div class="p-6 rounded-2xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-xl flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-bold text-white tracking-tight mb-1">Quick Actions</h3>
                    <p class="text-slate-400 text-xs mb-6">Common workflow tasks and portal triggers</p>

                    <div class="space-y-3">
                        <Link 
                            href="/leaves" 
                            class="w-full flex items-center justify-between p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 hover:border-indigo-500/30 text-left transition-all hover:translate-x-0.5 group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400 group-hover:bg-indigo-500/20 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-semibold text-white">Apply for Leave</span>
                                    <span class="block text-[10px] text-slate-500 mt-0.5">Submit new leave request</span>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-500 group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>

                        <Link 
                            v-if="isAdmin || isManager"
                            href="/leaves" 
                            class="w-full flex items-center justify-between p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 hover:border-purple-500/30 text-left transition-all hover:translate-x-0.5 group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-purple-500/10 text-purple-400 group-hover:bg-purple-500/20 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-semibold text-white">Review Approvals</span>
                                    <span class="block text-[10px] text-slate-500 mt-0.5">Approve or reject leave applications</span>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-500 group-hover:text-purple-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>

                        <Link 
                            v-if="isAdmin"
                            href="/leave-types" 
                            class="w-full flex items-center justify-between p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 hover:border-pink-500/30 text-left transition-all hover:translate-x-0.5 group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-pink-500/10 text-pink-400 group-hover:bg-pink-500/20 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-semibold text-white">Leave Settings</span>
                                    <span class="block text-[10px] text-slate-500 mt-0.5">Manage leave allowances & types</span>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-500 group-hover:text-pink-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Footer support card -->
                <div class="mt-6 p-4 rounded-xl bg-slate-950/40 border border-slate-800/60 text-center">
                    <p class="text-xs text-slate-500 leading-normal">
                        Need assistance or have feedback about the new dashboard? 
                        <a href="mailto:support@test.com" class="text-indigo-400 hover:underline">Contact HR Support</a>
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
