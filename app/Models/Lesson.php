<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Lesson extends Model
{
    protected $fillable = ['course_id', 'title', 'content', 'video_url', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Lesson text (Markdown) rendered to safe HTML: raw HTML is stripped and unsafe links are removed. */
    public function renderedContent(): string
    {
        return (string) Str::markdown((string) $this->content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /** An embeddable player URL for YouTube / Vimeo links, or null when the link is something else. */
    public function embedUrl(): ?string
    {
        $url = (string) $this->video_url;

        if (preg_match('~^https://(?:www\.)?(?:youtube\.com/watch\?(?:[^#]*&)?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        if (preg_match('~^https://(?:www\.)?vimeo\.com/(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }
}
