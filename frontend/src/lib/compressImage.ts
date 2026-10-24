/** Files already this small are uploaded untouched. */
const SKIP_BELOW_BYTES = 250 * 1024

/**
 * Downscale a photo to `maxEdge` and re-encode it as WebP in the browser, so
 * a phone photo uploads in a fraction of the time. The server optimises again
 * either way; on any failure, or when the result is not smaller, the original
 * file is returned.
 */
export async function compressImage(file: File, maxEdge: number, quality = 0.82): Promise<File> {
  if (file.size <= SKIP_BELOW_BYTES || typeof createImageBitmap !== 'function') return file

  let bitmap: ImageBitmap | null = null
  try {
    bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' })
    const scale = Math.min(1, maxEdge / Math.max(bitmap.width, bitmap.height))
    const width = Math.max(1, Math.round(bitmap.width * scale))
    const height = Math.max(1, Math.round(bitmap.height * scale))

    const canvas = document.createElement('canvas')
    canvas.width = width
    canvas.height = height
    const ctx = canvas.getContext('2d')
    if (!ctx) return file
    ctx.drawImage(bitmap, 0, 0, width, height)

    const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/webp', quality))
    // Browsers without WebP encoding fall back to PNG - not worth sending.
    if (!blob || blob.type !== 'image/webp' || blob.size >= file.size) return file

    return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.webp', { type: 'image/webp' })
  } catch {
    return file
  } finally {
    bitmap?.close()
  }
}
