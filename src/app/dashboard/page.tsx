"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { authApi } from "@/lib/api";
import { toast } from "sonner";
import type { Profile } from "@/lib/types";

export default function DashboardPage() {
  const [profile, setProfile] = useState<Profile | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const fetch = async () => {
      const res = await authApi.getMe();
      if (res.success && res.data?.user) {
        setProfile(res.data.user);
      }
    };
    fetch();
  }, []);

  const handleSave = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    const formData = new FormData(e.currentTarget);

    const res = await authApi.updateProfile({
      full_name: formData.get("full_name") as string,
      phone: formData.get("phone") as string,
      address: formData.get("address") as string,
      city: formData.get("city") as string,
      province: formData.get("province") as string,
      postal_code: formData.get("postal_code") as string,
    });

    if (!res.success) {
      toast.error(res.error || "Gagal menyimpan");
    } else {
      toast.success("Profil berhasil diperbarui");
      if (res.data?.user) {
        setProfile(res.data.user);
      }
    }
    setLoading(false);
  };

  if (!profile) return <p className="text-muted-foreground">Memuat...</p>;

  return (
    <div>
      <h1 className="font-serif text-2xl">Profil Saya</h1>
      <form onSubmit={handleSave} className="mt-6 space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="full_name">Nama Lengkap</Label>
            <Input id="full_name" name="full_name" defaultValue={profile.full_name || ""} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="phone">No. Telepon</Label>
            <Input id="phone" name="phone" defaultValue={profile.phone || ""} />
          </div>
        </div>
        <div className="space-y-2">
          <Label htmlFor="address">Alamat</Label>
          <Input id="address" name="address" defaultValue={profile.address || ""} />
        </div>
        <div className="grid gap-4 sm:grid-cols-3">
          <div className="space-y-2">
            <Label htmlFor="city">Kota</Label>
            <Input id="city" name="city" defaultValue={profile.city || ""} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="province">Provinsi</Label>
            <Input id="province" name="province" defaultValue={profile.province || ""} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="postal_code">Kode Pos</Label>
            <Input id="postal_code" name="postal_code" defaultValue={profile.postal_code || ""} />
          </div>
        </div>
        <Button type="submit" className="bg-accent text-accent-foreground hover:bg-accent/90" disabled={loading}>
          {loading ? "Menyimpan..." : "Simpan Perubahan"}
        </Button>
      </form>
    </div>
  );
}
