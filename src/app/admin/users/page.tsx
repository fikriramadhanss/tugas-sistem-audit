"use client";

import { useEffect, useState } from "react";
import { RoleToggle } from "./role-toggle";
import { userApi } from "@/lib/api";

export default function AdminUsersPage() {
  const [users, setUsers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchUsers = async () => {
    const res = await userApi.getAll();
    if (res.success && res.data) {
      setUsers(res.data);
    }
    setLoading(false);
  };

  useEffect(() => {
    fetchUsers();
  }, []);

  return (
    <div>
      <h1 className="font-serif text-2xl">Users</h1>
      <div className="mt-6 overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border text-left text-xs text-muted-foreground">
              <th className="pb-2 pr-4">Nama</th>
              <th className="pb-2 pr-4">Telepon</th>
              <th className="pb-2 pr-4">Kota</th>
              <th className="pb-2 pr-4">Role</th>
              <th className="pb-2">Bergabung</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan={5} className="py-4 text-center text-muted-foreground">Memuat users...</td>
              </tr>
            ) : users.length === 0 ? (
              <tr>
                <td colSpan={5} className="py-4 text-center text-muted-foreground">Belum ada user.</td>
              </tr>
            ) : (
              users.map((user) => (
                <tr key={user.id} className="border-b border-border">
                  <td className="py-2 pr-4 font-medium">{user.full_name || user.email || "-"}</td>
                  <td className="py-2 pr-4">{user.phone || "-"}</td>
                  <td className="py-2 pr-4">{user.city || "-"}</td>
                  <td className="py-2 pr-4">
                    <RoleToggle userId={user.id} initialRole={user.role} onUpdated={fetchUsers} />
                  </td>
                  <td className="py-2 text-xs text-muted-foreground">
                    {new Date(user.created_at).toLocaleDateString("id-ID")}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
