<?php

namespace Tests\Feature;

use App\Models\QuoteRequest;
use App\Models\QuoteRequestAttachment;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecureUploadTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
        Storage::fake('public');

        $this->service = Service::create(['name' => 'Web Development', 'slug' => 'web-development', 'is_active' => true]);
    }

    private function submitQuote(array $files, ?User $as = null)
    {
        $request = $as ? $this->actingAs($as) : $this;

        return $request->post('/quote', [
            'service_id' => $this->service->id,
            'title' => 'New site',
            'description' => 'Need a website',
            'guest_name' => 'Guest',
            'guest_email' => 'guest@gmail.com',
            'attachments' => $files,
        ]);
    }

    private function realPng(int $width = 20, int $height = 20, string $append = ''): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean().$append;

        $path = tempnam(sys_get_temp_dir(), 'png');
        file_put_contents($path, $bytes);

        return new UploadedFile($path, 'sample.png', 'image/png', null, true);
    }

    private function fileWithContents(string $name, string $contents, string $claimedMime): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, $claimedMime, null, true);
    }

    public function test_valid_image_video_and_text_are_stored_privately(): void
    {
        $mp4 = $this->fileWithContents('clip.mp4', "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat("\x00", 64), 'video/mp4');
        $txt = $this->fileWithContents('brief.txt', "Plain text brief.\nSecond line.", 'text/plain');

        $this->submitQuote([$this->realPng(), $mp4, $txt])->assertSessionHasNoErrors()->assertRedirect();

        $attachments = QuoteRequestAttachment::all();
        $this->assertCount(3, $attachments);

        foreach ($attachments as $attachment) {
            $this->assertSame('local', $attachment->disk);
            Storage::disk('local')->assertExists($attachment->path);
            // Stored under a random name, never the client's.
            $this->assertStringNotContainsString('sample', $attachment->path);
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertEqualsCanonicalizing(['image/png', 'video/mp4', 'text/plain'], $attachments->pluck('mime_type')->all());
    }

    public function test_a_script_renamed_to_jpg_is_rejected(): void
    {
        // Deliberately harmless: a real web-shell string here gets the temp file
        // quarantined by Windows Defender before the test can read it.
        $fake = $this->fileWithContents('photo.jpg', "<?php echo 'not really a photo'; ?>", 'image/jpeg');

        $this->submitQuote([$fake])->assertSessionHasErrors('attachments.0');
        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_disallowed_types_are_rejected(): void
    {
        foreach ([
            ['setup.exe', "MZ\x90\x00".str_repeat("\x00", 100), 'application/octet-stream'],
            ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'image/svg+xml'],
            ['files.zip', "PK\x03\x04".str_repeat("\x00", 100), 'application/zip'],
        ] as [$name, $contents, $mime]) {
            $this->submitQuote([$this->fileWithContents($name, $contents, $mime)])->assertSessionHasErrors('attachments.0');
        }

        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_image_decompression_bomb_is_rejected_before_decoding(): void
    {
        // A real 1x1 PNG whose header claims 50,000 x 50,000 pixels: tiny on
        // disk, ~10 GB once decoded.
        $png = $this->realPng(1, 1);
        $bytes = file_get_contents($png->getRealPath());
        $bytes = substr_replace($bytes, pack('NN', 50000, 50000), 16, 8);
        file_put_contents($png->getRealPath(), $bytes);

        $this->submitQuote([$png])->assertSessionHasErrors('attachments.0');
        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_images_are_reencoded_so_appended_payloads_are_dropped(): void
    {
        $this->submitQuote([$this->realPng(append: '<?php echo "payload"; ?>')])->assertSessionHasNoErrors();

        $stored = Storage::disk('local')->get(QuoteRequestAttachment::first()->path);
        $this->assertStringNotContainsString('payload', $stored);
        $this->assertNotFalse(imagecreatefromstring($stored));
    }

    public function test_too_many_files_are_rejected(): void
    {
        $files = array_map(fn () => $this->realPng(), range(1, 6));

        $this->submitQuote($files)->assertSessionHasErrors('attachments');
    }

    public function test_only_people_who_can_see_the_request_can_open_its_attachments(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');
        $this->submitQuote([$this->realPng()], $client)->assertSessionHasNoErrors();

        $quote = QuoteRequest::first();
        $attachment = $quote->attachments->first();
        $url = route('quote-requests.attachments.show', [$quote, $attachment]);

        $this->actingAs($client)->get($url)->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $ceo = User::where('email', 'ceo@undek.com')->first();
        $this->actingAs($ceo)->get($url)->assertOk();

        $stranger = User::factory()->create();
        $stranger->assignRole('client');
        $this->actingAs($stranger)->get($url)->assertForbidden();

        auth()->logout();
        $this->get($url)->assertRedirect('/login');
    }
}
