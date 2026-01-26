<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TrainingEvaluationQuestion;

class QuestionEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // ================= PENYELENGGARAAN =================
            [
                'category' => 'penyelenggaraan',
                'question_text' => 'Apakah materi pelatihan sesuai dengan yang saudara butuhkan dalam menunjang pekerjaan?',
            ],
            [
                'category' => 'penyelenggaraan',
                'question_text' => 'Apakah waktu yang dibutuhkan dalam pelatihan ini mencukupi?',
            ],
            [
                'category' => 'penyelenggaraan',
                'question_text' => 'Apakah pembicara pelatihan menguasai materi yang disampaikan?',
            ],
            [
                'category' => 'penyelenggaraan',
                'question_text' => 'Apakah saudara menyukai cara presentasi/mengajar dari pembicara pelatihan?',
            ],
            [
                'category' => 'penyelenggaraan',
                'question_text' => 'Apakah anda puas dengan penyelenggaraan pelatihan ini (fasilitas, peralatan yang mendukung proses)?',
            ],
            [
                'category' => 'penyelenggaraan',
                'question_type' => 'text',
                'question_text' => 'Komentar tentang seputar penyelenggaraan dan pembicara.',
            ],

            // ================= DAMPAK =================
            [
                'category' => 'dampak',
                'question_text' => 'Apakah setelah mengikuti pelatihan tersebut keterampilan saudara meningkat?',
            ],
            [
                'category' => 'dampak',
                'question_text' => 'Apakah pelatihan yang saudara ikuti menunjang kebutuhan pekerjaan?',
            ],
            [
                'category' => 'dampak',
                'question_text' => 'Apakah pelatihan yang saudara ikuti dapat diterapkan di tempat kerja?',
            ],
            [
                'category' => 'dampak',
                'question_type' => 'text',
                'question_text' => 'Saran.',
            ],

            // ================= ATASAN =================
            [
                'category' => 'atasan',
                'question_text' => 'Apakah setelah mengikuti pelatihan tersebut keterampilan anak buah saudara meningkat?',
            ],
            [
                'category' => 'atasan',
                'question_text' => 'Apakah pelatihan yang anak buah saudara ikuti menunjang kebutuhan pekerjaan?',
            ],
            [
                'category' => 'atasan',
                'question_text' => 'Apakah pelatihan yang anak buah saudara ikuti diterapkan di tempat kerja?',
            ],
            [
                'category' => 'atasan',
                'question_type' => 'text',
                'question_text' => 'Saran.',
            ],
        ];

        foreach ($data as $item) {
            TrainingEvaluationQuestion::updateOrCreate(
                [
                    'category'      => $item['category'],
                    'question_text'=> $item['question_text'],
                ],
                [
                    'question_type' => $item['question_type'] ?? 'scale',
                    'is_active'     => true,
                ]
            );
        }
    }
}
