<?php

declare(strict_types=1);

namespace CnA\Ai\Rag;

class Document
{
    public function __construct(
        public readonly string $id,
        public readonly string $content,
        public readonly array $vector = [],
        public readonly ?string $tenantId = null,
        public readonly array $metadata = []
    ) {
    }
}

class Chunker
{
    public static function chunk(string $text, int $chunkSize = 500, int $overlap = 50): array
    {
        $words = preg_split('/\s+/', $text);
        if ($words === false || empty($words)) {
            return [];
        }

        $chunks = [];
        $i = 0;
        $totalWords = count($words);

        while ($i < $totalWords) {
            $slice = array_slice($words, $i, $chunkSize);
            $chunks[] = implode(' ', $slice);
            $i += ($chunkSize - $overlap);
            if ($i <= 0) break;
        }

        return $chunks;
    }
}

class VectorStore
{
    /** @var array<Document> */
    protected array $documents = [];

    public function addDocument(Document $doc): void
    {
        $this->documents[$doc->id] = $doc;
    }

    /**
     * Search with cosine similarity and enforce tenant isolation.
     *
     * @return array<array{document: Document, score: float}>
     */
    public function search(array $queryVector, int $limit = 5, ?string $tenantId = null): array
    {
        $results = [];

        foreach ($this->documents as $doc) {
            // Tenant isolation check
            if ($tenantId !== null && $doc->tenantId !== null && $doc->tenantId !== $tenantId) {
                continue;
            }

            if (empty($doc->vector)) {
                continue;
            }

            $score = $this->cosineSimilarity($queryVector, $doc->vector);
            $results[] = [
                'document' => $doc,
                'score' => $score,
            ];
        }

        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, $limit);
    }

    protected function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $dim = min(count($vecA), count($vecB));

        for ($i = 0; $i < $dim; $i++) {
            $dot += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] ** 2;
            $normB += $vecB[$i] ** 2;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}

