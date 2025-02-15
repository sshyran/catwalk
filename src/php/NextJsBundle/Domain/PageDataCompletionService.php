<?php

namespace Frontastic\Catwalk\NextJsBundle\Domain;

use Frontastic\Catwalk\ApiCoreBundle\Domain\Context;
use Frontastic\Catwalk\ApiCoreBundle\Domain\TasticService;
use Frontastic\Catwalk\FrontendBundle\Domain\Node;
use Frontastic\Catwalk\FrontendBundle\Domain\NodeService;
use Frontastic\Catwalk\FrontendBundle\Domain\Page;

use Frontastic\Catwalk\FrontendBundle\Domain\Tastic as TasticInstance;
use Frontastic\Catwalk\ApiCoreBundle\Domain\Tastic as TasticDefinition;
use Frontastic\Catwalk\NextJsBundle\Domain\PageCompletion\FieldVisitorFactory;
use Frontastic\Common\SpecificationBundle\Domain\ConfigurationSchema;

class PageDataCompletionService
{
    private TasticService $tasticService;
    private NodeService $nodeService;
    private FieldVisitorFactory $fieldVisitorFactory;

    public function __construct(
        TasticService $tasticService,
        NodeService $nodeService,
        FieldVisitorFactory $fieldVisitorFactory
    ) {
        $this->tasticService = $tasticService;
        $this->fieldVisitorFactory = $fieldVisitorFactory;
        $this->nodeService = $nodeService;
    }

    public function completeNodeData(Node $node, Context $context): void
    {
        $this->nodeService->completeCustomNodeData(
            $node,
            $this->fieldVisitorFactory->createNodeDataVisitor($context)
        );
    }

    public function completePageData(Page $page, Node $node, Context $context, object $tasticFieldData): void
    {
        $this->tasticService->getTasticsMappedByType();

        foreach ($page->regions as $region) {
            foreach ($region->elements as $element) {
                foreach ($element->tastics as $tasticInstance) {
                    $this->completeTasticData(
                        $tasticInstance,
                        $node,
                        $context,
                        $tasticFieldData
                    );
                }
            }
        }
    }

    private function completeTasticData(
        TasticInstance $tasticInstance,
        Node $node,
        Context $context,
        object $tasticFieldData
    ) {
        $tasticDefinition = $this->getTasticDefinition($tasticInstance->tasticType);
        if ($tasticDefinition === null) {
            return;
        }

        $baseConfigurationBackup = [
            'mobile' => $tasticInstance->configuration->mobile,
            'tablet' => $tasticInstance->configuration->tablet,
            'desktop' => $tasticInstance->configuration->desktop,
        ];

        $schema = ConfigurationSchema::fromSchemaAndConfiguration(
            $tasticDefinition->configurationSchema['schema'],
            (array)$tasticInstance->configuration
        );

        $tasticInstanceId = $tasticInstance->tasticId;

        $fieldVisitor = $this->fieldVisitorFactory->createTasticDataVisitor(
            $context,
            ($tasticFieldData->$tasticInstanceId ?? [])
        );

        $tasticInstance->configuration = new TasticInstance\Configuration(
            array_merge(
                $schema->getCompleteValues($fieldVisitor),
                $baseConfigurationBackup
            )
        );
    }

    private function getTasticDefinition(string $tasticType): ?TasticDefinition
    {
        // TODO: Cache
        $tasticMap = $this->tasticService->getTasticsMappedByType();
        if (!isset($tasticMap[$tasticType])) {
            return null;
        }
        return $tasticMap[$tasticType];
    }
}
