<?php

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.
// Returns the private 'Frontastic\Common\SprykerBundle\Domain\Wishlist\Mapper\LineItemMapper' shared autowired service.

return $this->privates['Frontastic\\Common\\SprykerBundle\\Domain\\Wishlist\\Mapper\\LineItemMapper'] = new \Frontastic\Common\SprykerBundle\Domain\Wishlist\Mapper\LineItemMapper(($this->privates['Frontastic\\Common\\SprykerBundle\\Domain\\Product\\Mapper\\VariantMapper'] ?? ($this->privates['Frontastic\\Common\\SprykerBundle\\Domain\\Product\\Mapper\\VariantMapper'] = new \Frontastic\Common\SprykerBundle\Domain\Product\Mapper\VariantMapper())));
