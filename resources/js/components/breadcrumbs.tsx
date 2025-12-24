import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/react';
import { Fragment, useEffect, useState } from 'react';
import { Moon, Sun } from 'lucide-react';

export function Breadcrumbs({
    breadcrumbs,
}: {
    breadcrumbs: BreadcrumbItemType[];
}) {
    const [theme, setTheme] = useState(localStorage.getItem('theme') || 'light');

    useEffect(() => {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        localStorage.setItem('theme', theme);
    }, [theme]);

    const toggleTheme = () => {
        setTheme(theme === 'light' ? 'dark' : 'light');
    };

    return (
        /* أضفنا w-full و justify-between و items-center */
        <div className="flex items-center justify-between w-full px-4 py-2">

            {/* 1. حاوية الروابط (أضفنا flex-1 لتأخذ كل المساحة المتاحة وتدفع الزر لليمين) */}
            <div className="flex-1 flex items-center min-w-0">
                {breadcrumbs.length > 0 && (
                    <Breadcrumb>
                        <BreadcrumbList>
                            {breadcrumbs.map((item, index) => {
                                const isLast = index === breadcrumbs.length - 1;
                                return (
                                    <Fragment key={index}>
                                        <BreadcrumbItem className="whitespace-nowrap">
                                            {isLast ? (
                                                <BreadcrumbPage>{item.title}</BreadcrumbPage>
                                            ) : (
                                                <BreadcrumbLink asChild>
                                                    <Link href={item.href || '#'}>{item.title}</Link>
                                                </BreadcrumbLink>
                                            )}
                                        </BreadcrumbItem>
                                        {!isLast && <BreadcrumbSeparator />}
                                    </Fragment>
                                );
                            })}
                        </BreadcrumbList>
                    </Breadcrumb>
                )}
            </div>

            <div className="flex items-right ml-4 shrink-0">
                <div
                    onClick={toggleTheme}
                    className="relative w-14 h-7 flex items-center bg-slate-200 dark:bg-slate-700 rounded-full p-1 cursor-pointer transition-colors duration-300"
                >
                    <Sun className="h-4 w-4 text-yellow-500 ml-0.5" />
                    <div className="flex-1" />
                    <Moon className="h-4 w-4 text-slate-400 mr-0.5" />

                    <div
                        className={`absolute bg-white w-5 h-5 rounded-full shadow-md transform transition-transform duration-300 flex items-center justify-center
                        ${theme === 'dark' ? 'translate-x-7' : 'translate-x-0'}`}
                    >
                        {theme === 'dark' ? (
                            <Moon className="h-3 w-3 text-slate-700" />
                        ) : (
                            <Sun className="h-3 w-3 text-yellow-500" />
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
