<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import {
    Menu,
    Search,
    Bell,
    Check,
    CircleAlert,
    CalendarClock,
    MessageSquare,
    FileText,
} from "lucide-vue-next";

defineEmits<{ (e: "toggle-sidebar"): void }>();

const today = new Intl.DateTimeFormat("en-US", {
    weekday: "long",
    day: "numeric",
    month: "short",
    year: "numeric",
}).format(new Date());

interface Notification {
    id: number;
    title: string;
    body: string;
    time: string;
    read: boolean;
    icon: "leave" | "message" | "report" | "alert";
}

// DUMMY — nanti ganti dgn usePage().props.notifications
const notifications = ref<Notification[]>([
    {
        id: 1,
        title: "Leave request approved",
        body: "Your vacation request for 25–28 Oct was approved.",
        time: "5m ago",
        read: false,
        icon: "leave",
    },
    {
        id: 2,
        title: "New message from Mariam",
        body: "Can you review the Q4 portfolio before the meeting?",
        time: "1h ago",
        read: false,
        icon: "message",
    },
    {
        id: 3,
        title: "Monthly report ready",
        body: "October performance report is available to download.",
        time: "3h ago",
        read: false,
        icon: "report",
    },
    {
        id: 4,
        title: "Payroll processed",
        body: "Your salary for October has been transferred.",
        time: "Yesterday",
        read: true,
        icon: "report",
    },
    {
        id: 5,
        title: "System maintenance",
        body: "Scheduled downtime on Sunday 02:00–04:00.",
        time: "2d ago",
        read: true,
        icon: "alert",
    },
]);

const unreadCount = computed(
    () => notifications.value.filter((n) => !n.read).length,
);

const iconMap = {
    leave: CalendarClock,
    message: MessageSquare,
    report: FileText,
    alert: CircleAlert,
};

const open = ref(false);
const root = ref<HTMLElement | null>(null);

function toggle() {
    open.value = !open.value;
}
function markRead(n: Notification) {
    n.read = true;
}
function markAllRead() {
    notifications.value.forEach((n) => (n.read = true));
}

// tutup saat klik di luar
function onClickOutside(e: MouseEvent) {
    if (root.value && !root.value.contains(e.target as Node))
        open.value = false;
}
onMounted(() => document.addEventListener("click", onClickOutside));
onUnmounted(() => document.removeEventListener("click", onClickOutside));
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/80 px-4 backdrop-blur lg:px-6"
    >
        <button
            class="rounded-lg p-2 text-slate-500 hover:bg-sky-50 lg:hidden"
            @click="$emit('toggle-sidebar')"
        >
            <Menu class="h-5 w-5" />
        </button>

        <div class="min-w-0">
            <h1
                class="truncate text-base font-semibold text-slate-800 sm:text-lg"
            >
                Good afternoon, Ahmed!
            </h1>
            <p class="hidden text-xs text-slate-400 sm:block">{{ today }}</p>
        </div>


        <!-- Notifikasi -->
        <div ref="root" class="relative ml-auto">
            <button
                class="relative rounded-lg p-2 text-slate-500 hover:bg-sky-50"
                :class="open && 'bg-sky-50 text-[#0284c7]'"
                @click="toggle"
            >
                <Bell class="h-5 w-5" />
                <span
                    v-if="unreadCount"
                    class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-[#0284c7] px-1 text-[10px] font-semibold text-white"
                >
                    {{ unreadCount }}
                </span>
            </button>

            <!-- Panel dropdown -->
            <transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0"
                leave-active-class="transition duration-100 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="open"
                    class="fixed left-1/2 top-16 z-30 w-[calc(100vw-2rem)] max-w-sm -translate-x-1/2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-96 sm:max-w-none sm:translate-x-0 sm:origin-top-right"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-4 py-3"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-slate-800"
                                >Notifications</span
                            >
                            <span
                                v-if="unreadCount"
                                class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-[#0284c7]"
                            >
                                {{ unreadCount }} new
                            </span>
                        </div>
                        <button
                            v-if="unreadCount"
                            class="text-xs font-medium text-[#0284c7] hover:underline"
                            @click="markAllRead"
                        >
                            Mark all read
                        </button>
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        <button
                            v-for="n in notifications"
                            :key="n.id"
                            class="flex w-full gap-3 border-b border-slate-50 px-4 py-3 text-left transition-colors hover:bg-slate-50"
                            :class="!n.read && 'bg-sky-50/40'"
                            @click="markRead(n)"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                :class="
                                    n.read
                                        ? 'bg-slate-100 text-slate-400'
                                        : 'bg-sky-50 text-[#0284c7]'
                                "
                            >
                                <component
                                    :is="iconMap[n.icon]"
                                    class="h-4 w-4"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <p
                                        class="truncate text-sm font-medium text-slate-800"
                                    >
                                        {{ n.title }}
                                    </p>
                                    <span
                                        v-if="!n.read"
                                        class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#0284c7]"
                                    />
                                </div>
                                <p
                                    class="mt-0.5 line-clamp-2 text-xs text-slate-500"
                                >
                                    {{ n.body }}
                                </p>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    {{ n.time }}
                                </p>
                            </div>
                        </button>

                        <div
                            v-if="!notifications.length"
                            class="flex flex-col items-center gap-2 px-4 py-10 text-center"
                        >
                            <Check class="h-6 w-6 text-slate-300" />
                            <p class="text-sm text-slate-400">
                                You're all caught up.
                            </p>
                        </div>
                    </div>

                    <div
                        class="border-t border-slate-100 px-4 py-2.5 text-center"
                    >
                        <button
                            class="text-sm font-medium text-[#0284c7] hover:underline"
                        >
                            View all notifications
                        </button>
                    </div>
                </div>
            </transition>
        </div>

        <div
            class="h-9 w-9 overflow-hidden rounded-full bg-sky-100 ring-2 ring-sky-100"
        >
            <img
                src="https://i.pravatar.cc/80?img=12"
                alt="User"
                class="h-full w-full object-cover"
            />
        </div>
    </header>
</template>
