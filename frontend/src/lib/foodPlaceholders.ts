import foodPlate from '@/assets/placeholders/food-plate.svg'
import foodDrink from '@/assets/placeholders/food-drink.svg'
import foodBowl from '@/assets/placeholders/food-bowl.svg'
import foodSnack from '@/assets/placeholders/food-snack.svg'

export const FOOD_PLACEHOLDERS = [
  foodPlate,
  foodDrink,
  foodBowl,
  foodSnack,
]

/**
 * Returns a high-quality "makanan dan minuman" placeholder image
 * deterministically based on UMKM category and ID.
 */
export function getFoodPlaceholder(id?: number | string | null, category?: string | null): string {
  const cat = (category || '').toLowerCase().trim()

  if (cat.includes('minuman') || cat.includes('kopi') || cat.includes('tea')) {
    return foodDrink
  }

  if (cat.includes('oleh') || cat.includes('snack') || cat.includes('kue')) {
    return foodSnack
  }

  if (cat.includes('kuliner') || cat.includes('makanan') || cat.includes('resto') || cat.includes('warung')) {
    const num = Number(id) || 1
    return num % 2 === 0 ? foodBowl : foodPlate
  }

  // Fallback distribution for other businesses so homepage cards always have vibrant food & drink imagery
  const num = Math.abs(Number(id) || 0)
  return FOOD_PLACEHOLDERS[num % FOOD_PLACEHOLDERS.length]
}
