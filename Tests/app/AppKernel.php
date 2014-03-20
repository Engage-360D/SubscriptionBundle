<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Engage360d\Bundle\SubscriptionBundle\Engage360dSubscriptionBundle(),
        );

        return $bundles;
    }

    public function getCacheDir()
    {
        return realpath(__DIR__ . '/../..') . '/.tmp/cache';
    }

    public function getLogDir()
    {
        return realpath(__DIR__ . '/../..') . '/.tmp/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/config.yml');
    }

    protected function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            array(
                'kernel.vendor_dir' => realpath($this->rootDir . '/../../vendor'),
            )
        );
    }
}
