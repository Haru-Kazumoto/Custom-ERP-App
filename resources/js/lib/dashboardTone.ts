/**
 * Palet tone untuk widget Dashboard.
 *
 * Tiap dashboard (Procurement, Marketing, BusinessDevelopment, ...) memakai
 * komponen yang sama: StatCard, CalendarWidget, TaskList, CheckInCard,
 * RevenueChart. Warna brand di tiap widget diambil dari `tone`, bukan
 * hardcode, supaya satu dashboard bisa ganti warna tanpa efek samping ke
 * dashboard lain.
 *
 * `blue` = brand SaaS Sky (#0284c7), default dan sesuai naiveTheme.js.
 * `green` = hijau default Naive UI (#18a058) + hover/pressed bawaannya.
 *
 * Pakai:
 *   const props = withDefaults(defineProps<{ tone?: DashboardTone }>(), { tone: 'blue' });
 *   const t = computed(() => dashboardTone(props.tone));
 */
export type DashboardTone = 'blue' | 'green';

export const dashboardTones = {
    blue: {
        // class Tailwind
        solid: 'bg-[#0284c7]',
        text: 'text-[#0284c7]',
        textSoft: 'text-sky-100',
        softBg: 'bg-sky-50',
        softBgHover: 'hover:bg-sky-50',
        focusRing: 'focus:ring-sky-400',
        // nilai hex untuk canvas/chart (chart.js, dsb)
        hex: '#0284c7',
        gradientTop: 'rgba(2, 132, 199, 0.25)',
        gradientBottom: 'rgba(2, 132, 199, 0)',
    },
    green: {
        solid: 'bg-[#18a058]',
        text: 'text-[#18a058]',
        textSoft: 'text-[#b7e4c7]',
        softBg: 'bg-[#e9f6ee]',
        softBgHover: 'hover:bg-[#e9f6ee]',
        focusRing: 'focus:ring-[#36ad6a]',
        hex: '#18a058',
        gradientTop: 'rgba(24, 160, 88, 0.25)',
        gradientBottom: 'rgba(24, 160, 88, 0)',
    },
} as const satisfies Record<DashboardTone, Record<string, string>>;

export function dashboardTone(tone: DashboardTone = 'blue') {
    return dashboardTones[tone] ?? dashboardTones.blue;
}