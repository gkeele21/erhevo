<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PDF documents attached to a post. Separate from cover_image, which is
 * rendered as an <img> everywhere a post appears and so stays image-only.
 */
class PostAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function pdf(string $name = 'handout.pdf', int $kb = 40): UploadedFile
    {
        // A real %PDF- header so the mimetypes rule sniffs it as application/pdf.
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n" . str_repeat('0', $kb * 1024)
        );
    }

    public function test_a_pdf_can_be_uploaded_and_comes_back_with_its_metadata(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('attachments.store'), ['file' => $this->pdf()])
            ->assertOk()
            ->assertJsonStructure(['url', 'path', 'name', 'size']);

        $this->assertSame('handout.pdf', $response->json('name'));
        Storage::disk('public')->assertExists($response->json('path'));
        $this->assertStringStartsWith("post-attachments/{$user->id}/", $response->json('path'));
    }

    public function test_a_non_pdf_upload_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->postJson(route('attachments.store'), ['file' => UploadedFile::fake()->image('photo.jpg')])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_an_upload_requires_signing_in(): void
    {
        Storage::fake('public');

        $this->post(route('attachments.store'), ['file' => $this->pdf()])
            ->assertRedirect(route('login'));
    }

    public function test_a_pdf_can_be_attached_to_any_post_type(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        foreach (['scripture_help', 'story', 'thought'] as $type) {
            $this->actingAs($user)->post(route('posts.store'), [
                'post_type' => $type,
                'title' => "A {$type} with a handout",
                'content' => 'Body text.',
                'author_type' => 'self',
                'visibility' => 'private',
                'attachment_url' => '/storage/post-attachments/1/abc.pdf',
                'attachment_path' => 'post-attachments/1/abc.pdf',
                'attachment_name' => 'handout.pdf',
                'attachment_size' => 40960,
            ])->assertRedirect();

            $post = Post::where('title', "A {$type} with a handout")->sole();
            $this->assertSame('handout.pdf', $post->attachment_name);
            $this->assertSame(40960, $post->attachment_size);
            $this->assertSame('/storage/post-attachments/1/abc.pdf', $post->attachment_url);
        }
    }

    public function test_an_attachment_can_be_cleared_on_edit(): void
    {
        $user = User::factory()->create();
        $post = Post::create([
            'post_type' => 'story',
            'title' => 'A story with a handout',
            'content' => 'Body text.',
            'user_id' => $user->id,
            'author_type' => 'self',
            'visibility' => 'private',
            'attachment_url' => '/storage/post-attachments/1/abc.pdf',
            'attachment_path' => 'post-attachments/1/abc.pdf',
            'attachment_name' => 'handout.pdf',
            'attachment_size' => 40960,
        ]);

        $this->actingAs($user)->put(route('posts.update', $post), [
            'post_type' => 'story',
            'title' => $post->title,
            'content' => 'Body text.',
            'author_type' => 'self',
            'visibility' => 'private',
            'attachment_url' => null,
            'attachment_path' => null,
            'attachment_name' => null,
            'attachment_size' => null,
        ])->assertRedirect();

        $this->assertNull($post->fresh()->attachment_url);
    }

    public function test_deleting_an_attachment_is_scoped_to_its_owner(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $path = $this->actingAs($owner)
            ->post(route('attachments.store'), ['file' => $this->pdf()])
            ->json('path');

        $this->actingAs($other)
            ->deleteJson(route('attachments.destroy'), ['path' => $path])
            ->assertForbidden();
        Storage::disk('public')->assertExists($path);

        $this->actingAs($owner)
            ->deleteJson(route('attachments.destroy'), ['path' => $path])
            ->assertOk();
        Storage::disk('public')->assertMissing($path);
    }
}
