import type { VideoPlatform } from '@/types'

/**
 * Read a pasted share link: its platform and clean form (no tracking query,
 * no "m."/missing "www"), or an error message saying why it was refused.
 * Mirrors `SocialVideo::parseLink` on the API - keep the two in step.
 */
export function parseVideoLink(raw: string): { platform: VideoPlatform; url: string } | { error: string } {
  const text = raw.trim()
  if (!text) return { error: 'Tautan video wajib diisi.' }

  let link: URL
  try {
    link = new URL(/^https?:\/\//i.test(text) ? text : `https://${text}`)
  } catch {
    return { error: 'Tautan harus dari Instagram (instagram.com) atau YouTube (youtube.com / youtu.be).' }
  }
  const host = link.hostname.toLowerCase().replace(/^(www\.|m\.)/, '')
  const path = link.pathname

  if (host === 'instagram.com') {
    const m = /^\/(p|reels?|tv)\/([A-Za-z0-9_-]+)\/?$/.exec(path)
    return m
      ? { platform: 'instagram', url: `https://www.instagram.com/${m[1] === 'reels' ? 'reel' : m[1]}/${m[2]}/` }
      : { error: 'Tautan Instagram harus berupa post, reel, atau IGTV: instagram.com/p/…, /reel/…, atau /tv/….' }
  }

  const id = /^[A-Za-z0-9_-]{11}$/
  const youtube = { error: 'Tautan YouTube harus berupa video: youtube.com/watch?v=…, youtu.be/…, atau youtube.com/shorts/….' }
  if (host === 'youtu.be') {
    const code = path.replace(/^\/+|\/+$/g, '')
    return id.test(code) ? { platform: 'youtube', url: `https://youtu.be/${code}` } : youtube
  }
  if (host === 'youtube.com') {
    const v = link.searchParams.get('v') ?? ''
    if (path === '/watch' && id.test(v)) return { platform: 'youtube', url: `https://www.youtube.com/watch?v=${v}` }
    const m = /^\/shorts\/([A-Za-z0-9_-]{11})\/?$/.exec(path)
    return m ? { platform: 'youtube', url: `https://www.youtube.com/shorts/${m[1]}` } : youtube
  }

  return { error: 'Tautan harus dari Instagram (instagram.com) atau YouTube (youtube.com / youtu.be).' }
}
