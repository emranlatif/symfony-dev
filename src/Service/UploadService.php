<?php

namespace App\Service;

use Random\RandomException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadService
{
    public function __construct(
        private readonly ContainerInterface $container
    )
    {
    }

    /**
     * @throws RandomException
     */
    public function upload(UploadedFile $file, $path = '/media/companies'){
        $name = bin2hex(random_bytes(16)).'.'.$file->guessExtension();

        $file->move($this->container->getParameter('kernel.project_dir').'/public'.$path, $name);

        return $name;
    }
}
