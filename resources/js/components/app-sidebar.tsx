import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { office , OMhelper , TTSAnalyzer } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, BarChart3, Activity , PieChart , FileText } from 'lucide-react';
import AppLogo from './app-logo';
import compensation from '@/routes/compensation'


const mainNavItems: NavItem[] = [
    {
        title: 'TTS Analyzer',
        href: TTSAnalyzer(),
        icon: BarChart3,
    },
    {
        title: 'Compensation',
            icon: Activity,
            isActive: true,
            href: '#',
            items: [
                {
                    title: 'TTS Tickets',
                    href: compensation.tts.url(),
                    icon: PieChart,
                },
                {
                    title: 'ADF Tickets',
                    href: compensation.adf.url(),
                    icon: PieChart,
                },
                {
                    title: 'Outage',
                    href: compensation.outage.url(),
                    icon: FileText,
                },
            ],
    },
    {
        title: 'OM Helper',
        href: OMhelper.url(),
        icon: BarChart3,
    },
    {
        title: 'Office tools',
        href: office.url(),
        icon: BarChart3,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'User Guide',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={TTSAnalyzer()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
            </SidebarFooter>
        </Sidebar>
    );
}
