"use client";

import { Button } from "@/components/ui/button";
import { LogOut } from "lucide-react";
import { authApi } from "@/lib/api";

export function LogoutButton() {
  const handleLogout = () => {
    authApi.logout();
    window.location.href = "/login";
  };

  return (
    <Button variant="ghost" className="w-full justify-start text-muted-foreground" onClick={handleLogout}>
      <LogOut className="mr-2 h-4 w-4" />Keluar
    </Button>
  );
}
