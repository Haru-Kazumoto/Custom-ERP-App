<template>
    <div
        class="hidden md:flex flex-col flex-1 relative overflow-hidden bg-[#f0f9ff]"
    >
        <!-- Grid texture -->
        <div
            aria-hidden="true"
            class="absolute inset-0"
            style="
                background-image:
                    linear-gradient(
                        rgba(14, 165, 233, 0.06) 1px,
                        transparent 1px
                    ),
                    linear-gradient(
                        90deg,
                        rgba(14, 165, 233, 0.06) 1px,
                        transparent 1px
                    );
                background-size: 28px 28px;
            "
        />

        <!-- Decorative circles -->
        <div
            aria-hidden="true"
            class="absolute -top-16 -right-16 w-56 h-56 rounded-full bg-primary/8"
        />
        <div
            aria-hidden="true"
            class="absolute -bottom-12 -left-12 w-44 h-44 rounded-full bg-sky-400/10"
        />

        <!-- Content -->
        <div
            class="relative z-10 flex flex-col items-center justify-center h-full gap-6 px-8 py-10"
        >
            <!-- Dashboard mini-illustration -->
            <DashboardIllustration />

            <!-- Tagline -->
            <div class="text-center">
                <p class="text-sm font-semibold text-sky-900 leading-snug">
                    Unified Sales Platform
                </p>
                <p
                    class="text-xs text-sky-600/80 mt-1 leading-relaxed max-w-[220px] mx-auto"
                >
                    Manage leads, pipeline, orders, and reporting — all in one
                    place.
                </p>
            </div>

            <!-- <Separator class="bg-sky-200/60 w-3/4" /> -->
            

            <!-- Feature pills -->
            <div class="flex flex-wrap gap-2 justify-center">
                <span class="pill">
                    <svg
                        class="w-3 h-3"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.2"
                    >
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 00-3-3.87" />
                        <path d="M16 3.13a4 4 0 010 7.75" />
                    </svg>
                    Customer Management
                </span>
                <span class="pill">
                    <svg
                        class="w-3 h-3"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.2"
                    >
                        <line x1="12" y1="1" x2="12" y2="23" />
                        <path
                            d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"
                        />
                    </svg>
                    Sales Pipeline
                </span>
                <span class="pill">
                    <svg
                        class="w-3 h-3"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.2"
                    >
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                    </svg>
                    Analytics & Reports
                </span>
                <span class="pill">
                    <svg
                        class="w-3 h-3"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.2"
                    >
                        <path
                            d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"
                        />
                        <polyline points="14 2 14 8 20 8" />
                    </svg>
                    Quotation & Invoicing
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import DashboardIllustration from "@/Components/icons/DashboardIllustration.vue";
</script>

<style scoped>

/* -----------------------------------------------------------------------
   Feature pills
----------------------------------------------------------------------- */
.pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 9999px;
    background: rgba(2, 132, 199, 0.08);
    color: #0369a1;
    border: 0.5px solid rgba(2, 132, 199, 0.2);
    line-height: 1;
}

/* -----------------------------------------------------------------------
   Right panel subtle blob colors
----------------------------------------------------------------------- */
.bg-primary\/8 {
    background-color: rgba(2, 132, 199, 0.08);
}
.bg-sky-400\/10 {
    background-color: rgba(56, 189, 248, 0.1);
}
</style>
