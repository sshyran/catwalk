<?php

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.
// Returns the public 'Frontastic\Catwalk\FrontendBundle\Domain\SchemaService' shared service.

return $this->services['Frontastic\\Catwalk\\FrontendBundle\\Domain\\SchemaService'] = new \Frontastic\Catwalk\FrontendBundle\Domain\SchemaService(($this->services['Frontastic\\Catwalk\\FrontendBundle\\Gateway\\SchemaGateway'] ?? $this->load('getSchemaGatewayService.php')));
