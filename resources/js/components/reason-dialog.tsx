import * as Dialog from '@radix-ui/react-dialog';
import { ReactNode, useId, useState } from 'react';
import { Button } from '@/components/ui/button';

interface ReasonDialogProps {
    trigger: ReactNode;
    title: string;
    description: string;
    label: string;
    confirmLabel?: string;
    destructive?: boolean;
    minimumLength?: number;
    options?: { value: string; label: string }[];
    optionLabel?: string;
    initialOption?: string;
    onConfirm: (reason: string, option?: string) => void;
}

export function ReasonDialog({ trigger, title, description, label, confirmLabel = 'Simpan', destructive = false, minimumLength = 10, options, optionLabel = 'Pilihan', initialOption, onConfirm }: ReasonDialogProps) {
    const [open, setOpen] = useState(false);
    const [reason, setReason] = useState('');
    const [option, setOption] = useState(initialOption ?? options?.[0]?.value ?? '');
    const reasonId = useId();
    const optionId = useId();
    const valid = reason.trim().length >= minimumLength && (!options || Boolean(option));

    function confirm() {
        if (!valid) return;
        onConfirm(reason.trim(), options ? option : undefined);
        setReason('');
        setOpen(false);
    }

    return <Dialog.Root open={open} onOpenChange={setOpen}>
        <Dialog.Trigger asChild>{trigger}</Dialog.Trigger>
        <Dialog.Portal>
            <Dialog.Overlay className="fixed inset-0 z-50 bg-slate-950/55" />
            <Dialog.Content className="fixed left-1/2 top-1/2 z-50 w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-2xl border bg-white p-6 shadow-xl focus:outline-none">
                <Dialog.Title className="text-lg font-bold">{title}</Dialog.Title>
                <Dialog.Description className="mt-2 text-sm leading-6 text-muted-foreground">{description}</Dialog.Description>
                {options && <label className="mt-5 grid gap-2 text-sm font-semibold" htmlFor={optionId}>{optionLabel}<select id={optionId} value={option} onChange={(event) => setOption(event.target.value)} className="h-11 rounded-lg border bg-white px-3 font-normal focus:outline-none focus:ring-2 focus:ring-primary">{options.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select></label>}
                <label className="mt-5 grid gap-2 text-sm font-semibold" htmlFor={reasonId}>{label}<textarea id={reasonId} value={reason} onChange={(event) => setReason(event.target.value)} className="min-h-28 rounded-lg border p-3 font-normal focus:outline-none focus:ring-2 focus:ring-primary" aria-describedby={`${reasonId}-help`} autoFocus /></label>
                <p id={`${reasonId}-help`} className={`mt-1 text-xs ${reason.length > 0 && !valid ? 'text-destructive' : 'text-muted-foreground'}`}>Minimal {minimumLength} karakter.</p>
                <div className="mt-6 flex justify-end gap-2"><Dialog.Close asChild><Button variant="outline">Batal</Button></Dialog.Close><Button variant={destructive ? 'destructive' : 'default'} disabled={!valid} onClick={confirm}>{confirmLabel}</Button></div>
            </Dialog.Content>
        </Dialog.Portal>
    </Dialog.Root>;
}
