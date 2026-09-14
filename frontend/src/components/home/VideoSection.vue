<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useVideoStore } from '@/stores/videos'
import type { SocialVideo } from '@/types'

const store = useVideoStore()

/** Ids the visitor has clicked play on — the iframe only mounts after that, so
 *  a homepage with three videos doesn't load three players up front. */
const playing = ref(new Set<number>())

const PLATFORM_META: Record<string, { c: string; b: string }> = {
  youtube: { c: '#C0472F', b: '#F8E6E0' },
  instagram: { c: '#8B3FA8', b: '#F4E9F9' },
}
function platformMeta(platform: string) {
  return PLATFORM_META[platform] ?? { c: '#5B6672', b: '#EEF0F2' }
}

/** Instagram exposes no poster image, so its embed mounts straight away. */
function autoMounts(v: SocialVideo) {
  return !!v.embedUrl && !v.thumbnailUrl
}

function play(v: SocialVideo) {
  playing.value = new Set(playing.value).add(v.id)
}

onMounted(() => store.fetchVideos())
</script>

<template>
  <!-- Nothing renders until the slots arrive: an admin can deactivate all of
       them, and the heading alone would be a dead section. -->
  <section v-if="store.videos.length" class="mx-auto max-w-[1200px] px-6 pt-[54px] pb-5">
    <div v-reveal class="mb-[26px]">
      <div class="text-[13px] font-bold tracking-[.08em] text-gold uppercase">Konten terbaru</div>
      <h2 class="mt-1.5 text-[24px] font-extrabold tracking-[-.02em] mobile:text-[28px] tablet:text-[32px]">
        Video dari medsos NearBy
      </h2>
      <p class="mt-2 max-w-[640px] text-[15px] leading-relaxed text-text-muted">
        Cuplikan UMKM &amp; oleh-oleh khas Balikpapan langsung dari Instagram &amp; YouTube kami.
      </p>
    </div>

    <div class="grid grid-cols-1 gap-5 mobile:grid-cols-2 tablet:grid-cols-3">
      <article v-for="(v, i) in store.videos" :key="v.id" v-reveal="{ delay: i * 60 }">
        <div
          class="relative aspect-video w-full overflow-hidden rounded-[16px] bg-brand-navy shadow-[0_10px_28px_rgba(19,50,77,.16)]"
        >
          <!-- Live embed, once it's been asked for -->
          <iframe
            v-if="v.embedUrl && (playing.has(v.id) || autoMounts(v))"
            :src="playing.has(v.id) && v.thumbnailUrl ? `${v.embedUrl}?autoplay=1` : v.embedUrl"
            :title="v.title"
            class="h-full w-full border-0"
            loading="lazy"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          />

          <!-- Poster + play button (YouTube) -->
          <button
            v-else-if="v.embedUrl"
            type="button"
            class="group absolute inset-0 h-full w-full cursor-pointer"
            :aria-label="`Putar video: ${v.title}`"
            @click="play(v)"
          >
            <img :src="v.thumbnailUrl!" :alt="v.title" class="h-full w-full object-cover" loading="lazy" />
            <span class="absolute inset-0 bg-[rgba(12,28,45,.28)] transition-colors duration-200 group-hover:bg-[rgba(12,28,45,.14)]" />
            <span
              class="absolute top-1/2 left-1/2 flex h-[58px] w-[58px] -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 pl-1 text-[22px] text-brand-navy shadow-[0_8px_24px_rgba(9,24,40,.32)] transition-transform duration-200 group-hover:scale-105"
            >
              ▶
            </span>
          </button>

          <!-- Empty slot — an admin hasn't pasted a link yet -->
          <div v-else class="flex h-full w-full flex-col items-center justify-center px-5 text-center">
            <div class="text-[34px] leading-none">🎬</div>
            <div class="mt-2.5 text-[14.5px] font-extrabold text-white">Video belum tersedia</div>
            <div class="mt-1 text-[12px] leading-relaxed text-[#AFC3DC]">
              Admin dapat menggantinya lewat dashboard.
            </div>
          </div>
        </div>

        <div class="mt-3 flex items-start gap-2.5">
          <span
            class="mt-px flex-none rounded-full px-[11px] py-1 text-[11.5px] font-bold"
            :style="{ background: platformMeta(v.platform).b, color: platformMeta(v.platform).c }"
          >
            {{ v.platformLabel }}
          </span>
          <a
            v-if="v.url"
            :href="v.url"
            target="_blank"
            rel="noopener noreferrer"
            class="text-[14.5px] font-bold leading-snug text-brand-navy hover:text-brand-blue"
          >
            {{ v.title }}
          </a>
          <span v-else class="text-[14.5px] font-bold leading-snug text-brand-navy">{{ v.title }}</span>
        </div>
      </article>
    </div>
  </section>
</template>
