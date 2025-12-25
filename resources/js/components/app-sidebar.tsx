import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    useSidebar,
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
import { BookOpen, BarChart3, Activity , FileSpreadsheet , ClipboardCheck ,FileSearch , AlertTriangle , ClipboardList } from 'lucide-react';
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
                    icon: ClipboardCheck,
                },
                {
                    title: 'ADF Tickets',
                    href: compensation.adf.url(),
                    icon: FileSearch,
                },
                {
                    title: 'Outage',
                    href: compensation.outage.url(),
                    icon: AlertTriangle,
                },
            ],
    },
    {
        title: 'OM Helper',
        href: OMhelper.url(),
        icon: ClipboardList,
    },
    {
        title: 'Office tools',
        href: office.url(),
        icon: FileSpreadsheet,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'User Guide',
        href: '#',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { toggleSidebar } = useSidebar();
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        {/* 3. تغيير SidebarMenuButton ليعمل كـ button بدلاً من Link */}
                        <SidebarMenuButton
                            size="lg"
                            onClick={toggleSidebar}
                            tooltip="Toggle Sidebar"
                        >
                            <AppLogo />
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
