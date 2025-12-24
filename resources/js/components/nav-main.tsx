import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { resolveUrl } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
    const [openMenus, setOpenMenus] = useState<Record<string, boolean>>({});

    // فتح القوائم تلقائيًا إذا أي عنصر فرعي نشط
    useEffect(() => {
        const newOpen: Record<string, boolean> = {};
        items.forEach((item) => {
            if (item.items) {
                newOpen[item.title] = item.items.some(
                    (sub) => resolveUrl(sub.href) === page.url
                );
            }
        });
        setOpenMenus(newOpen);
    }, [items, page.url]);

    const toggleMenu = (title: string) => {
        setOpenMenus((prev) => ({ ...prev, [title]: !prev[title] }));
    };

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const href = item.href ? resolveUrl(item.href) : '#';
                    const isActive = item.href
                        ? (href === '/' ? page.url === '/' : page.url.startsWith(href))
                        : false;

                    if (item.items) {
                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    onClick={() => toggleMenu(item.title)}
                                    isActive={openMenus[item.title]}
                                >
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                    <span className="ml-auto">{openMenus[item.title] ? '▾' : '▸'}</span>
                                </SidebarMenuButton>

                                {openMenus[item.title] && (
    <SidebarMenu className="mt-1">
        {item.items.map((sub) => (
            <SidebarMenuItem key={sub.title}>
                <SidebarMenuButton
                    asChild
                    isActive={resolveUrl(sub.href) === page.url}
                    className="pl-6 w-full" // padding للتفرقة فقط + full width
                >
                    <Link href={sub.href} prefetch className="flex items-center gap-2 w-full">
                        {sub.icon && <sub.icon />}
                        <span>{sub.title}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        ))}
    </SidebarMenu>
)}

                            </SidebarMenuItem>
                        );
                    }

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton asChild isActive={isActive} tooltip={{ children: item.title }}>
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
