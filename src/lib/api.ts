// src/lib/api.ts
// Client utility untuk berkomunikasi dengan Backend PHP Native

import type { Product, Category, Order, CartItem, WishlistItem, Review, Profile } from "./types";

const API_BASE_URL =
  process.env.NEXT_PUBLIC_PHP_API_URL || "http://localhost:8000/api";

function setCookie(name: string, value: string, days = 7) {
  if (typeof document === "undefined") return;
  const expires = new Date(Date.now() + days * 864e5).toUTCString();
  document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
}

function deleteCookie(name: string) {
  if (typeof document === "undefined") return;
  document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax`;
}

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem("auth_token");
}

export function setToken(token: string): void {
  if (typeof window === "undefined") return;
  localStorage.setItem("auth_token", token);
  setCookie("auth_token", token);
}

export function removeToken(): void {
  if (typeof window === "undefined") return;
  localStorage.removeItem("auth_token");
  localStorage.removeItem("auth_user");
  deleteCookie("auth_token");
  deleteCookie("user_role");
}

export function getStoredUser(): Profile | null {
  if (typeof window === "undefined") return null;
  const user = localStorage.getItem("auth_user");
  try {
    return user ? JSON.parse(user) : null;
  } catch {
    return null;
  }
}

export function setStoredUser(user: Profile): void {
  if (typeof window === "undefined") return;
  localStorage.setItem("auth_user", JSON.stringify(user));
  setCookie("user_role", user.role);
}

export async function fetchApi<T = any>(
  endpoint: string,
  options: RequestInit = {}
): Promise<{ success: boolean; data?: T; error?: string; message?: string }> {
  const token = getToken();

  const headers: Record<string, string> = {
    "Content-Type": "application/json",
    Accept: "application/json",
    ...(options.headers as Record<string, string>),
  };

  if (token) {
    headers["Authorization"] = `Bearer ${token}`;
  }

  const url = endpoint.startsWith("http")
    ? endpoint
    : `${API_BASE_URL}${endpoint.startsWith("/") ? "" : "/"}${endpoint}`;

  try {
    const res = await fetch(url, {
      ...options,
      headers,
    });

    const json = await res.json();
    return json;
  } catch (err: any) {
    return {
      success: false,
      error: err.message || "Gagal menghubungi server PHP",
    };
  }
}

// ----------------------------------------------------
// Specific API Clients
// ----------------------------------------------------

export const authApi = {
  async login(email: string, password: string) {
    const res = await fetchApi<{ token: string; user: Profile }>("/auth/login.php", {
      method: "POST",
      body: JSON.stringify({ email, password }),
    });
    if (res.success && res.data) {
      setToken(res.data.token);
      setStoredUser(res.data.user);
    }
    return res;
  },

  async register(data: { email: string; password: string; full_name?: string }) {
    const res = await fetchApi<{ token: string; user: Profile }>("/auth/register.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
    if (res.success && res.data) {
      setToken(res.data.token);
      setStoredUser(res.data.user);
    }
    return res;
  },

  async getMe() {
    return await fetchApi<{ user: Profile }>("/auth/me.php");
  },

  async updateProfile(data: Partial<Profile>) {
    const res = await fetchApi<{ user: Profile }>("/auth/update-profile.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
    if (res.success && res.data) {
      setStoredUser(res.data.user);
    }
    return res;
  },

  logout() {
    removeToken();
  },
};

export const productApi = {
  async getAll(params?: {
    featured?: number;
    category?: string;
    category_id?: number;
    search?: string;
    limit?: number;
    sort?: string;
  }) {
    const searchParams = new URLSearchParams();
    if (params) {
      Object.entries(params).forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== "") {
          searchParams.append(k, String(v));
        }
      });
    }
    const query = searchParams.toString() ? `?${searchParams.toString()}` : "";
    return await fetchApi<Product[]>(`/products/index.php${query}`);
  },

  async getBySlug(slug: string) {
    return await fetchApi<Product>(`/products/detail.php?slug=${encodeURIComponent(slug)}`);
  },

  async getById(id: string | number) {
    return await fetchApi<Product>(`/products/detail.php?id=${id}`);
  },

  async create(data: Partial<Product>) {
    return await fetchApi<{ id: number; slug: string }>("/products/index.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
  },

  async update(id: string | number, data: Partial<Product>) {
    return await fetchApi<{ success: boolean }>("/products/update.php", {
      method: "POST",
      body: JSON.stringify({ id, ...data }),
    });
  },

  async delete(id: string | number) {
    return await fetchApi<{ success: boolean }>("/products/delete.php", {
      method: "POST",
      body: JSON.stringify({ id }),
    });
  },
};

export const categoryApi = {
  async getAll() {
    return await fetchApi<Category[]>("/categories/index.php");
  },

  async create(data: { name: string; description?: string; image_url?: string }) {
    return await fetchApi<Category>("/categories/index.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
  },

  async update(id: string | number, data: { name: string; description?: string; image_url?: string }) {
    return await fetchApi<{ success: boolean }>("/categories/detail.php", {
      method: "POST",
      body: JSON.stringify({ id, ...data }),
    });
  },

  async delete(id: string | number) {
    return await fetchApi<{ success: boolean }>("/categories/detail.php", {
      method: "POST",
      body: JSON.stringify({ id, _method: "DELETE" }),
    });
  },
};

export const bannerApi = {
  async getAll() {
    return await fetchApi<{ id: string; image_url: string; sort_order: number }[]>("/banners/index.php");
  },

  async create(imageUrl: string, sortOrder: number = 0) {
    return await fetchApi<{ id: string }>("/banners/index.php", {
      method: "POST",
      body: JSON.stringify({ image_url: imageUrl, sort_order: sortOrder }),
    });
  },

  async delete(id: string | number) {
    return await fetchApi<{ success: boolean }>("/banners/index.php", {
      method: "DELETE",
      body: JSON.stringify({ id }),
    });
  },
};

export const cartApi = {
  async get() {
    return await fetchApi<CartItem[]>("/cart/index.php");
  },

  async add(productId: string | number, quantity: number = 1) {
    return await fetchApi<{ success: boolean }>("/cart/add.php", {
      method: "POST",
      body: JSON.stringify({ product_id: productId, quantity }),
    });
  },

  async update(id: string | number, quantity: number) {
    return await fetchApi<{ success: boolean }>("/cart/update.php", {
      method: "POST",
      body: JSON.stringify({ id, quantity }),
    });
  },

  async delete(id: string | number) {
    return await fetchApi<{ success: boolean }>("/cart/delete.php", {
      method: "POST",
      body: JSON.stringify({ id }),
    });
  },

  async clear() {
    return await fetchApi<{ success: boolean }>("/cart/delete.php", {
      method: "POST",
      body: JSON.stringify({ clear_all: 1 }),
    });
  },
};

export const wishlistApi = {
  async get() {
    return await fetchApi<WishlistItem[]>("/wishlist/index.php");
  },

  async toggle(productId: string | number) {
    return await fetchApi<{ added: boolean; message: string }>("/wishlist/index.php", {
      method: "POST",
      body: JSON.stringify({ product_id: productId }),
    });
  },

  async delete(productId: string | number) {
    return await fetchApi<{ success: boolean }>(`/wishlist/index.php?product_id=${productId}`, {
      method: "DELETE",
    });
  },
};

export const checkoutApi = {
  async placeOrder(input: {
    shipping_name: string;
    shipping_phone: string;
    shipping_address: string;
    shipping_city: string;
    shipping_province: string;
    shipping_postal_code: string;
    notes?: string;
    shipping_cost?: number;
  }) {
    return await fetchApi<{
      order_id: string;
      total_amount: number;
      items: any[];
      shipping: any;
    }>("/checkout/place-order.php", {
      method: "POST",
      body: JSON.stringify(input),
    });
  },
};

export const orderApi = {
  async getAll(params?: { admin?: boolean; status?: string }) {
    const searchParams = new URLSearchParams();
    if (params?.admin) searchParams.append("admin", "1");
    if (params?.status) searchParams.append("status", params.status);
    const query = searchParams.toString() ? `?${searchParams.toString()}` : "";
    return await fetchApi<Order[]>(`/orders/index.php${query}`);
  },

  async getDetail(id: string) {
    return await fetchApi<Order>(`/orders/detail.php?id=${encodeURIComponent(id)}`);
  },

  async updateStatus(orderId: string, status: string) {
    return await fetchApi<{ success: boolean }>("/orders/update-status.php", {
      method: "POST",
      body: JSON.stringify({ order_id: orderId, status }),
    });
  },

  async updateShipment(orderId: string, courier: string, trackingNumber: string) {
    return await fetchApi<{ success: boolean }>("/orders/shipment.php", {
      method: "POST",
      body: JSON.stringify({ order_id: orderId, courier, tracking_number: trackingNumber }),
    });
  },

  async delete(orderId: string) {
    return await fetchApi<{ success: boolean }>("/orders/delete.php", {
      method: "POST",
      body: JSON.stringify({ order_id: orderId }),
    });
  },
};

export const reviewApi = {
  async getByProduct(productId: string | number) {
    return await fetchApi<Review[]>(`/reviews/index.php?product_id=${productId}`);
  },

  async getAllForAdmin() {
    return await fetchApi<any[]>("/reviews/index.php?all=1");
  },

  async create(data: { product_id: string | number; rating: number; comment?: string }) {
    return await fetchApi<{ id: string }>("/reviews/index.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
  },

  async delete(id: string | number) {
    return await fetchApi<{ success: boolean }>("/reviews/index.php", {
      method: "DELETE",
      body: JSON.stringify({ id }),
    });
  },
};

export const settingApi = {
  async get() {
    return await fetchApi<Record<string, string>>("/settings/index.php");
  },

  async update(settings: Record<string, string>) {
    return await fetchApi<{ success: boolean }>("/settings/index.php", {
      method: "POST",
      body: JSON.stringify(settings),
    });
  },
};

export const userApi = {
  async getAll() {
    return await fetchApi<Profile[]>("/users/index.php");
  },

  async toggleRole(userId: string, role: "user" | "admin" | "superadmin") {
    return await fetchApi<{ success: boolean; role: string }>("/users/toggle-role.php", {
      method: "POST",
      body: JSON.stringify({ user_id: userId, role }),
    });
  },
};
