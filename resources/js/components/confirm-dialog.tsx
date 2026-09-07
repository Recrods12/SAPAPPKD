import * as Dialog from '@radix-ui/react-dialog';
import { ReactNode, useState } from 'react';
import { Button } from '@/components/ui/button';

interface ConfirmDialogProps {
    trigger: ReactNode;
    title: string;
    description: string;
    confirmLabel?: string;
    destructive?: boolean;
    onConfirm: () => void;
}

export function ConfirmDialog({ trigger, title, description, confirmLabel = 'Konfirmasi', destructive = false, onConfirm }: ConfirmDialogProps) {
    const [open, setOpen] = useState(false);
    function confirm() { setOpen(false); onConfirm(); }
    return <Dialog.Root open={open} onOpenChange={setOpen}><Dialog.Trigger asChild>{trigger}</Dialog.Trigger><Dialog.Portal><Dialog.Overlay className="fixed inset-0 z-50 bg-slate-950/55 data-[state=closed]:animate-out data-[state=open]:animate-in"/><Dialog.Content className="fixed left-1/2 top-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl border bg-white p-6 shadow-xl focus:outline-none"><Dialog.Title className="text-lg font-bold">{title}</Dialog.Title><Dialog.Description className="mt-2 text-sm leading-6 text-muted-foreground">{description}</Dialog.Description><div className="mt-6 flex justify-end gap-2"><Dialog.Close asChild><Button variant="outline">Batal</Button></Dialog.Close><Button variant={destructive ? 'destructive' : 'default'} onClick={confirm}>{confirmLabel}</Button></div></Dialog.Content></Dialog.Portal></Dialog.Root>;
}
