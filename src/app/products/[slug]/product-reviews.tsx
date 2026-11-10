"use client";

import { useEffect, useState } from "react";
import { Star, MessageSquare } from "lucide-react";
import { ReviewForm } from "./review-form";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { reviewApi, getToken } from "@/lib/api";
import type { Review } from "@/lib/types";

export function ProductReviews({ productId }: { productId: string }) {
  const [reviews, setReviews] = useState<Review[]>([]);
  const [loading, setLoading] = useState(true);
  const [isLoggedIn, setIsLoggedIn] = useState(false);

  const fetchReviews = async () => {
    const res = await reviewApi.getByProduct(productId);
    if (res.success && res.data) {
      setReviews(res.data);
    }
    setLoading(false);
  };

  useEffect(() => {
    setIsLoggedIn(!!getToken());
    fetchReviews();
  }, [productId]);

  const averageRating =
    reviews.length > 0
      ? (reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length).toFixed(1)
      : "0";

  return (
    <section className="mt-16 border-t border-border pt-10">
      <div className="flex items-end justify-between gap-4">
        <div>
          <p className="text-xs uppercase tracking-[0.15em] text-accent">Ulasan Pelanggan</p>
          <h2 className="mt-1 font-serif text-2xl">Penilaian Produk</h2>
        </div>
        {reviews.length > 0 && (
          <div className="flex items-center gap-1 text-sm font-medium">
            <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
            <span>{averageRating}</span>
            <span className="text-muted-foreground">({reviews.length} ulasan)</span>
          </div>
        )}
      </div>

      {/* Review Form (jika login) */}
      {isLoggedIn ? (
        <ReviewForm productId={productId} onSubmitted={fetchReviews} />
      ) : (
        <div className="mt-6 rounded-xl border border-dashed border-border p-5 text-center text-sm text-muted-foreground">
          Silakan <a href="/login" className="text-accent underline font-medium">masuk ke akun Anda</a> untuk memberikan ulasan produk ini.
        </div>
      )}

      {/* Reviews List */}
      <div className="mt-8 space-y-4">
        {loading ? (
          <p className="text-sm text-muted-foreground">Memuat ulasan...</p>
        ) : reviews.length === 0 ? (
          <div className="flex flex-col items-center justify-center rounded-xl border border-border bg-card/50 py-12 text-center">
            <MessageSquare className="h-8 w-8 text-muted-foreground" />
            <p className="mt-3 text-sm font-medium">Belum ada ulasan</p>
            <p className="mt-1 text-xs text-muted-foreground">Jadilah yang pertama mengulas aroma parfum ini.</p>
          </div>
        ) : (
          reviews.map((review) => (
            <div key={review.id} className="rounded-xl border border-border bg-card p-5">
              <div className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                  <Avatar className="h-8 w-8">
                    <AvatarImage src={review.user?.avatar_url || ""} />
                    <AvatarFallback className="text-xs">
                      {review.user?.full_name?.slice(0, 2).toUpperCase() || "U"}
                    </AvatarFallback>
                  </Avatar>
                  <div>
                    <p className="text-sm font-medium">{review.user?.full_name || "Pelanggan"}</p>
                    <p className="text-[10px] text-muted-foreground">
                      {new Date(review.created_at).toLocaleDateString("id-ID", {
                        day: "numeric",
                        month: "short",
                        year: "numeric",
                      })}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-0.5">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <Star
                      key={star}
                      className={`h-3.5 w-3.5 ${
                        review.rating >= star
                          ? "fill-yellow-400 text-yellow-400"
                          : "text-muted-foreground/30"
                      }`}
                    />
                  ))}
                </div>
              </div>
              {review.comment && (
                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                  {review.comment}
                </p>
              )}
            </div>
          ))
        )}
      </div>
    </section>
  );
}
