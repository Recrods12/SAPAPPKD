import { router } from "@inertiajs/react";

interface SortOption {
    value: string;
    label: string;
}

interface DataSortControlsProps {
    routeName: string;
    sort: string;
    direction: string;
    options: SortOption[];
}

export function DataSortControls({
    routeName,
    sort,
    direction,
    options,
}: DataSortControlsProps) {
    function update(nextSort: string, nextDirection: string) {
        const parameters = Object.fromEntries(
            new URLSearchParams(window.location.search),
        );
        router.get(
            route(routeName),
            { ...parameters, sort: nextSort, direction: nextDirection },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <div className="flex flex-wrap items-center justify-end gap-2 border-b px-4 py-3 text-sm">
            <label
                htmlFor={`${routeName}-sort`}
                className="font-medium text-muted-foreground"
            >
                Urutkan
            </label>
            <select
                id={`${routeName}-sort`}
                className="h-9 rounded-lg border bg-white px-3"
                value={sort}
                onChange={(event) => update(event.target.value, direction)}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            <select
                aria-label="Arah pengurutan"
                className="h-9 rounded-lg border bg-white px-3"
                value={direction}
                onChange={(event) => update(sort, event.target.value)}
            >
                <option value="asc">Menaik</option>
                <option value="desc">Menurun</option>
            </select>
        </div>
    );
}
