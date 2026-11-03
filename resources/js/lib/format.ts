export function parseDate(value: string): Date {
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return new Date(`${value}T00:00:00`);
    }

    return new Date(value);
}

export function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(parseDate(value));
}

export function formatDateLong(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(parseDate(value));
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

export function nightsBetween(checkIn: string, checkOut: string): number {
    if (!checkIn || !checkOut) {
        return 0;
    }

    const start = parseDate(checkIn).getTime();
    const end = parseDate(checkOut).getTime();

    return Math.max(0, Math.round((end - start) / 86_400_000));
}

export function todayIso(): string {
    return isoDate(new Date());
}

export function isoDate(date: Date): string {
    const offset = date.getTimezoneOffset();

    return new Date(date.getTime() - offset * 60_000).toISOString().slice(0, 10);
}

export function addDaysIso(date: Date, days: number): string {
    const result = new Date(date);
    result.setDate(result.getDate() + days);

    return isoDate(result);
}

export function addDaysToIso(value: string, days: number): string {
    const result = parseDate(value);
    result.setDate(result.getDate() + days);

    return isoDate(result);
}

export function daysBetween(start: string, end: string): number {
    return Math.round(
        (parseDate(end).getTime() - parseDate(start).getTime()) / 86_400_000,
    );
}

export function periodLabel(period: string): string {
    switch (period) {
        case 'daily':
            return 'Harian';
        case 'monthly':
            return 'Bulanan';
        case 'yearly':
            return 'Tahunan';
        default:
            return period;
    }
}

export function periodUnit(period: string): string {
    switch (period) {
        case 'daily':
            return 'hari';
        case 'monthly':
            return 'bulan';
        case 'yearly':
            return 'tahun';
        default:
            return 'periode';
    }
}

export function planLabel(plan: string): string {
    switch (plan) {
        case 'full':
            return 'Lunas sekaligus';
        case 'per_period':
            return 'Bayar per periode';
        case 'installment':
            return 'Cicilan';
        default:
            return plan;
    }
}

const periodOrder = ['daily', 'monthly', 'yearly'];

/**
 * Cheapest active rate of a property, preferring the shortest period.
 * Mirrors the server-side "Mulai dari" rule from the PRD.
 */
export function startingPrice(
    property: {
        room_types?: Array<{
            prices?: Array<{
                period: string;
                price: number;
                is_active: boolean;
            }>;
        }>;
    },
    preferredPeriod?: string,
): { price: number; unit: string } | null {
    const rates = (property.room_types ?? []).flatMap((roomType) =>
        (roomType.prices ?? []).filter((rate) => rate.is_active),
    );

    if (rates.length === 0) {
        return null;
    }

    const periods = preferredPeriod ? [preferredPeriod] : periodOrder;

    for (const period of periods) {
        const cheapest = rates
            .filter((rate) => rate.period === period)
            .sort((a, b) => a.price - b.price)[0];

        if (cheapest) {
            return { price: cheapest.price, unit: periodUnit(period) };
        }
    }

    return preferredPeriod ? startingPrice(property) : null;
}

export function lowestRate(
    property: {
        room_types?: Array<{
            prices?: Array<{
                period: string;
                price: number;
                is_active: boolean;
            }>;
        }>;
    },
    period: string,
): number | null {
    const prices = (property.room_types ?? [])
        .flatMap((roomType) => roomType.prices ?? [])
        .filter((rate) => rate.is_active && rate.period === period)
        .map((rate) => rate.price);

    return prices.length > 0 ? Math.min(...prices) : null;
}
