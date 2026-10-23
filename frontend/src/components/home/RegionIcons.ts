export interface RegionVisual {
  gradient: string
  accentColor: string
  iconSvg: string
}

export const REGION_VISUALS: Record<string, RegionVisual> = {
  'Balikpapan Kota': {
    gradient: 'linear-gradient(145deg, #1b385a 0%, #2c5ead 100%)',
    accentColor: '#60A5FA',
    // Modern skyline buildings
    iconSvg: `
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full">
        <path d="M3 21h18"/>
        <path d="M5 21V7l8-4v18"/>
        <path d="M13 10l6 3v8"/>
        <line x1="8" y1="11" x2="8" y2="11.01"/>
        <line x1="8" y1="15" x2="8" y2="15.01"/>
        <line x1="16" y1="15" x2="16" y2="15.01"/>
        <line x1="16" y1="18" x2="16" y2="18.01"/>
      </svg>
    `,
  },
  'Balikpapan Utara': {
    gradient: 'linear-gradient(145deg, #1c4238 0%, #3e8e82 100%)',
    accentColor: '#34D399',
    // Compass / hills / nature
    iconSvg: `
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full">
        <circle cx="12" cy="12" r="9"/>
        <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76" fill="currentColor" fill-opacity="0.2"/>
      </svg>
    `,
  },
  'Balikpapan Selatan': {
    gradient: 'linear-gradient(145deg, #2a3454 0%, #4f46e5 100%)',
    accentColor: '#818CF8',
    // Airport gateway / landmark
    iconSvg: `
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full">
        <path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/>
        <circle cx="12" cy="10" r="3" fill="currentColor" fill-opacity="0.2"/>
      </svg>
    `,
  },
  'Balikpapan Timur': {
    gradient: 'linear-gradient(145deg, #5c3818 0%, #c98a2e 100%)',
    accentColor: '#FBBF24',
    // Coastal sunrise & waves
    iconSvg: `
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full">
        <path d="M12 4v2m0 12v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41m14.14-14.14l-1.41 1.41"/>
        <path d="M2 18c2-2 4 0 6-1s4 1 6-1 4 1 6-1"/>
        <circle cx="12" cy="12" r="4" fill="currentColor" fill-opacity="0.2"/>
      </svg>
    `,
  },
  'Balikpapan Barat': {
    gradient: 'linear-gradient(145deg, #134252 0%, #0891b2 100%)',
    accentColor: '#22D3EE',
    // Harbor port & maritime anchor
    iconSvg: `
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full">
        <circle cx="12" cy="5" r="3"/>
        <line x1="12" y1="8" x2="12" y2="21"/>
        <path d="M5 12H2a10 10 0 0 0 20 0h-3"/>
        <line x1="9" y1="12" x2="15" y2="12"/>
      </svg>
    `,
  },
  'Balikpapan Tengah': {
    gradient: 'linear-gradient(145deg, #37284f 0%, #7c3aed 100%)',
    accentColor: '#A78BFA',
    // Central hub nexus
    iconSvg: `
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full">
        <circle cx="12" cy="12" r="3" fill="currentColor" fill-opacity="0.3"/>
        <path d="M12 3v3m0 12v3M3 12h3m12 0h3"/>
        <circle cx="12" cy="12" r="8" stroke-dasharray="3 3"/>
      </svg>
    `,
  },
}
