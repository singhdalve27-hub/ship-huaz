<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class Post extends Model
{
    protected $fillable = [
        'category',
        'title',
        'excerpt',
        'link',
        'image',
        'post_date',
        'status',
    ];

    protected $appends = [
        'link',
    ];

    /**
     * Get destination link. Supports both physical database column and embedded excerpt fallback.
     */
    public function getLinkAttribute(?string $value = null): ?string
    {
        $raw = $value ?? ($this->attributes['link'] ?? null);
        if (!empty($raw)) {
            $raw = trim($raw);
            if (!preg_match('~^https?://~i', $raw)) {
                $raw = 'https://' . $raw;
            }
            return $raw;
        }

        // Fallback: extract from excerpt if embedded
        $rawExcerpt = $this->attributes['excerpt'] ?? '';
        if (!empty($rawExcerpt) && preg_match('/<!--DEST_LINK:(.*?)-->/', $rawExcerpt, $matches)) {
            $raw = trim($matches[1]);
            if (!empty($raw)) {
                if (!preg_match('~^https?://~i', $raw)) {
                    $raw = 'https://' . $raw;
                }
                return $raw;
            }
        }

        return null;
    }

    /**
     * Clean out any embedded link tags from excerpt before displaying.
     */
    public function getExcerptAttribute(?string $value = null): ?string
    {
        $raw = $value ?? ($this->attributes['excerpt'] ?? '');
        if (empty($raw)) {
            return '';
        }
        return trim(preg_replace('/<!--DEST_LINK:.*?-->/', '', $raw));
    }

    protected function image(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return '/images/blog1.jpg';
                }
                if (str_contains($value, 'XmTOpOEzc8Q71BziIlsEPiIcOn36151otlKZ9b5I')) {
                    return '/images/blog1.jpg';
                }
                if (str_contains($value, '6pdOrmIILX1jfPKuzGK7Wes1hNF0L0hzLfIWkjBF')) {
                    return '/images/blog2.jpg';
                }
                return $value;
            }
        );
    }
}