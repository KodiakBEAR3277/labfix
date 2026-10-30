<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the two "system" routes that sit outside the web stack:
 *   - the token-guarded /cron/* entry points used by the external scheduler
 *   - the /storage/{path} fallback that serves uploads from the public disk
 */
class SystemRoutesTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-cron-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.cron_secret' => self::TOKEN]);
    }

    public function test_cron_endpoints_reject_a_missing_or_wrong_token(): void
    {
        $this->get('/cron/escalate')->assertForbidden();
        $this->get('/cron/close')->assertForbidden();

        $this->get('/cron/escalate', ['X-Cron-Token' => 'nope'])->assertForbidden();
    }

    public function test_cron_endpoints_run_when_the_token_matches(): void
    {
        $this->get('/cron/escalate', ['X-Cron-Token' => self::TOKEN])->assertOk();
        $this->get('/cron/close',    ['X-Cron-Token' => self::TOKEN])->assertOk();
    }

    public function test_storage_route_streams_a_stored_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('voice-notes/sample.m4a', 'audio-bytes');

        $response = $this->get('/storage/voice-notes/sample.m4a');

        $response->assertOk();
        $this->assertSame('audio-bytes', $response->streamedContent());
    }

    public function test_storage_route_404s_for_a_missing_file(): void
    {
        Storage::fake('public');

        $this->get('/storage/voice-notes/does-not-exist.m4a')->assertNotFound();
    }
}
