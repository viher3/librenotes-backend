<?php

namespace App\Core\Infrastructure\Framework\Symfony\Router;

use App\Core\Domain\Router\UrlGenerator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class SymfonyBaseUrlGenerator implements UrlGenerator
{
    public function __construct(
        private RequestStack $requestStack,
        private ParameterBagInterface $parameterBag
    )
    {
    }

    public function generate() : string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
    }

    public function generateUploadBaseurl() : string
    {
        list(,$uploadPath) = explode("public", $this->parameterBag->get('media.upload_folder.base_path'));
        return $this->generate() . $uploadPath;
    }
}