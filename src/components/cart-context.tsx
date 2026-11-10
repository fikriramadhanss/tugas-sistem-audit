"use client";

import { createContext, useContext, useEffect, useState, ReactNode } from "react";
import { getToken, cartApi } from "@/lib/api";

interface CartContextType {
  cartCount: number;
  refreshCart: () => Promise<void>;
  loading: boolean;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

export function CartProvider({ children }: { children: ReactNode }) {
  const [cartCount, setCartCount] = useState<number>(0);
  const [loading, setLoading] = useState(true);

  const fetchCartCount = async () => {
    try {
      const token = getToken();
      if (!token) {
        setCartCount(0);
        setLoading(false);
        return;
      }

      const res = await cartApi.get();
      if (res.success && res.data) {
        const count = res.data.reduce((sum, item) => sum + (item.quantity || 0), 0);
        setCartCount(count);
      } else {
        setCartCount(0);
      }
    } catch (err) {
      console.error("Cart count error:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCartCount();
  }, []);

  return (
    <CartContext.Provider value={{ cartCount, refreshCart: fetchCartCount, loading }}>
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  const context = useContext(CartContext);
  if (context === undefined) {
    throw new Error("useCart must be used within a CartProvider");
  }
  return context;
}
