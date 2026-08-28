<?php

namespace App\Core\Infrastructure\Framework\Symfony\Describers;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use OpenApi\Annotations\OpenApi;
use Symfony\Component\Finder\Finder;

class ExternalDocDescriber implements DescriberInterface
{
    private const OPEN_API_FILES_DIR = '/etc/openapi/';

    public function __construct(
        private ParameterBagInterface $parameteBag,
        private KernelInterface $kernel
    )
    {
    }

    public function describe(OpenApi $api): void
    {
        $finder = new Finder();
        $filesPath = $this->kernel->getProjectDir() . self::OPEN_API_FILES_DIR;
        $finder->files()->in($filesPath)->name('*.yaml');

        foreach ($finder as $file) {
            Util::merge($api, Yaml::parseFile($file));
        }

        $api->servers[0]->url = $this->parameteBag->get('api_url');
    }
}