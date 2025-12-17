"use client";

import { useState, useEffect } from "react";

export default function AuthPage() {
  const [isLogin, setIsLogin] = useState(true);

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  // redirect if already logged in
  useEffect(() => {
    fetch("/api/test/me").then((res) => {
      if (res.ok) {
        window.location.href = "/";
      }
    });
  }, []);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setLoading(true);

    const url = isLogin ? "/api/auth/login" : "/api/auth/signup";

    const body = isLogin
      ? { email, password }
      : { name, email, password };

    try {
      const res = await fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(body),
      });

      if (!res.ok) {
        const data = await res.json();
        setError(data.message || "Something went wrong");
        setLoading(false);
        return;
      }

      window.location.href = isLogin ? "/" : "/auth";
    } catch {
      setError("Something went wrong");
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="min-h-screen flex items-center justify-center p-4 ">
      <form
        onSubmit={handleSubmit}
        className="w-full max-w-sm space-y-4 bg-white p-6 rounded-lg shadow text-black"
      >
        <h1 className="text-xl font-semibold text-center text-dark">
          {isLogin ? "Login" : "Create Account"}
        </h1>

        {error && (
          <p className="text-sm text-red-600 text-center">{error}</p>
        )}

        {!isLogin && (
          <div>
            <label className="text-sm">Name</label>
            <input
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
              className="w-full border rounded px-3 py-2 mt-1"
            />
          </div>
        )}

        <div>
          <label className="text-sm">Email</label>
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            className="w-full border rounded px-3 py-2 mt-1"
          />
        </div>

        <div>
          <label className="text-sm">Password</label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            className="w-full border rounded px-3 py-2 mt-1"
          />
        </div>

        <button
          type="submit"
          disabled={loading}
          className="w-full bg-black text-white py-2 rounded disabled:opacity-50"
        >
          {loading
            ? isLogin
              ? "Logging in..."
              : "Creating account..."
            : isLogin
            ? "Login"
            : "Sign up"}
        </button>

        <p className="text-sm text-center">
          {isLogin ? "New here?" : "Already have an account?"}{" "}
          <button
            type="button"
            onClick={() => setIsLogin(!isLogin)}
            className="underline"
          >
            {isLogin ? "Create account" : "Login"}
          </button>
        </p>
      </form>
    </main>
  );
}
