const DIVISIONS: Array<{ amount: number; unit: string }> = [
    { amount: 60, unit: 's' },
    { amount: 60, unit: 'm' },
    { amount: 24, unit: 'h' },
    { amount: 7, unit: 'd' },
    { amount: 4.34524, unit: 'wk' },
    { amount: 12, unit: 'mo' },
    { amount: Number.POSITIVE_INFINITY, unit: 'y' },
];

export function relativeTime(iso: string): string {
    const then = new Date(iso).getTime();

    if (Number.isNaN(then)) {
        return '';
    }

    let duration = (Date.now() - then) / 1000;

    if (duration < 5) {
        return 'just now';
    }

    for (const division of DIVISIONS) {
        if (Math.abs(duration) < division.amount) {
            return `${Math.floor(Math.abs(duration))}${division.unit} ago`;
        }

        duration /= division.amount;
    }

    return '';
}

export function ageBetween(startIso: string, endIso: string): string {
    const start = new Date(startIso).getTime();
    const end = new Date(endIso).getTime();

    if (Number.isNaN(start) || Number.isNaN(end)) {
        return '';
    }

    let duration = Math.max(0, (end - start) / 1000);
    let result = '';

    for (const division of DIVISIONS) {
        if (Math.abs(duration) < division.amount) {
            result = `${Math.floor(Math.abs(duration))}${division.unit}`;
            break;
        }

        duration /= division.amount;
    }

    return result || '0s';
}
