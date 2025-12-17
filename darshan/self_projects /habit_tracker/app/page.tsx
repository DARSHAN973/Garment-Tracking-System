import { getCurrentUser } from "@/app/lib/getCurrentUser";
import { redirect } from "next/navigation";


export default async function HomePage() {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/auth");
  }

  return (
  <main className="p-4 space-y-4">
  <header className="flex justify-between items-center">
    <h1 className="text-xl font-semibold">Today</h1>

    <form
      action={async () => {
        "use server";
        await fetch("http://localhost:3000/api/auth/logout", {
          method: "POST",
        });
        redirect("/auth");
      }}
    >
      <button className="text-sm text-red-600">
        Logout
      </button>
    </form>
  </header>

  <p className="text-sm text-gray-500">
    {new Date().toDateString()}
  </p>
</main>

);

}
