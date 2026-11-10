"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Heart } from "lucide-react";
import { wishlistApi, getToken } from "@/lib/api";
import { toast } from "sonner";
import { ProductCard } from "@/components/product-card";
import type { WishlistItem, Product } from "@/lib/types";

export default function WishlistPage() {
  const [items, setItems] = useState<WishlistItem[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchWishlist = async () => {
    const token = getToken();
    if (!token) {
      setLoading(false);
      return;
    }

    const res = await wishlistApi.get();
    if (res.success && res.data) {
      setItems(res.data);
    }
    setLoading(false);
  };

  useEffect(() => {
    fetchWishlist();
  }, []);

  const removeItem = async (productId: string) => {
    const res = await wishlistApi.delete(productId);
    if (res.success) {
      setItems((prev) => prev.filter((i) => i.product_id !== productId && i.id !== productId));
      toast.success("Dihapus dari wishlist");
    } else {
      toast.error(res.error || "Gagal menghapus");
    }
  };

  if (loading) {
    return <div className="mx-auto max-w-7xl px-4 py-12"><p className="text-center text-muted-foreground">Memuat...</p></div>;
  }

  if (items.length === 0) {
    return (
      <div className="mx-auto max-w-7xl px-4 py-16 text-center">
        <Heart className="mx-auto h-12 w-12 text-muted-foreground" />
        <h1 className="mt-4 font-serif text-2xl">Wishlist Kosong</h1>
        <p className="mt-2 text-sm text-muted-foreground">Belum ada produk di wishlist Anda.</p>
        <Button render={<Link href="/products" />} className="mt-6">
          Jelajahi Produk
        </Button>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="font-serif text-2xl">Wishlist</h1>
      <div className="mt-8 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
        {items.map((item) => (
          <div key={item.id} className="relative">
            <ProductCard product={item.product as Product} />
            <Button
              variant="ghost"
              size="icon"
              className="absolute right-1 top-1 h-8 w-8 rounded-full bg-background/80"
              onClick={() => removeItem(item.product_id)}
            >
              <Heart className="h-4 w-4 fill-current text-red-500" />
            </Button>
          </div>
        ))}
      </div>
    </div>
  );
}
