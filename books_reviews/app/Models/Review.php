<?php

namespace App\Models;

use App\Models\Book;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Add this import statement

class Review extends Model
{
    use HasFactory;

    protected $fillable = ['review', 'rating'];


    /**
     * Get the book associated with the review.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * The "booted" method is called when the Review model is booted.
     * It registers event listeners for the "updated" and "deleted" events.
     * When a review is updated or deleted, it clears the cache for the corresponding book.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updated(fn (Review $review) => cache()->forget('book:' . $review->book_id));
        static::deleted(fn (Review $review) => cache()->forget('book:' . $review->book_id));
        static::created(fn (Review $review) => cache()->forget('book:' . $review->book_id));
    }
}
