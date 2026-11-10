"use client";

import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { userApi } from "@/lib/api";
import { toast } from "sonner";

interface RoleToggleProps {
  userId: string;
  initialRole: string;
  onUpdated?: () => void;
}

export function RoleToggle({ userId, initialRole, onUpdated }: RoleToggleProps) {
  const [role, setRole] = useState(initialRole);
  const [isPending, setIsPending] = useState(false);

  const handleToggle = async (newRole: "user" | "admin" | "superadmin") => {
    setIsPending(true);
    const res = await userApi.toggleRole(userId, newRole);

    if (!res.success) {
      toast.error(res.error || "Gagal mengubah role");
    } else {
      setRole(newRole);
      toast.success(`Role berhasil diubah ke ${newRole}`);
      if (onUpdated) onUpdated();
    }

    setIsPending(false);
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger disabled={isPending} className="focus:outline-none">
        <Badge
          variant={role === "superadmin" ? "default" : role === "admin" ? "secondary" : "outline"}
          className={`cursor-pointer ${isPending ? "opacity-50" : "hover:opacity-80"}`}
        >
          {role}
        </Badge>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {role !== "user" && (
          <DropdownMenuItem onClick={() => handleToggle("user")}>
            Ubah ke User
          </DropdownMenuItem>
        )}
        {role !== "admin" && (
          <DropdownMenuItem onClick={() => handleToggle("admin")}>
            Ubah ke Admin
          </DropdownMenuItem>
        )}
        {role !== "superadmin" && (
          <DropdownMenuItem onClick={() => handleToggle("superadmin")}>
            Ubah ke Superadmin
          </DropdownMenuItem>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
