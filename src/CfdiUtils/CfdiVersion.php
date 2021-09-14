<?php

namespace CfdiUtils;

use CfdiUtils\VersionDiscovery\VersionDiscoverer;

/**
 * This class provides the methods to retrieve the version attribute from a
 * Comprobante Fiscal Digital por Internet (CFDI)
 *
 * It will not check anything but the value of the correct attribute
 * It will not care if the element is following a schema or element's name
 *
 * Possible values are always 3.2, 3.3 or empty string
 */
class CfdiVersion extends VersionDiscoverer
{
    public function rules(): array
    {
        return [
            '3.3' => 'Version',
            '3.2' => 'version',
        ];
    }
}
