import { Loader2, Activity, Cpu } from "lucide-react";
import { useState, useEffect } from "react";

export default function SectionLoader({ text = "Analyzing Data..." }: { text?: string }) {
  const [dots, setDots] = useState("");

  // أنيميشن للنقاط الثلاث المتغيرة
  useEffect(() => {
    const interval = setInterval(() => {
      setDots(prev => {
        if (prev.length >= 3) return "";
        return prev + ".";
      });
    }, 500);

    return () => clearInterval(interval);
  }, []);

  return (
    <div className="absolute inset-0 z-50 bg-background/90 backdrop-blur-md
                    flex flex-col items-center justify-center rounded-xl
                    animate-in fade-in-0 zoom-in-95 duration-500">

      {/* الحاوية الرئيسية للأنيميشن */}
      <div className="relative flex items-center justify-center p-8">

        {/* حلقة التحميل الخارجية */}
        <div className="absolute w-24 h-24 border-4 border-primary/10 rounded-full" />

        {/* حلقة التحميل المتحركة */}
        <div className="absolute w-24 h-24 border-4 border-transparent border-t-primary border-r-primary/30 rounded-full
                        animate-[spin_1.5s_linear_infinite]" />

        {/* حلقة التحميل الداخلية */}
        <div className="absolute w-16 h-16 border-4 border-transparent border-b-primary/70 border-l-primary/50 rounded-full
                        animate-[spin_2s_linear_infinite_reverse]" />

        {/* الأيقونة المركزية */}
        <div className="relative">
          <Cpu className="w-10 h-10 text-primary animate-pulse" style={{ animationDuration: '2s' }} />

          {/* تأثير نبض إضافي */}
          <div className="absolute inset-0 rounded-full bg-primary/20 animate-ping" style={{ animationDuration: '3s' }} />
        </div>

        {/* نقاط تدور حول الأيقونة */}
        {[...Array(3)].map((_, i) => (
          <div
            key={i}
            className="absolute w-2 h-2 bg-primary rounded-full"
            style={{
              transform: `rotate(${i * 120}deg) translateX(40px)`,
              animation: `orbit 2s linear infinite`,
              animationDelay: `${i * 0.66}s`,
            }}
          />
        ))}
      </div>

      {/* النصوص مع أنيميشن */}
      <div className="mt-8 space-y-2 text-center max-w-xs">
        <div className="flex items-center justify-center gap-1">
          <p className="text-sm font-semibold text-foreground tracking-wide">
            {text}
          </p>
          <span className="text-sm font-semibold text-primary w-4 text-left">
            {dots}
          </span>
        </div>

        <p className="text-xs text-muted-foreground animate-fade-in-up" style={{ animationDelay: '0.3s' }}>
          Running calculations & fetching history<span className="animate-pulse">...</span>
        </p>

        {/* شريط التقدم */}
        <div className="mt-4 w-48 h-1.5 bg-primary/10 rounded-full overflow-hidden">
          <div className="h-full bg-primary rounded-full animate-progress" style={{ animationDuration: '3s' }} />
        </div>
      </div>

      {/* إضافة الأنيميشن المخصصة في الـ style */}
      <style>{`
        @keyframes orbit {
          0% {
            transform: rotate(0deg) translateX(40px) rotate(0deg);
            opacity: 0.7;
          }
          50% {
            opacity: 1;
          }
          100% {
            transform: rotate(360deg) translateX(40px) rotate(-360deg);
            opacity: 0.7;
          }
        }

        @keyframes progress {
          0% {
            width: 0%;
            transform: translateX(-100%);
          }
          50% {
            width: 60%;
          }
          100% {
            width: 100%;
            transform: translateX(0%);
          }
        }

        .animate-progress {
          animation: progress 3s ease-in-out infinite;
        }

        .animate-fade-in-up {
          animation: fadeInUp 0.5s ease-out forwards;
        }

        @keyframes fadeInUp {
          from {
            opacity: 0;
            transform: translateY(5px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        @keyframes spin-reverse {
          from {
            transform: rotate(360deg);
          }
          to {
            transform: rotate(0deg);
          }
        }
      `}</style>
    </div>
  );
}
