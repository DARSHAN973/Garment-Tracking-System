"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  Home,
  CheckCircle,
  ListTodo,
  User,
} from "lucide-react";

export default function AppLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const pathname = usePathname();

  const linkClass = (path: string) =>
    pathname === path
      ? "flex flex-col items-center text-black font-semibold"
      : "flex flex-col items-center text-black opacity-60";

  return (
    <div className="min-h-screen flex flex-col bg-white text-black">
      <main className="flex-1 p-4 pb-24">
        {children}
      </main>

      <nav className="fixed bottom-0 left-0 right-0 border-t bg-white">
        <div className="max-w-md mx-auto flex justify-around py-2">
          <Link href="/" className={linkClass("/")}>
            <Home size={20} />
            <span className="text-xs mt-1">Dashboard</span>
          </Link>

          <Link href="/today" className={linkClass("/today")}>
            <CheckCircle size={20} />
            <span className="text-xs mt-1">Today</span>
          </Link>

          <Link href="/habits" className={linkClass("/habits")}>
            <ListTodo size={20} />
            <span className="text-xs mt-1">Habits</span>
          </Link>

          <Link href="/profile" className={linkClass("/profile")}>
            <User size={20} />
            <span className="text-xs mt-1">Profile</span>
          </Link>
        </div>
      </nav>
    </div>
  );
}
