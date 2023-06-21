<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\House;
use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_original_filename_upload(): void
    {
        // Arrange
        $filename = 'logo.jpg';

        // Act
        $response = $this->post('projects', [
            'name' => 'Some name',
            'logo' => UploadedFile::fake()->image($filename)
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('projects', [
            'name' => 'Some name',
            'logo' => $filename
        ]);
    }

    public function test_file_size_validation(): void
    {
        // Act
        $response = $this->post('projects', [
            'name' => 'Some name',
            'logo' => UploadedFile::fake()->create('logo.jpg', 2000)
        ]);
        // Assert
        $response->assertInvalid();

        // Act
        $response = $this->post('projects', [
            'name' => 'Some name',
            'logo' => UploadedFile::fake()->create('logo.jpg', 500)
        ]);
        // Assert
        $response->assertValid();
    }

    public function test_update_file_remove_old_one(): void
    {
        // Act
        $response = $this->post('houses', [
            'name' => 'Some name',
            'photo' => UploadedFile::fake()->image('photo.jpg')
        ]);
        // Assert
        $response->assertStatus(200);
        $house = House::first();
        $this->assertTrue(Storage::exists($house->photo));

        // Act
        $response = $this->put('houses/' . $house->id, [
            'name' => 'Some name',
            'photo' => UploadedFile::fake()->image('photo2.jpg')
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertFalse(Storage::exists($house->photo));
    }

    public function test_download_uploaded_file(): void
    {
        // Arrange
        $this->post('houses', [
            'name' => 'Some name',
            'photo' => UploadedFile::fake()->image('photo.jpg')
        ]);
        $house = House::first();

        // Act
        $response = $this->get('houses/download/' . $house->id);

        // Assert
        $response->assertStatus(200);
        $response->assertDownload(str_replace('houses/', '', $house->photo));
    }

    public function test_public_file_show(): void
    {
        // Arrange
        $filename = Str::random(8) . '.jpg';

        // Act
        $this->post('offices', [
            'name' => 'Some name',
            'photo' => UploadedFile::fake()->image($filename)
        ]);
        // Assert
        $office = Office::first();
        $this->assertTrue(Storage::disk('public')->exists('offices/' . $filename));

        // Act
        $response = $this->get('offices/' . $office->id);
        // Assert
        $response->assertStatus(200);
        $response->assertSee(public_path('offices/' . $filename));
    }

    public function test_upload_resize(): void
    {
        // Arrange
        $filename = Str::random(8) . '.jpg';

        // Act
        $response = $this->post('shops', [
            'name' => 'Some name',
            'photo' => UploadedFile::fake()->image($filename, 1000, 1000)
        ]);

        // Assert
        $response->assertStatus(200);
        $image = Image::make(storage_path('app/shops/resized-' . $filename));
        $this->assertEquals(500, $image->width());
        $this->assertEquals(500, $image->height());
    }

    public function test_spatie_media_library(): void
    {
        // Arrange
        $filename = Str::random(8) . '.jpg';
        // Act
        $response = $this->post('companies', [
            'name' => 'Some name',
            'photo' => UploadedFile::fake()->image($filename)
        ]);
        // Assert
        $response->assertStatus(200);

        // Arrange
        $company = Company::first();
        // Act
        $response = $this->get('companies/' . $company->id);
        // Assert
        $response->assertStatus(200);
        $response->assertSee('storage/' . $company->id . '/' . $filename);
    }
}
