"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

export default function AppLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const pathname = usePathname();

  const linkClass = (path: string) =>
    pathname === path
      ? "text-black font-semibold"
      : "text-black opacity-60";

  return (
    <div className="min-h-screen flex flex-col bg-white text-black">
      {/* Main content */}
      <main className="flex-1 p-4 pb-20">
        {children}
      </main>

      {/* Bottom Navigation */}
      <nav className="fixed bottom-0 left-0 right-0 border-t bg-white">
        <div className="max-w-md mx-auto flex justify-around py-3">
          <Link href="/" className={linkClass("/")}>
            Dashboard
          </Link>
          <Link href="/today" className={linkClass("/today")}>
            Today
          </Link>
          <Link href="/habits" className={linkClass("/habits")}>
            Habits
          </Link>
          <Link href="/profile" className={linkClass("/profile")}>
            Profile
          </Link>
        </div>
      </nav>
    </div>
  );
}
