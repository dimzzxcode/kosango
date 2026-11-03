export const accentGradients: Record<string, string> = {
    jade: 'from-emerald-500 via-teal-500 to-cyan-600',
    ocean: 'from-sky-500 via-blue-600 to-indigo-700',
    sunset: 'from-orange-500 via-rose-500 to-pink-600',
    violet: 'from-violet-500 via-purple-600 to-fuchsia-600',
    amber: 'from-amber-400 via-orange-500 to-red-500',
};

export const accentSoft: Record<string, string> = {
    jade: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
    ocean: 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
    sunset: 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
    violet: 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
    amber: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
};

export function gradientFor(accent: string): string {
    return accentGradients[accent] ?? accentGradients.jade;
}

/**
 * Derive a stable accent from a city slug so colours stay consistent
 * without storing presentation data in the database.
 */
export function accentForSlug(slug: string): string {
    const keys = Object.keys(accentGradients);
    let hash = 0;

    for (let index = 0; index < slug.length; index++) {
        hash = (hash * 31 + slug.charCodeAt(index)) % 100_000;
    }

    return keys[hash % keys.length];
}

export function gradientForSlug(slug: string): string {
    return gradientFor(accentForSlug(slug));
}

export function softFor(accent: string): string {
    return accentSoft[accent] ?? accentSoft.jade;
}
