import { NextResponse } from "next/server";
import { cookies } from "next/headers";
import { prisma } from "@/app/lib/prisma";
import { verifyPassword } from "@/app/lib/auth";
import { createSession } from "@/app/lib/session";

export async function POST(req: Request) {
  try {
    // 1️⃣ Read request body
    const body = await req.json();
    const { email, password } = body;

    // 2️⃣ Validate input
    if (!email || !password) {
      return NextResponse.json(
        { message: "Email and password are required" },
        { status: 400 }
      );
    }

    // 3️⃣ Find user by email
    const user = await prisma.user.findUnique({
      where: { email },
    });

    // 4️⃣ If user not found → reject
    if (!user) {
      return NextResponse.json(
        { message: "Invalid credentials" },
        { status: 401 }
      );
    }

    // 5️⃣ Verify password (plain vs hashed)
    const isValid = await verifyPassword(password, user.password);

    if (!isValid) {
      return NextResponse.json(
        { message: "Invalid credentials" },
        { status: 401 }
      );
    }

    // 6️⃣ Create session in DB
    const { token, expiresAt } = await createSession(user.id);

    // 7️⃣ Set HTTP-only cookie
    (await
          // 7️⃣ Set HTTP-only cookie
          cookies()).set("session", token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === "production",
      sameSite: "lax",
      expires: expiresAt,
      path: "/",
    });

    // 8️⃣ Success response
    return NextResponse.json(
      { message: "Login successful" },
      { status: 200 }
    );
  } catch {
    // 9️⃣ Fallback error
    return NextResponse.json(
      { message: "Something went wrong" },
      { status: 500 }
    );
  }
}
