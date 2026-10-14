export const money = (cents: number): string =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(cents / 100);

export const compact = (n: number): string =>
    new Intl.NumberFormat('en-US', { notation: 'compact' }).format(n);
