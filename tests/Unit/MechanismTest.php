<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Unit;

use ITHilbert\AiManifest\Enums\Mechanism;
use PHPUnit\Framework\TestCase;

final class MechanismTest extends TestCase
{
    public function test_from_input_accepts_valid_values(): void
    {
        $this->assertSame(Mechanism::HttpApi, Mechanism::fromInput('http_api'));
        $this->assertSame(Mechanism::Mcp, Mechanism::fromInput('MCP'));
        $this->assertSame(Mechanism::ArtisanCommand, Mechanism::fromInput('  artisan_command  '));
    }

    public function test_from_input_rejects_unknown_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Mechanism::fromInput('graphql');
    }
}
