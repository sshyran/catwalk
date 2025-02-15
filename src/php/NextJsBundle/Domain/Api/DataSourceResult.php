<?php

namespace Frontastic\Catwalk\NextJsBundle\Domain\Api;

use Kore\DataObject\DataObject;

/**
 * Return type for "data-source" extensions. Can contain any payload, depending on the data source.
 *
 * Different data source implementations
 *
 * @type
 */
class DataSourceResult extends DataObject
{
    /**
     * Arbitrary (JSON serializable) payload information returned by the data source.
     *
     * This payload will be transmitted to the Tastics which are assigned to the corresponding data source in the
     * frontend. IMPORTANT: The payload must be JSON serializable (and therefore e.g. must not contain cyclic
     * references).
     *
     * @internal We might want to not deserialize this information in PHP to gain performance.
     * @required
     * @var mixed
     */
    public $dataSourcePayload;
}
