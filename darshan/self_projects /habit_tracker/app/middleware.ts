import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

export function middleware(request: NextRequest) {
  const session = request.cookies.get("session");
  const pathname = request.nextUrl.pathname;

  // public routes
  const isAuthRoute =
    pathname.startsWith("/auth/login") ||
    pathname.startsWith("/auth/signup");

  const isPublicRoute = pathname === "/";

  // user NOT logged in
  if (!session && !isAuthRoute && !isPublicRoute) {
    return NextResponse.redirect(
      new URL("/auth/login", request.url)
    );
  }

  // user IS logged in but trying to access auth pages
  if (session && isAuthRoute) {
    return NextResponse.redirect(
      new URL("/", request.url)
    );
  }

  return NextResponse.next();
}
