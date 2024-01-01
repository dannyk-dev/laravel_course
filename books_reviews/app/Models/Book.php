<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Book extends Model
{
    use HasFactory;

    /**
     * Get the reviews for the book.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Scope a query to filter books by title.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $title
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTitle(Builder $query, string $title): Builder
    {
        return $query->where('title', 'LIKE', '%' . $title . '%');
    }

    /**
     * Scope to retrieve books with the count of reviews within a specified date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $from
     * @param  string|null  $to
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function scopeWithReviewsCount(Builder $query, $from=null, $to=null): Builder|QueryBuilder
    {
        return $query->withCount([
            'reviews' => fn(Builder $q) => $this->dateRangFilter($q, $from, $to)
        ]);
    }

    public function scopeWithAvgRating(Builder $query, $from=null, $to=null): Builder|QueryBuilder
    {
        return $query->withAvg([
            'reviews' => fn(Builder $q) => $this->dateRangFilter($q, $from, $to)
        ], 'rating');
    }

    /**
     * Scope a query to only include popular books within a specified date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $from
     * @param  string|null  $to
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function scopePopular(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withReviewsCount()->orderBy('reviews_count', 'desc');
    }

    /**
     * Scope a query to retrieve the highest rated books within a specified date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $from
     * @param  string|null  $to
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function scopeHighestRated(Builder $query, $from=null, $to=null): Builder|QueryBuilder
    {
        return $query->withAvgRating()->orderBy('reviews_avg_rating', 'desc');
    }


    /**
     * Scope a query to only include books with a minimum number of reviews.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $minReviews
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function scopeMinReviews(Builder $query, int $minReviews): Builder|QueryBuilder
    {
        return $query->having('reviews_count', '>=', $minReviews);
    }

    /**
     * Apply date range filter to the query.
     *
     * @param Builder $query The query builder instance.
     * @param mixed $from The start date of the range.
     * @param mixed $to The end date of the range.
     * @return void
     */
    private function dateRangFilter(Builder $query, $from = null, $to = null)
    {
        if ($from && !$to){
            $query->where('created_at', '>=', $from);
        } elseif (!$from && $to){
            $query->where('created_at', '<=', $to);
        } elseif ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }
    }

    /**
     * Scope a query to only include popular books from the last month.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function scopePopularLastMonth(Builder $query): Builder|QueryBuilder
    {
        return $query->popular(now()->subMonth(), now())
            ->highestRated(now()->subMonth(), now())
            ->minReviews(2);
    }

    /**
     * Scope a query to only include popular books from the last 6 months.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function scopePopularLast6Months(Builder $query): Builder|QueryBuilder
    {
        return $query->popular(now()->subMonths(6), now())
            ->highestRated(now()->subMonths(6), now())
            ->minReviews(5);
    }

    /**
     * Scope a query to retrieve the highest rated books from the last month.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function scopeHighestRatedLastMonth(Builder $query): Builder|QueryBuilder
    {
        return $query->highestRated(now()->subMonth(), now())
            ->popular(now()->subMonth(), now())
            ->minReviews(2);
    }

    /**
     * Scope a query to retrieve the highest rated books in the last 6 months.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function scopeHighestRatedLast6Months(Builder $query): Builder|QueryBuilder
    {
        return $query->highestRated(now()->subMonths(6), now())
            ->popular(now()->subMonths(6), now())
            ->minReviews(5);
    }

    /**
     * The "booted" method of the Book model.
     *
     * This method is called when the Book model is booted. It registers event listeners for the "updated" and "deleted" events.
     * When a Book is updated or deleted, the corresponding cache entry is removed.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updated(fn (Book $review) => cache()->forget('books:' . $review->book_id));
        static::deleted(fn (Book $review) => cache()->forget('books:' . $review->book_id));
    }
}
