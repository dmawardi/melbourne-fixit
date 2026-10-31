import { Link, usePage } from '@inertiajs/react';
import {
    ClipboardList,
    Inbox,
    LayoutDashboard,
    Plus,
    Truck,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as adminDashboard } from '@/routes/admin';
import { index as adminRequests } from '@/routes/admin/job-requests';
import { index as adminVendors } from '@/routes/admin/vendors';
import { create, index } from '@/routes/job-requests';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'My requests',
        href: index(),
        icon: ClipboardList,
    },
    {
        title: 'Request a quote',
        href: create(),
        icon: Plus,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: adminDashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'Requests',
        href: adminRequests(),
        icon: Inbox,
    },
    {
        title: 'Vendors',
        href: adminVendors(),
        icon: Truck,
    },
];

export function AppSidebar() {
    const { auth, emergencyCount } = usePage().props;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={index()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {auth.isAdmin && (
                    <NavMain
                        items={adminNavItems.map((item) =>
                            item.title === 'Dashboard'
                                ? { ...item, badge: emergencyCount }
                                : item,
                        )}
                        label="Admin"
                    />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
