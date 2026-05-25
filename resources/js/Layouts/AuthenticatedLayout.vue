<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Get user initials for avatar
const userInitials = computed(() => {
    if (!user.value || !user.value.name) return 'U';
    return user.value.name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

// Detect user role to show on the profile card (Spatie role or fallback)
const userRole = computed(() => {
    // Spatie roles are usually shared globally via ShareErrorsAndFlash / HandleInertiaRequests middleware
    // If not shared, we fallback to Admin/Employee based on email or default to Employee
    if (user.value.roles && user.value.roles.length > 0) {
        return user.value.roles[0].name;
    }
    return user.value.email === 'admin@test.com' ? 'Admin' : 'Employee';
});

const isMobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

// Menu Items configuration
const menuItems = [
    {
        name: 'Dashboard',
        href: '/dashboard',
        icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z',
    },
    {
        name: 'Users',
        href: '/users',
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    },
    {
        name: 'Roles',
        href: '/roles',
        icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    },
    {
        name: 'Leave Types',
        href: '/leave-types',
        icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
    },
    {
        name: 'Leave Applications',
        href: '/leaves',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
];

const isAdmin = computed(() => {
    return userRole.value === 'Admin';
});

const filteredMenuItems = computed(() => {
    return menuItems.filter(item => {
        if (['Users', 'Roles', 'Leave Types'].includes(item.name)) {
            return isAdmin.value;
        }
        return true;
    });
});

// Helper to check if a menu item is active
const isItemActive = (href) => {
    // Extract current path from Inertia
    const currentPath = page.url;
    if (href === '/dashboard') {
        return currentPath === '/dashboard';
    }
    return currentPath.startsWith(href);
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex font-sans overflow-x-hidden">
        <!-- BACKGROUND GLOW EFFECTS -->
        <div class="absolute w-[500px] h-[500px] bg-indigo-500/5 rounded-full blur-[150px] top-10 left-10 pointer-events-none z-0"></div>
        <div class="absolute w-[500px] h-[500px] bg-purple-500/5 rounded-full blur-[150px] bottom-10 right-10 pointer-events-none z-0"></div>

        <!-- 1. SIDEBAR FOR DESKTOP (Fixed left side) -->
        <aside class="hidden lg:flex flex-col w-64 bg-slate-900/60 backdrop-blur-2xl border-r border-slate-800/80 min-h-screen fixed left-0 top-0 z-20">
            <!-- Sidebar Header / Logo -->
            <div class="h-16 px-6 border-b border-slate-800/80 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/25">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="font-extrabold text-white tracking-tight">Leave System</span>
            </div>

            <!-- User Profile Quick Card -->
            <div class="p-4 mx-3 my-4 rounded-xl bg-slate-950/50 border border-slate-800/50 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-tr from-indigo-500/20 to-purple-500/20 border border-indigo-500/30 flex items-center justify-center text-sm font-bold text-indigo-400">
                    {{ userInitials }}
                </div>
                <div class="overflow-hidden">
                    <h4 class="text-sm font-semibold text-white truncate">{{ user.name }}</h4>
                    <span class="inline-block text-[10px] font-bold px-2 py-0.5 mt-0.5 rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase tracking-wider">
                        {{ userRole }}
                    </span>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="flex-1 px-3 space-y-1">
                <Link
                    v-for="item in filteredMenuItems"
                    :key="item.name"
                    :href="item.href"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
                    :class="[
                        isItemActive(item.href)
                            ? 'bg-gradient-to-r from-indigo-600/20 to-purple-600/20 border-l-2 border-indigo-500 text-indigo-400 font-semibold'
                            : 'text-slate-400 hover:bg-slate-800/40 hover:text-white border-l-2 border-transparent'
                    ]"
                >
                    <svg class="w-5 h-5 transition-transform group-hover:scale-105" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <!-- Bottom Actions / Logout -->
            <div class="p-4 border-t border-slate-800/80">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-rose-400 hover:bg-rose-500/10 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </Link>
            </div>
        </aside>

        <!-- 2. MOBILE MENU DRAWER (Slide over drawer) -->
        <div v-if="isMobileMenuOpen" class="lg:hidden fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-30 transition-opacity" @click="toggleMobileMenu"></div>
        <aside
            class="lg:hidden fixed top-0 bottom-0 left-0 w-64 bg-slate-900 border-r border-slate-800 z-40 transform transition-transform duration-300 flex flex-col"
            :class="isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="h-16 px-6 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="font-extrabold text-white tracking-tight text-sm">Leave System</span>
                </div>
                <button @click="toggleMobileMenu" class="text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-4 border-b border-slate-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-sm font-bold text-indigo-400">
                    {{ userInitials }}
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white">{{ user.name }}</h4>
                    <span class="inline-block text-[10px] font-bold px-2 py-0.5 mt-0.5 rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase tracking-wider">
                        {{ userRole }}
                    </span>
                </div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in filteredMenuItems"
                    :key="item.name"
                    :href="item.href"
                    @click="isMobileMenuOpen = false"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
                    :class="[
                        isItemActive(item.href)
                            ? 'bg-gradient-to-r from-indigo-600/20 to-purple-600/20 border-l-2 border-indigo-500 text-indigo-400'
                            : 'text-slate-400 hover:bg-slate-800 hover:text-white border-l-2 border-transparent'
                    ]"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <div class="p-4 border-t border-slate-800">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-rose-400 hover:bg-rose-500/10 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </Link>
            </div>
        </aside>

        <!-- 3. MAIN CONTENT CONTAINER -->
        <div class="flex-1 lg:pl-64 flex flex-col min-h-screen relative z-10">
            <!-- Header/Top Nav -->
            <header class="h-16 border-b border-slate-800/80 bg-slate-900/40 backdrop-blur-xl flex items-center justify-between px-6 sticky top-0 z-20">
                <div class="flex items-center gap-4">
                    <!-- Hamburger button for mobile -->
                    <button @click="toggleMobileMenu" class="lg:hidden text-slate-400 hover:text-white focus:outline-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <!-- Page title from template slot -->
                    <h1 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                        <slot name="header" />
                    </h1>
                </div>

                <!-- Right header elements -->
                <div class="flex items-center gap-4">
                    <span class="hidden sm:inline-block text-xs font-semibold text-slate-400 py-1 px-3 rounded-full bg-slate-800/60 border border-slate-700/50">
                        Portal active
                    </span>
                </div>
            </header>

            <!-- Page Main Content Slot -->
            <main class="flex-1 p-6 md:p-8 w-full max-w-7xl mx-auto">
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
/* Clean global scrollbar overrides for a unified premium theme */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: #020617;
}
::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
</style>
