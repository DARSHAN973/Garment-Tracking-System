import { getCurrentUser } from "@/app/lib/getCurrentUser";
import { redirect } from "next/navigation";

export default async function HomePage() {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login");
  }

  return (
  <main className="p-4 space-y-4">
    <header>
      <h1 className="text-xl font-semibold">
        Today
      </h1>
      <p className="text-sm text-gray-500">
        {new Date().toDateString()}
      </p>
    </header>

    <section className="space-y-3">
      <div className="p-4 border rounded-lg flex justify-between items-center">
        <span>Morning Run</span>
        <input type="checkbox" />
      </div>

      <div className="p-4 border rounded-lg flex justify-between items-center">
        <span>Read 10 pages</span>
        <input type="checkbox" />
      </div>
    </section>
  </main>
);

}
