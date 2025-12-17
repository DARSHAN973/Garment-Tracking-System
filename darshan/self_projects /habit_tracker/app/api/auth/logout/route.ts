import { NextResponse } from "next/server";
import { cookies } from "next/headers";
import { prisma } from "@/app/lib/prisma";

export async function POST() {
  const cookieStore = cookies();
  const sessionToken = (await cookieStore).get("session")?.value;

  if (sessionToken) {
    await prisma.session.deleteMany({
      where: { token: sessionToken },
    });
  }

  (await cookieStore).delete("session");

  return NextResponse.json({ message: "Logged out" });
}
