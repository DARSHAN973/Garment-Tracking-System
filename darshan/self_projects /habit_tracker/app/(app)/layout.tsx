"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Home, CheckCircle, ListTodo, User } from "lucide-react";

export default function AppLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const pathname = usePathname();

  const isActive = (path: string) => pathname === path;

  return (
    <div className="min-h-screen bg-[#f7f8fa] text-black">
      <main className="pb-28 p-4 max-w-md mx-auto">
        {children}
      </main>

      <nav className="fixed bottom-4 left-0 right-0">
        <div className="mx-auto max-w-md rounded-2xl bg-white/90 backdrop-blur border shadow-lg flex justify-around py-3">
          {[
            { href: "/", label: "Dashboard", Icon: Home },
            { href: "/today", label: "Today", Icon: CheckCircle },
            { href: "/habits", label: "Habits", Icon: ListTodo },
            { href: "/profile", label: "Profile", Icon: User },
          ].map(({ href, label, Icon }) => {
            const active = isActive(href);
            return (
              <Link
                key={href}
                href={href}
                className={`relative flex flex-col items-center gap-1 transition-all duration-200 ${
                  active ? "text-blue-600 scale-110" : "opacity-50"
                }`}
              >
                {active && (
                  <span className="absolute -top-2 w-10 h-10 rounded-full bg-blue-500/20 blur-xl" />
                )}
                <Icon size={22} />
                <span className="text-[11px]">{label}</span>
              </Link>
            );
          })}
        </div>
      </nav>
    </div>
  );
}
