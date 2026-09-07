import type { ReactNode } from 'react';
import { motion } from 'motion/react';
import { Brand } from '@/components/brand';
import { fadeUp, scaleIn } from '@/lib/motion';

export function AuthLayout({ children, title, description }: { children: ReactNode; title: string; description: string }) {
  return <main className="min-h-screen bg-white xl:grid xl:grid-cols-[minmax(440px,1fr)_minmax(520px,1fr)]">
    <section className="relative hidden overflow-hidden bg-slate-950 p-12 text-white xl:flex xl:flex-col xl:justify-between">
      <div className="sapa-grid absolute inset-0 opacity-60"/><div className="relative"><Brand inverse/></div>
      <motion.div className="relative max-w-xl" initial="hidden" animate="visible" variants={fadeUp}><span className="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold">LAYANAN DIGITAL PPKD</span><h1 className="mt-6 text-5xl font-bold leading-tight">Hadir tepat waktu,<br/><span className="text-blue-300">bertumbuh bersama.</span></h1><p className="mt-5 max-w-lg text-lg leading-8 text-blue-100/70">Presensi peserta pelatihan yang transparan melalui verifikasi foto, lokasi, dan waktu server.</p></motion.div>
      <p className="relative text-xs text-blue-200/60">PPKD Jakarta Barat · Asia/Jakarta</p>
    </section>
    <section className="flex min-w-0 items-center justify-center px-5 py-10 sm:px-10"><motion.div className="w-full max-w-md" initial="hidden" animate="visible" variants={scaleIn}><div className="mb-8 xl:hidden"><Brand/></div><p className="text-xs font-bold tracking-[.16em] text-primary">SAPA PPKD</p><h2 className="mt-2 text-3xl font-bold">{title}</h2><p className="mt-2 text-sm leading-6 text-muted-foreground">{description}</p>{children}</motion.div></section>
  </main>;
}
