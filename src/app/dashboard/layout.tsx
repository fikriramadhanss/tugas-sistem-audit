import Link from "next/link";
import { Button } from "@/components/ui/button";
import { User, Package, ShieldAlert } from "lucide-react";
import { LogoutButton } from "@/components/logout-button";
import { cookies } from "next/headers";

export default async function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const cookieStore = await cookies();
  const userRole = cookieStore.get("user_role")?.value;
  const isAdmin = userRole === "admin" || userRole === "superadmin";

  return (
    <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
      <div className="flex flex-col gap-8 md:flex-row">
        <aside className="w-full shrink-0 md:w-48">
          <nav className="flex gap-2 md:flex-col">
            <Button variant="ghost" className="justify-start" render={<Link href="/dashboard" />}>
              <User className="mr-2 h-4 w-4" />Profil
            </Button>
            <Button variant="ghost" className="justify-start" render={<Link href="/dashboard/orders" />}>
              <Package className="mr-2 h-4 w-4" />Pesanan
            </Button>
            {isAdmin && (
              <Button variant="ghost" className="justify-start text-accent hover:text-accent/80 hover:bg-accent/10" render={<Link href="/admin" />}>
                <ShieldAlert className="mr-2 h-4 w-4" />Admin Panel
              </Button>
            )}
            <LogoutButton />
          </nav>
        </aside>
        <div className="flex-1">{children}</div>
      </div>
    </div>
  );
}
