<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

const slides = [
    {
        title: "Satu pusat kendali untuk seluruh penjualan",
        desc: "Pantau target, pipeline, dan performa tim sales dalam satu tampilan yang rapi dan mudah dibaca.",
    },
    {
        title: "Keputusan lebih cepat, berbasis data",
        desc: "Lihat produk terlaris dan tren penjualan terbaru agar tim bisa bergerak tanpa menunggu laporan.",
    },
    {
        title: "Target tercapai bersama tim",
        desc: "Setiap sales melihat progres targetnya sendiri, sementara manajer melihat gambaran besarnya.",
    },
];

const active = ref(0);
const slide = computed(() => slides[active.value]);

let timer;
onMounted(() => {
    timer = setInterval(() => {
        active.value = (active.value + 1) % slides.length;
    }, 5000);
});
onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <aside class="showcase relative flex-col flex-none w-1/2 rounded-2xl overflow-hidden text-white p-5">
        <!-- Mockup kartu -->
        <div class="relative flex-1 min-h-0" aria-hidden="true">
            <!-- Sales overview -->
            <div class="glass absolute top-2 left-0 w-[16rem] p-3">
                <div class="flex items-center justify-between text-[11px] text-slate-500">
                    <span class="font-medium text-slate-700">Sales Overview</span>
                    <span>Bulan ini</span>
                </div>
                <div class="flex items-center gap-4 mt-3">
                    <div class="donut" />
                    <div>
                        <p class="text-xl font-semibold text-slate-900">Rp 248,5 jt</p>
                        <p class="text-[11px] text-slate-500">Total penjualan</p>
                        <ul class="mt-2 space-y-1 text-[11px] text-slate-600">
                            <li class="flex items-center gap-1.5">
                                <i class="dot bg-[#18a058]" /> Tercapai
                            </li>
                            <li class="flex items-center gap-1.5">
                                <i class="dot bg-[#f0a020]" /> Dalam proses
                            </li>
                            <li class="flex items-center gap-1.5">
                                <i class="dot bg-[#d03050]" /> Batal
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sales target -->
            <div class="glass absolute top-28 right-0 w-[14rem] p-3">
                <p class="text-[11px] font-medium text-slate-700">Target Kuartal</p>
                <p class="text-lg font-semibold text-slate-900 mt-1">
                    Rp 620 jt
                    <span class="text-[11px] font-normal text-slate-400">/ Rp 800 jt</span>
                </p>
                <div class="h-1.5 rounded-full bg-slate-200 mt-2">
                    <div class="h-full w-[78%] rounded-full bg-[#18a058]" />
                </div>
                <p class="text-[11px] text-slate-500 mt-1.5">Tersisa Rp 180 jt</p>
            </div>

            <!-- Top products -->
            <div class="glass absolute bottom-0 left-6 w-[16rem] p-3">
                <div class="flex items-center justify-between text-[11px]">
                    <span class="font-medium text-slate-700">Produk Terlaris</span>
                    <span class="text-[#18a058]">+12%</span>
                </div>
                <ul class="mt-2 divide-y divide-slate-100 text-[12px]">
                    <li class="flex justify-between py-1.5">
                        <span class="text-slate-700">Paket Starter</span>
                        <span class="font-medium text-slate-900">Rp 42 jt</span>
                    </li>
                    <li class="flex justify-between py-1.5">
                        <span class="text-slate-700">Paket Business</span>
                        <span class="font-medium text-slate-900">Rp 35 jt</span>
                    </li>
                    <li class="flex justify-between py-1.5">
                        <span class="text-slate-700">Layanan Tambahan</span>
                        <span class="font-medium text-slate-900">Rp 18 jt</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Teks promo -->
        <div class="text-center mt-4">
            <Transition name="fade" mode="out-in">
                <div :key="active" class="min-h-[6.5rem]">
                    <h2 class="text-xl font-semibold leading-snug max-w-xs mx-auto">
                        {{ slide.title }}
                    </h2>
                    <p class="text-[13px] text-white/60 mt-2 max-w-sm mx-auto leading-relaxed">
                        {{ slide.desc }}
                    </p>
                </div>
            </Transition>
        </div>

        <!-- Indikator slide -->
        <div class="flex gap-2 mt-3" role="tablist" aria-label="Slide promo">
            <button v-for="(_, i) in slides" :key="i" type="button" role="tab" :aria-selected="i === active"
                :aria-label="`Slide ${i + 1}`" class="h-1 flex-1 rounded-full transition-colors"
                :class="i === active ? 'bg-white' : 'bg-white/25 hover:bg-white/40'" @click="active = i" />
        </div>
    </aside>
</template>

<style scoped>
/* Hijau gelap dari palet default Naive UI (#18a058) + pola grid halus */
.showcase {
    background-color: #0a3524;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px),
        linear-gradient(160deg, #0f5a3a 0%, #072a1b 100%);
    background-size: 32px 32px, 32px 32px, 100% 100%;
}

.glass {
    background: rgba(255, 255, 255, 0.94);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 14px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
    color: #1f2937;
}

.donut {
    width: 4.5rem;
    height: 4.5rem;
    flex: none;
    border-radius: 9999px;
    background: conic-gradient(#18a058 0 62%, #f0a020 62% 88%, #d03050 88% 100%);
    -webkit-mask: radial-gradient(circle, transparent 54%, #000 55%);
    mask: radial-gradient(circle, transparent 54%, #000 55%);
}

.dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 9999px;
    display: inline-block;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {

    .fade-enter-active,
    .fade-leave-active {
        transition: none;
    }
}
</style>
