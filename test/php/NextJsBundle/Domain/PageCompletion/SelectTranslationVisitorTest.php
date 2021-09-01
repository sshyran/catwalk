<?php

namespace Frontastic\Catwalk\NextJsBundle\Domain\PageCompletion;

use Frontastic\Catwalk\ApiCoreBundle\Domain\Context;
use Frontastic\Common\ReplicatorBundle\Domain\Project;
use Frontastic\Common\SpecificationBundle\Domain\ConfigurationSchema;
use PHPUnit\Framework\TestCase;

class SelectTranslationVisitorTest extends TestCase
{
    private ConfigurationSchema $schema;

    public function setUp()
    {
        $this->schema = ConfigurationSchema::fromSchemaAndConfiguration(
            json_decode(file_get_contents(
                __DIR__ . '/_fixtures/translations_schema.json'
            ), true)['schema'],
            json_decode(file_get_contents(
                __DIR__ . '/_fixtures/translations_values.json'
            ), true)
        );
    }

    public function testFetchesSelectedLanguageWhenExists()
    {
        $visitor = new SelectTranslationVisitor(
            $this->contextFixture('de_LI')
        );

        $actualValues = $this->schema->getCompleteValues($visitor);

        $this->assertEquals('German Liechtenstein', $actualValues['fullTranslated']);
    }

    /**
     * @return Context
     */
    private function contextFixture(string $currentLocale): Context
    {
        return new Context([
            'locale' => $currentLocale,
            'project' => new Project([
                'languages' => [
                    'de_CH',
                    'de_LI',
                    'fr_CH',
                    'it_CH',
                ],
                'defaultLanguage' => 'de_CH',
            ])
        ]);
    }
}
