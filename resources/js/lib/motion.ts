import type { Variants } from 'motion/react';
export const fadeIn: Variants = { hidden: { opacity: 0 }, visible: { opacity: 1, transition: { duration: 0.2 } } };
export const fadeUp: Variants = { hidden: { opacity: 0, y: 12 }, visible: { opacity: 1, y: 0, transition: { duration: 0.25, ease: 'easeOut' } } };
export const scaleIn: Variants = { hidden: { opacity: 0, scale: 0.98 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.2 } } };
export const staggerContainer: Variants = { hidden: {}, visible: { transition: { staggerChildren: 0.05 } } };
export const staggerItem = fadeUp;
export const slideSidebar: Variants = { hidden: { x: '-100%' }, visible: { x: 0, transition: { duration: 0.25, ease: 'easeOut' } }, exit: { x: '-100%', transition: { duration: 0.2 } } };
export const modalOverlay: Variants = { hidden: { opacity: 0 }, visible: { opacity: 1 }, exit: { opacity: 0 } };
export const modalContent = scaleIn;
