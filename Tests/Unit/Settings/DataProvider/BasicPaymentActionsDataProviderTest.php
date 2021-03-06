<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Settings\DataProvider;

use Oro\Bundle\AuthorizeNetBundle\Settings\DataProvider\BasicPaymentActionsDataProvider;

class BasicPaymentActionsDataProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPaymentActions()
    {
        $provider = new BasicPaymentActionsDataProvider();

        $this->assertEquals(
            [
                'authorize',
                'charge',
            ],
            $provider->getPaymentActions()
        );
    }
}
