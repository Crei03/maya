<?php

declare(strict_types=1);

namespace App\Automation;

class BlogSeoAnalyzer
{
    private string $plainText;

    public function __construct(
        private string $htmlContent,
        private string $title,
        private string $locale = 'es',
    ) {
        $this->plainText = strip_tags($this->htmlContent);
    }

    public function analyze(): array
    {
        return [
            'meta_title' => $this->suggestMetaTitle(),
            'meta_description' => $this->suggestMetaDescription(),
            'meta_keywords' => implode(', ', $this->extractKeywords()),
            'og_title' => $this->suggestMetaTitle(),
            'og_description' => $this->suggestMetaDescription(),
            'reading_time' => $this->extractReadingTime(),
            'structured_data' => $this->generateStructuredData(),
        ];
    }

    public function suggestMetaTitle(): string
    {
        $prefix = 'Blog Maya | ';
        $maxLen = 60 - strlen($prefix);
        $title = $this->title;
        if (mb_strlen($title) > $maxLen) {
            $title = mb_substr($title, 0, $maxLen - 3) . '...';
        }
        return $prefix . $title;
    }

    public function suggestMetaDescription(): string
    {
        $text = $this->plainText;
        if (preg_match('/^([^.!?]+[.!?][^.!?]+[.!?])/u', $text, $matches)) {
            $desc = trim($matches[1]);
        } else {
            $desc = mb_substr($text, 0, 155);
        }
        if (mb_strlen($desc) > 155) {
            $desc = mb_substr($desc, 0, 152) . '...';
        }
        return $desc;
    }

    public function extractKeywords(int $limit = 8): array
    {
        $stopWords = ['el','la','los','las','de','del','en','un','una','y','o','que',
            'por','para','con','sin','se','su','al','es','lo','como','más','pero',
            'sus','le','ya','este','entre','cuando','muy','hay','vez','todo','nos',
            'han','así','ser','fue','son','era','está','están','tiene','tienen',
            'del','al','una','unos','unas','sobre','todo','también','solo','otros',
            'cada','hace','puede','pueden','hacer','forma','parte','desde','hasta',
            'porque','cual','cuales','donde','cuando','aunque','sino','mientras'];

        $words = preg_split('/\s+/', strtolower($this->plainText));
        $words = array_map(function($w) {
            return preg_replace('/[^a-záéíóúüñ]/u', '', $w);
        }, $words);
        $words = array_filter($words, function($w) use ($stopWords) {
            return mb_strlen($w) > 2 && !in_array($w, $stopWords);
        });

        $freq = array_count_values($words);

        $scored = [];
        foreach ($freq as $word => $count) {
            $scored[$word] = $count * mb_strlen($word);
        }
        arsort($scored);

        return array_slice(array_keys($scored), 0, $limit);
    }

    public function extractReadingTime(int $wpm = 200): int
    {
        $wordCount = str_word_count($this->plainText, 0, 'áéíóúüñÁÉÍÓÚÜÑ');
        return max(1, (int)ceil($wordCount / $wpm));
    }

    public function generateStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $this->title,
            'description' => $this->suggestMetaDescription(),
            'datePublished' => now()->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => 'Maya',
            ],
        ];
    }
}
