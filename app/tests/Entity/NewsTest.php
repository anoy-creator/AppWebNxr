<?php

namespace App\Tests\Entity;

use App\Entity\News;
use PHPUnit\Framework\TestCase;

class NewsTest extends TestCase
{
    public function testNewsBasics(): void
    {
        $news = new News();
        $date = new \DateTimeImmutable('2026-05-27');

        $news
            ->setTitle('Titre')
            ->setAuthor('Admin')
            ->setDate($date)
            ->setImage('img.jpg')
            ->setExcerpt('Résumé')
            ->setContent('Contenu');

        $this->assertNull($news->getId());
        $this->assertSame('Titre', $news->getTitle());
        $this->assertSame('Admin', $news->getAuthor());
        $this->assertSame($date, $news->getDate());
        $this->assertSame('img.jpg', $news->getImage());
        $this->assertSame('Résumé', $news->getExcerpt());
        $this->assertSame('Contenu', $news->getContent());
    }
}
