<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const isSubmitting = ref(false);

const submit = () => {
    isSubmitting.value = true;
    form.post(route('register'), {
        onFinish: () => {
            isSubmitting.value = false;
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Create Account - Sign Up" />

    <div class="min-h-screen w-full bg-slate-950 text-slate-100 flex flex-col md:flex-row font-sans overflow-x-hidden relative">
        <!-- Abstract Background Glows -->
        <div class="absolute w-[500px] h-[500px] bg-indigo-500/10 rounded-full blur-[150px] -top-40 -left-40 pointer-events-none"></div>
        <div class="absolute w-[500px] h-[500px] bg-purple-500/10 rounded-full blur-[150px] -bottom-40 -right-40 pointer-events-none"></div>

        <!-- Left Side: Hero and Features Panel -->
        <div class="hidden md:flex md:w-1/2 relative bg-slate-900/40 backdrop-blur-3xl justify-center items-start flex-col p-16 overflow-hidden border-r border-slate-800/80">
            <!-- Background Radial Glow inside Left Panel -->
            <div class="absolute w-[300px] h-[300px] bg-indigo-600/10 rounded-full blur-[100px] top-1/4 right-1/4 pointer-events-none"></div>
            
            <!-- Logo Section -->
            <div class="flex items-center gap-3 mb-12 relative z-10">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white to-slate-300 bg-clip-text text-transparent">Leave System</span>
            </div>

            <!-- Hero Message -->
            <div class="space-y-4 mb-10 relative z-10">
                <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight">
                    Start Managing Your <br />
                    <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">Leaves Better.</span>
                </h1>
                <p class="text-slate-400 text-base max-w-md leading-relaxed">
                    Set up your workspace account in seconds and unlock simple, automated leave tracking, balances, and easy approvals.
                </p>
            </div>

            <!-- Feature Cards List -->
            <div class="w-full max-w-md space-y-4 relative z-10">
                <!-- Feature 1 -->
                <div class="group flex items-start gap-4 p-4 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-indigo-500/30 transition-all duration-300 hover:translate-x-1">
                    <div class="p-3 rounded-xl bg-indigo-500/10 text-indigo-400 group-hover:bg-indigo-500/20 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Interactive Balance Tracker</h3>
                        <p class="text-xs text-slate-400 mt-1">View accrued, taken, and pending leaves in a polished, unified interface.</p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="group flex items-start gap-4 p-4 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-indigo-500/30 transition-all duration-300 hover:translate-x-1">
                    <div class="p-3 rounded-xl bg-purple-500/10 text-purple-400 group-hover:bg-purple-500/20 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Instant Manager Decisions</h3>
                        <p class="text-xs text-slate-400 mt-1">Submit leaves instantly to managers for fast review and automated notifications.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Register Form -->
        <div class="w-full md:w-1/2 flex justify-center items-center p-6 sm:p-12 md:p-16 relative">
            <div class="w-full max-w-md bg-slate-900/50 backdrop-blur-xl border border-slate-800/80 p-8 sm:p-10 rounded-2xl shadow-2xl relative z-10 transition-all duration-300">
                <!-- Mobile Logo Header -->
                <div class="flex items-center gap-3 mb-8 md:hidden">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-white">Leave System</span>
                </div>

                <div class="space-y-2 mb-8">
                    <h2 class="text-2xl font-extrabold text-white tracking-tight">Create Account</h2>
                    <p class="text-slate-400 text-sm">Join the organization workspace today</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Name Field -->
                    <div class="space-y-2">
                        <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Full Name</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500 group-focus-within:text-indigo-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input
                                id="name"
                                type="text"
                                v-model="form.name"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="Your Name"
                                class="block w-full pl-12 pr-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                            />
                        </div>
                        <p v-if="form.errors.name" class="text-rose-500 text-xs mt-1 font-medium transition-all duration-300">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Email Address</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500 group-focus-within:text-indigo-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                                </svg>
                            </div>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="name@company.com"
                                class="block w-full pl-12 pr-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-rose-500 text-xs mt-1 font-medium transition-all duration-300">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Password</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500 group-focus-within:text-indigo-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input
                                id="password"
                                type="password"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••••••"
                                class="block w-full pl-12 pr-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-rose-500 text-xs mt-1 font-medium transition-all duration-300">{{ form.errors.password }}</p>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="space-y-2">
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Confirm Password</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500 group-focus-within:text-indigo-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <input
                                id="password_confirmation"
                                type="password"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••••••"
                                class="block w-full pl-12 pr-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm shadow-inner"
                            />
                        </div>
                        <p v-if="form.errors.password_confirmation" class="text-rose-500 text-xs mt-1 font-medium transition-all duration-300">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing || isSubmitting"
                        class="w-full relative group py-3.5 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 active:scale-[0.98] disabled:active:scale-100 disabled:opacity-50 text-white rounded-xl text-sm font-bold tracking-wide transition-all shadow-lg shadow-indigo-500/25 flex items-center justify-center overflow-hidden pt-2"
                    >
                        <span v-if="form.processing || isSubmitting" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Creating Account...
                        </span>
                        <span v-else class="flex items-center gap-1.5">
                            Register
                            <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    </button>

                    <!-- Sign In Link -->
                    <div class="text-center pt-2">
                        <p class="text-sm text-slate-500">
                            Already registered? 
                            <Link
                                :href="route('login')"
                                class="font-semibold text-indigo-400 hover:text-indigo-300 transition-colors focus:outline-none"
                            >
                                Sign in
                            </Link>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom autofill formatting for consistency */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
    -webkit-text-fill-color: #fff;
    -webkit-box-shadow: 0 0 0px 1000px #020617 inset;
    transition: background-color 5000s ease-in-out 0s;
}
</style>
