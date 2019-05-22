<?php
namespace GingerPayments\Payment\Tests\Client;

use GingerPayments\Payment\Client\EndpointResolver;

class EndpointResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function itShouldReturnDefaultEndpoints()
    {
        $resolver = new EndpointResolver();
        
        $this->assertEquals(
            $resolver->getEndpointEms(),
            EndpointResolver::ENDPOINT_EMS
        );

    }
    
}
