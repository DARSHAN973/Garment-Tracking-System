import Link from "next/link";

export default function AppLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen flex flex-col bg-white text-black">
      {/* Main content */}
      <main className="flex-1 p-4 pb-20">
        {children}
      </main>

      {/* Bottom Navigation */}
      <nav className="fixed bottom-0 left-0 right-0 border-t bg-white">
        <div className="max-w-md mx-auto flex justify-around py-3">
          <Link href="/" className="text-sm">
            Dashboard
          </Link>
          <Link href="/today" className="text-sm">
            Today
          </Link>
          <Link href="/habits" className="text-sm">
            Habits
          </Link>
          <Link href="/profile" className="text-sm">
            Profile
          </Link>
        </div>
      </nav>
    </div>
  );
}
