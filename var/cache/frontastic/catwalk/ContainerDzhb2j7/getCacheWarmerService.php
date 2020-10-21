<?php

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.
// Returns the public 'cache_warmer' shared service.

return $this->services['cache_warmer'] = new \Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate(new RewindableGenerator(function () {
    yield 0 => ($this->privates['templating.cache_warmer.template_paths'] ?? $this->load('getTemplating_CacheWarmer_TemplatePathsService.php'));
    yield 1 => ($this->privates['validator.mapping.cache_warmer'] ?? $this->load('getValidator_Mapping_CacheWarmerService.php'));
    yield 2 => ($this->privates['router.cache_warmer'] ?? $this->load('getRouter_CacheWarmerService.php'));
    yield 3 => ($this->privates['annotations.cache_warmer'] ?? $this->load('getAnnotations_CacheWarmerService.php'));
    yield 4 => ($this->privates['serializer.mapping.cache_warmer'] ?? ($this->privates['serializer.mapping.cache_warmer'] = new \Symfony\Bundle\FrameworkBundle\CacheWarmer\SerializerCacheWarmer([], ($this->targetDir.''.'/serialization.php'))));
    yield 5 => ($this->privates['twig.cache_warmer'] ?? $this->load('getTwig_CacheWarmerService.php'));
    yield 6 => ($this->privates['twig.template_cache_warmer'] ?? $this->load('getTwig_TemplateCacheWarmerService.php'));
    yield 7 => ($this->privates['doctrine.orm.proxy_cache_warmer'] ?? $this->load('getDoctrine_Orm_ProxyCacheWarmerService.php'));
}, 8), false, ($this->targetDir.''.'/catwalkFrontastic_Catwalk_AppKernelProdContainerDeprecations.log'));
