<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\AttachmentContent\AttachmentContentQuery;
use App\Notes\Application\AttachmentContent\AttachmentContentResponse;
use App\Notes\Domain\Exception\AttachmentNotFound;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the stored file to its owner. Content is user-supplied, so it is always served as a download
 * unless the client explicitly asks to view a raster image inline, and never sniffed or executed.
 */
class AttachmentContentController extends BaseApiController
{
    /** Raster formats only: SVG and HTML can carry scripts. */
    private const array INLINE_SAFE_MIME_TYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    public function __invoke(string $id, Request $request): Response
    {
        /** @var AttachmentContentResponse $response */
        $response = $this->ask(
            new AttachmentContentQuery(
                id: $id,
                ownerId: $this->currentUserId()
            )
        );

        $attachment = $response->getAttachment();
        $mimeType = $attachment->getMimeType()->value();
        $inline = $request->query->getBoolean('inline') && in_array($mimeType, self::INLINE_SAFE_MIME_TYPES, true);
        $fileName = $attachment->getFileName()->value();
        $stream = $response->getStream();

        $streamed = new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        });

        $streamed->headers->set('Content-Type', $mimeType);
        $streamed->headers->set('Content-Length', (string) $attachment->getSizeBytes()->value());
        $streamed->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(
                $inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
                $fileName,
                $this->asciiFallback($fileName)
            )
        );
        $streamed->headers->set('X-Content-Type-Options', 'nosniff');
        $streamed->headers->set('Content-Security-Policy', "default-src 'none'; sandbox");
        $streamed->headers->set('Cache-Control', 'private, no-store');

        return $streamed;
    }

    private function asciiFallback(string $fileName): string
    {
        $fallback = preg_replace('/[^\x20-\x7e]|[%\\\\"\/]/', '_', $fileName) ?? 'download';

        return $fallback === '' ? 'download' : $fallback;
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            AttachmentNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
