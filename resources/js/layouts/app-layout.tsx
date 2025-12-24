import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';
import Footer from '@/components/footer';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
        <div className="flex min-h-screen flex-col">
            <main className="flex-1 w-full container mx-auto py-6">
                {children}
            </main>
            <Footer />
        </div>
    </AppLayoutTemplate>
);
