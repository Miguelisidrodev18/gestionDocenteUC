<?php

use App\Models\CvDocument;
use App\Models\Docente;
use App\Models\User;
use App\Services\CVBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('generates a filled CV for a docente', function () {
    Storage::fake('cvs');

    $user = User::factory()->create([
        'role' => 'docente',
    ]);

    $docente = Docente::factory()->create([
        'user_id'  => $user->id,
        'nombre'   => 'Juan',
        'apellido' => 'Pérez',
        'dni'      => '12345678',
        'email'    => 'juan@example.com',
    ]);

    // Plantilla ficticia: se mockea el builder para no depender de PhpWord en el test
    $fakePath = 'generados/'.$docente->id.'/CV_'.$docente->id.'_test.docx';
    Storage::disk('cvs')->put($fakePath, 'dummy');

    $this->mock(CVBuilder::class, function ($mock) use ($docente, $fakePath) {
        $mock->shouldReceive('buildForDocente')
            ->once()
            ->with($docente)
            ->andReturn($fakePath);
    });

    $response = $this
        ->actingAs($user)
        ->get(route('cv.generar', ['docente' => $docente->id]));

    $response->assertOk();
    $response->assertHeader('content-disposition');
});

it('uploads a CV file and stores metadata', function () {
    Storage::fake('cvs');

    $user = User::factory()->create([
        'role' => 'docente',
    ]);

    $docente = Docente::factory()->create([
        'user_id'  => $user->id,
        'nombre'   => 'María',
        'apellido' => 'García',
        'dni'      => '87654321',
        'email'    => 'maria@example.com',
    ]);

    $file = UploadedFile::fake()->create(
        'cv.docx',
        100,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    );

    $response = $this
        ->actingAs($user)
        ->post(route('cv.upload', ['docente' => $docente->id]), [
            'cv' => $file,
        ]);

    $response->assertOk();
    $response->assertJsonStructure(['message', 'id']);

    $this->assertDatabaseCount('cv_documents', 1);

    $cv = CvDocument::first();

    Storage::disk('cvs')->assertExists($cv->path);
    expect($cv->docente_id)->toBe($docente->id);
    expect($cv->uploaded_by)->toBe($user->id);
});

