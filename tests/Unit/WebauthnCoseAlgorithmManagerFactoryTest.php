<?php

namespace Tests\Unit;

use Cose\Algorithm\Manager as CoseAlgorithmManager;
use Cose\Algorithm\ManagerFactory as CoseAlgorithmManagerFactory;
use Cose\Algorithm\Signature\EdDSA\Ed256;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebauthnCoseAlgorithmManagerFactoryTest extends TestCase
{
    #[Test]
    public function resolving_the_cose_algorithm_factory_does_not_throw(): void
    {
        $factory = app(CoseAlgorithmManagerFactory::class);
        $manager = app(CoseAlgorithmManager::class);

        $aliases = array_map(strval(...), iterator_to_array($factory->list()));

        $this->assertInstanceOf(CoseAlgorithmManagerFactory::class, $factory);
        $this->assertInstanceOf(CoseAlgorithmManager::class, $manager);
        $this->assertContains((string) Ed256::identifier(), $aliases);
    }
}
