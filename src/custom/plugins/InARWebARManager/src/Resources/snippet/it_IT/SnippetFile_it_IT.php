<?php declare (strict_types = 1);

namespace InAR\WebARManager\Resources\snippet\it_IT;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_it_IT implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'storefront.it-IT';
    }

    public function getPath(): string
    {
        return __DIR__ . '/storefront.it-IT.json';
    }

    public function getIso(): string
    {
        return 'it-IT';
    }

    public function getAuthor(): string
    {
        return 'InAR';
    }

    public function isBase(): bool
    {
        return false;
    }
}
