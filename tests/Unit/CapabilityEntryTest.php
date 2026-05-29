<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Unit;

use ITHilbert\AiManifest\Data\CapabilityEntry;
use ITHilbert\AiManifest\Data\CapabilityParam;
use ITHilbert\AiManifest\Enums\Mechanism;
use PHPUnit\Framework\TestCase;

final class CapabilityEntryTest extends TestCase
{
    public function test_to_array_renders_required_fields(): void
    {
        $entry = new CapabilityEntry(
            id: 'domains.list',
            method: 'GET',
            path: '/api/domains',
            summary: 'Liste alle Domains',
        );

        $array = $entry->toArray();

        $this->assertSame('domains.list', $array['id']);
        $this->assertSame('GET', $array['method']);
        $this->assertSame('/api/domains', $array['path']);
        $this->assertSame('Liste alle Domains', $array['summary']);
    }

    public function test_auth_false_is_absent_auth_true_is_present(): void
    {
        $withoutAuth = new CapabilityEntry('x', 'GET', '/x', 'X', auth: false);
        $withAuth = new CapabilityEntry('x', 'GET', '/x', 'X', auth: true);

        $this->assertArrayNotHasKey('auth', $withoutAuth->toArray());
        $this->assertTrue($withAuth->toArray()['auth']);
    }

    public function test_empty_params_are_absent_set_params_appear_as_map(): void
    {
        $withoutParams = new CapabilityEntry('x', 'GET', '/x', 'X');
        $this->assertArrayNotHasKey('params', $withoutParams->toArray());

        $param = new CapabilityParam('name', 'string', true, 'Domain-Name');
        $withParams = new CapabilityEntry('x', 'POST', '/x', 'X', params: [$param]);
        $array = $withParams->toArray();

        $this->assertArrayHasKey('params', $array);
        $this->assertArrayHasKey('name', $array['params']);
        $this->assertSame('string', $array['params']['name']['type']);
        $this->assertTrue($array['params']['name']['required']);
        $this->assertSame('Domain-Name', $array['params']['name']['desc']);
    }

    public function test_mechanism_absent_when_null_present_when_set(): void
    {
        $withoutMechanism = new CapabilityEntry('x', 'GET', '/x', 'X');
        $this->assertArrayNotHasKey('mechanism', $withoutMechanism->toArray());

        $withMechanism = new CapabilityEntry('x', 'GET', '/x', 'X', mechanism: Mechanism::Mcp);
        $this->assertSame('mcp', $withMechanism->toArray()['mechanism']);
    }

    public function test_param_without_desc_omits_desc_key(): void
    {
        $param = new CapabilityParam('page', 'integer', false);
        $entry = new CapabilityEntry('x', 'GET', '/x', 'X', params: [$param]);
        $array = $entry->toArray();

        $this->assertArrayNotHasKey('desc', $array['params']['page']);
    }
}
