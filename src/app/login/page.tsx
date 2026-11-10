"use client";

import { useState } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { authApi } from "@/lib/api";
import { toast } from "sonner";

export default function LoginPage() {
  const [loading, setLoading] = useState(false);

  const handleLogin = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    const formData = new FormData(e.currentTarget);
    const email = formData.get("email") as string;
    const password = formData.get("password") as string;

    const res = await authApi.login(email, password);

    if (!res.success) {
      toast.error(res.error || "Gagal masuk, periksa email dan password.");
      setLoading(false);
    } else {
      toast.success("Berhasil masuk! Mengalihkan...");
      const role = res.data?.user?.role;
      setTimeout(() => {
        if (role === "admin" || role === "superadmin") {
          window.location.href = "/admin";
        } else {
          window.location.href = "/";
        }
      }, 500);
    }
  };

  return (
    <div className="flex min-h-[60vh] items-center justify-center px-4 py-12" suppressHydrationWarning>
      <div className="w-full max-w-sm space-y-6" suppressHydrationWarning>
        <div className="text-center" suppressHydrationWarning>
          <h1 className="font-serif text-2xl">Masuk</h1>
          <p className="mt-2 text-sm text-muted-foreground" suppressHydrationWarning>
            Masuk ke akun Anda untuk melanjutkan
          </p>
        </div>

        <form onSubmit={handleLogin} className="space-y-4">
          <div className="space-y-2" suppressHydrationWarning>
            <Label htmlFor="email">Email</Label>
            <Input id="email" name="email" type="email" required placeholder="nama@email.com" />
          </div>
          <div className="space-y-2" suppressHydrationWarning>
            <div className="flex items-center justify-between" suppressHydrationWarning>
              <Label htmlFor="password">Password</Label>
              <Link href="/forgot-password" className="text-xs text-muted-foreground hover:text-foreground">
                Lupa password?
              </Link>
            </div>
            <Input id="password" name="password" type="password" required placeholder="••••••••" />
          </div>
          <Button type="submit" className="w-full bg-accent text-accent-foreground hover:bg-accent/90" disabled={loading}>
            {loading ? "Memproses..." : "Masuk"}
          </Button>
        </form>

        <p className="text-center text-sm text-muted-foreground" suppressHydrationWarning>
          Belum punya akun?{" "}
          <Link href="/register" className="text-foreground underline">
            Daftar
          </Link>
        </p>

        <div className="rounded-lg border border-border bg-muted/40 p-3 text-xs text-muted-foreground space-y-1">
          <p className="font-semibold text-foreground">Akun Uji Coba Tersedia:</p>
          <p>Superadmin: <code className="text-foreground">superadmin@parfum.com</code> / <code className="text-foreground">superadmin123</code></p>
          <p>Admin: <code className="text-foreground">admin@parfum.com</code> / <code className="text-foreground">admin123</code></p>
          <p>Customer: <code className="text-foreground">user@parfum.com</code> / <code className="text-foreground">user123</code></p>
        </div>
      </div>
    </div>
  );
}
