import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import { PageProps, User } from '@/types';

function NavItem({ href, active, children }: PropsWithChildren<{ href: string; active?: boolean }>) {
    return (
        <Link
            href={href}
            className={
                'rounded-md px-3 py-2 text-sm font-medium transition ' +
                (active ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900')
            }
        >
            {children}
        </Link>
    );
}

export default function SiteLayout({ children }: PropsWithChildren) {
    const { auth, flash } = usePage<PageProps>().props;
    // Guests have no user even though the shared type says otherwise (Breeze pages are auth-only).
    const user: User | null = auth.user ?? null;

    return (
        <div className="flex min-h-screen flex-col bg-slate-50 text-slate-900">
            <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-6">
                        <Link href="/" className="flex items-center gap-2 text-lg font-bold tracking-tight">
                            <span className="grid h-8 w-8 place-items-center rounded-lg bg-indigo-600 text-sm text-white">DV</span>
                            DomainVault
                        </Link>
                        <nav className="hidden items-center gap-1 sm:flex">
                            <NavItem href="/" active={route().current('marketplace.*')}>Marketplace</NavItem>
                            {user && <NavItem href="/orders" active={route().current('orders.*')}>Orders</NavItem>}
                            {user?.role === 'admin' && <NavItem href="/admin" active={route().current('admin.*')}>Admin</NavItem>}
                        </nav>
                    </div>
                    <div className="flex items-center gap-2">
                        {user && (
                            <Link href="/cart" className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                                Cart
                            </Link>
                        )}
                        {user ? (
                            <>
                                <Link href={route('profile.edit')} className="hidden text-sm text-slate-600 hover:text-slate-900 sm:block">
                                    {user.name}
                                </Link>
                                <Link href={route('logout')} method="post" as="button" className="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
                                    Log out
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link href={route('login')} className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Log in</Link>
                                <Link href={route('register')} className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Sign up</Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            {(flash?.success || flash?.error) && (
                <div className="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        role="status"
                        className={
                            'rounded-lg border px-4 py-3 text-sm ' +
                            (flash.error ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800')
                        }
                    >
                        {flash.error ?? flash.success}
                    </div>
                </div>
            )}

            <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">{children}</main>

            <footer className="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
                DomainVault is a demo marketplace. No real domains are sold and Stripe runs in test mode.
            </footer>
        </div>
    );
}
