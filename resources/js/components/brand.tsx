import { usePage } from '@inertiajs/react';
import { MapPinCheck } from 'lucide-react';
import type { SharedProps } from '@/types';

export function Brand({ inverse = false, compact = false }: { inverse?: boolean; compact?: boolean }) {
    const { app } = usePage<SharedProps>().props;
    return <div className="flex min-w-0 items-center gap-3">{app.logoUrl ? <img src={app.logoUrl} alt={`Logo ${app.fullName}`} className="size-10 shrink-0 rounded-xl object-contain"/> : <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-primary text-white shadow-sm"><MapPinCheck className="size-5"/></span>}<div className="min-w-0 leading-tight"><strong className={`${inverse ? 'text-white' : 'text-foreground'} block truncate`}>{app.name}</strong><span className={`${compact ? 'hidden sm:block' : 'block'} truncate text-xs ${inverse ? 'text-blue-200' : 'text-muted-foreground'}`}>{app.fullName}</span></div></div>;
}
