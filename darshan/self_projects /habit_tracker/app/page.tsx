import { getCurrentUser } from "@/app/lib/getCurrentUser";
import { redirect } from "next/navigation";

export default async function HomePage() {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login");
  }

  return (
    <main className="p-4">
      <h1 className="text-xl font-semibold">
        Welcome, {user.name}
      </h1>
    </main>
  );
}
