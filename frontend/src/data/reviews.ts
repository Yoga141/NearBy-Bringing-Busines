/** Renders a 1-5 star rating as a fixed 5-character "★★★☆☆"-style string. */
export function starsLabel(stars: number): string {
  const full = Math.round(stars)
  return '★★★★★☆☆☆☆☆'.slice(5 - full, 10 - full)
}
