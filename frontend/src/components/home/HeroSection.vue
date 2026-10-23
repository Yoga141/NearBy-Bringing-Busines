<script setup lang="ts">
import { computed, defineAsyncComponent, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUmkmStore } from '@/stores/umkm'
import { CATEGORY_FILTERS, CATEGORY_NAMES, LOCATION_NAMES } from '@/data/categories'
import UmkmThumb from '@/components/shared/UmkmThumb.vue'
import CategoryPill from '@/components/shared/CategoryPill.vue'
import SearchIcon from '@/components/shared/SearchIcon.vue'
import StarIcon from '@/components/shared/StarIcon.vue'

// The 3D backdrop is decoration: loading it (and three.js, ~500 kB) in its own
// chunk lets the hero text and search box render without waiting for it.
const HeroCanvas = defineAsyncComponent(() => import('@/components/home/HeroCanvas.vue'))

const router = useRouter()
const umkm = useUmkmStore()
const heroCat = ref('Semua')

/** Top 2 UMKM with complete data, for the hero's preview cards. */
const heroPreview = computed(() => umkm.featured.slice(0, 2))

function doHeroSearch() {
  umkm.cat = heroCat.value
  umkm.loc = 'Semua'
  router.push({ name: 'daftar' })
}
</script>

<template>
  <section
    class="relative overflow-hidden"
    style="background: linear-gradient(160deg, #16324b 0%, #1e4372 55%, #2c5ead 100%)"
  >
    <div class="pointer-events-none absolute inset-0 z-0">
      <HeroCanvas />
    </div>

    <div
      class="pointer-events-none absolute -top-[120px] -right-20 z-0 h-[420px] w-[420px] rounded-full animate-drift"
      style="background: radial-gradient(circle, rgba(214, 162, 74, 0.35), transparent 70%)"
    />
    <div
      class="pointer-events-none absolute -bottom-[140px] -left-[60px] z-0 h-[360px] w-[360px] rounded-full animate-drift"
      style="background: radial-gradient(circle, rgba(62, 142, 130, 0.4), transparent 70%); animation-delay: -4.5s"
    />

    <div
      class="relative z-10 mx-auto grid max-w-[1200px] grid-cols-1 items-center gap-12 px-6 py-[76px] pb-[88px] tablet:grid-cols-[1.15fr_.85fr]"
    >
      <div class="animate-float-up">
        <div
          class="mb-[22px] inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-[7px] text-[13px] font-semibold text-[#DCE9FB]"
        >
          <span class="h-[7px] w-[7px] rounded-full bg-[#57C785]" /> Direktori UMKM sekitaranmu
        </div>
        <h1 class="m-0 text-[33px] leading-[1.05] font-extrabold tracking-[-.03em] text-white text-balance tablet:text-[52px]">
          Temukan UMKM terbaik<br />
          di sekitaran <span class="text-gold-bright">Balikpapan</span>
        </h1>
        <p class="my-5 max-w-[480px] text-lg leading-relaxed text-[#C6D6EC]">
          Dari kuliner, penginapan, fashion, oleh-oleh hingga jasa semua usaha lokal terdekat, dalam satu tempat.
          <em class="font-semibold text-white not-italic">NearBy Balikpapan</em> yang terdekat, yang terbaik.
        </p>

        <div class="flex max-w-[560px] gap-1.5 rounded-2xl bg-white p-2 shadow-[0_20px_50px_rgba(9,24,40,.35)] mobile:gap-2">
          <div class="flex min-w-0 flex-1 items-center gap-1.5 px-2 mobile:gap-2.5 mobile:px-3">
            <SearchIcon size="16px" class="text-[#A79D89]" />
            <input
              v-model="umkm.q"
              placeholder="Cari UMKM…"
              class="w-full min-w-0 border-none py-3 text-sm font-medium text-brand-navy mobile:text-base"
            />
          </div>
          <select
            v-model="heroCat"
            class="max-w-[84px] cursor-pointer border-l border-[#EEE8DB] px-1.5 text-sm font-semibold text-text-secondary mobile:max-w-none mobile:px-3 mobile:text-base"
          >
            <option v-for="c in CATEGORY_FILTERS" :key="c">{{ c }}</option>
          </select>
          <button
            type="button"
            class="cursor-pointer rounded-[11px] bg-gold px-3.5 py-2.5 text-sm font-bold whitespace-nowrap text-white mobile:px-6 mobile:py-[13px] mobile:text-base"
            @click="doHeroSearch"
          >
            Cari
          </button>
        </div>

        <div class="mt-[34px] flex gap-[26px]">
          <div>
            <div class="text-[26px] font-extrabold text-white">{{ umkm.completeCount }}+</div>
            <div class="text-[13px] font-semibold text-[#9FB4D0]">UMKM terdaftar</div>
          </div>
          <div class="w-px bg-white/15" />
          <div>
            <div class="text-[26px] font-extrabold text-white">{{ CATEGORY_NAMES.length }}</div>
            <div class="text-[13px] font-semibold text-[#9FB4D0]">Kategori</div>
          </div>
          <div class="w-px bg-white/15" />
          <div>
            <div class="text-[26px] font-extrabold text-white">{{ LOCATION_NAMES.length }}</div>
            <div class="text-[13px] font-semibold text-[#9FB4D0]">Kecamatan</div>
          </div>
        </div>
      </div>

      <div class="relative animate-float-up">
        <div class="rounded-3xl border border-white/16 bg-white/8 p-[18px] backdrop-blur-[6px] shadow-[0_20px_45px_rgba(10,24,39,0.35)]">
          <!-- Main Hero Showcase Card -->
          <div class="relative h-[210px] overflow-hidden rounded-2xl shadow-inner">
            <RouterLink
              v-if="heroPreview[0]"
              :to="{ name: 'detail', params: { id: heroPreview[0].id } }"
              class="group relative block h-full w-full"
            >
              <UmkmThumb
                :src="heroPreview[0].photos?.[0]?.url || heroPreview[0].coverUrl"
                :alt="`Foto ${heroPreview[0].name}`"
                rounded="rounded-2xl"
              />
              <!-- Dark gradient overlay for title legibility -->
              <div class="absolute inset-0 bg-gradient-to-t from-[rgba(10,24,39,0.88)] via-[rgba(10,24,39,0.2)] to-transparent" />
              <div class="absolute top-3 left-3 z-10">
                <CategoryPill :category="heroPreview[0].cat" size="sm" />
              </div>
              <div class="absolute bottom-3.5 left-4 right-4 z-10 text-white">
                <div class="truncate text-[16px] font-extrabold transition-colors group-hover:text-gold-bright">
                  {{ heroPreview[0].name }}
                </div>
                <div class="mt-1 flex items-center justify-between text-[12.5px] font-semibold text-[#DCE9FB]">
                  <span>{{ heroPreview[0].loc }}</span>
                  <span class="flex items-center gap-1 font-bold text-gold-bright">
                    <StarIcon size="12px" /> {{ heroPreview[0].rating }}
                  </span>
                </div>
              </div>
            </RouterLink>
            <div v-else class="flex h-full w-full items-center justify-center bg-brand-navy/60 text-sm font-semibold text-white">
              UMKM Sekitaranmu
            </div>
          </div>

          <!-- Bottom 2 Preview Mini Cards -->
          <div v-if="heroPreview.length" class="mt-3.5 grid grid-cols-2 gap-3">
            <RouterLink
              v-for="u in heroPreview"
              :key="u.id"
              :to="{ name: 'detail', params: { id: u.id } }"
              class="group rounded-[16px] bg-white p-3 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
            >
              <div class="h-[80px] overflow-hidden rounded-[10px]">
                <UmkmThumb
                  :src="u.photos?.[0]?.url || u.coverUrl"
                  :alt="`Foto ${u.name}`"
                  rounded="rounded-[10px]"
                />
              </div>
              <div class="mt-2.5 truncate text-[13.5px] font-extrabold text-brand-navy transition-colors group-hover:text-brand-blue">
                {{ u.name }}
              </div>
              <div class="mt-0.5 flex items-center gap-1 text-[12px] font-bold" :style="{ color: u.accent }">
                <StarIcon size="11px" /> {{ u.rating }} · {{ u.cat }}
              </div>
            </RouterLink>
          </div>
          <div v-else class="mt-3 rounded-[14px] bg-white p-4 text-center text-[13px] font-semibold text-text-faint">
            Belum ada UMKM terdaftar.
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
