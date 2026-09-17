/** Returns the number of filled stars (0-5) to render for a 1-5 star rating. */
export function starsCount(stars: number): number {
  return Math.max(0, Math.min(5, Math.round(stars)))
}
