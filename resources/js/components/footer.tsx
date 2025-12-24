export default function Footer() {
    // Automatically fetch the current year
    const currentYear = new Date().getFullYear();

    return (
        <footer className="w-full border-t bg-background py-6 mt-auto">
            <div className="container mx-auto px-4">
                <div className="flex flex-col items-center justify-center gap-2 md:flex-row md:justify-between">

                    {/* Copyright and Year */}
                    <p className="text-sm text-muted-foreground">
                        © {currentYear} <span className="font-semibold text-foreground tracking-tight">TTS Analyzer</span>.
                        All rights reserved.
                    </p>

                    {/* Developer Credit */}
                    <p className="text-sm text-muted-foreground">
                        Developed by
                        <span className="ml-1 font-medium text-primary hover:text-primary/80 transition-colors cursor-default">
                            Mina Hawarei
                        </span>
                    </p>

                </div>
            </div>
        </footer>
    );
}
