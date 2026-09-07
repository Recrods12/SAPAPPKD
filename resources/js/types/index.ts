export interface AuthUser { id: number; name: string; email: string; roles: string[]; permissions: string[]; unread_notifications_count: number }
export interface SharedProps { app: { name: string; fullName: string; logoUrl: string | null; privacyNotice: string }; auth: { user: AuthUser | null }; flash: { status?: string }; [key: string]: unknown }
export interface PaginationLink { url: string | null; label: string; active: boolean }
export interface Paginated<T> { data: T[]; links: PaginationLink[]; current_page: number; last_page: number; total: number }
