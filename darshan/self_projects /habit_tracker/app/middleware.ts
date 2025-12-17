import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

export function middleware(request: NextRequest) {
  const session = request.cookies.get("session");
  const pathname = request.nextUrl.pathname;

  const isAuthPage = pathname.startsWith("/auth");

  // user NOT logged in
  if (!session && !isAuthPage) {
    return NextResponse.redirect(new URL("/auth", request.url));
  }

  // user IS logged in but trying to access auth page
  if (session && isAuthPage) {
    return NextResponse.redirect(new URL("/", request.url));
  }

  return NextResponse.next();
}
